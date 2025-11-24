<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetAvailableSlotsByCupHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;
use Illuminate\Support\Carbon;

/**
 * Estructura JSON enviada al webhook:
 * {
 *   "status": "ok" | "error",
 *   "message": "...",
 *   "data": [...],
 *   ...extra data...
 * }
 */
class GetAvailableSlotsByCupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 180; // 3 minutos, esta consulta puede ser pesada.

    private const REDIS_KEY_PREFIX = 'slots_pagination:';
    private const REDIS_TTL_HOURS = 8;

    private array $procedures;
    private int $espacios;
    private string $resumeKey;
    private int $patientAge;
    private bool $isContrasted;
    private bool $isSedated;
    private string $patientId;
    private ?string $afterDate;

    public function __construct(array $procedures, int $espacios, string $resumeKey, int $patientAge, bool $isContrasted, bool $isSedated = false, string $patientId = '', ?string $afterDate = null)
    {
        $this->procedures = $procedures;
        $this->espacios = $espacios;
        $this->resumeKey = $resumeKey;
        $this->patientAge = $patientAge;
        $this->isContrasted = $isContrasted;
        $this->isSedated = $isSedated;
        $this->patientId = $patientId;
        $this->afterDate = $afterDate;
    }

    public function handle(GetAvailableSlotsByCupHandler $handler, WebhookNotifierService $notifier): void
    {
        Log::info('----OBTENER ESPACIOS Job en ejecución', ['attempts' => $this->attempts(), 'after_date' => $this->afterDate]);
        try {
            $slots = $handler->handle($this->procedures, $this->espacios, $this->patientAge, $this->isContrasted, $this->isSedated, $this->patientId, $this->afterDate);

            // Si la paginación devolvió 0 slots y había un afterDate, reiniciar y buscar desde el inicio
            if (empty($slots) && $this->afterDate !== null && !empty($this->patientId)) {
                Log::info('Pagination returned 0 slots, resetting to first slots', [
                    'patient_id' => $this->patientId,
                    'previous_after_date' => $this->afterDate
                ]);

                // Eliminar registro de Redis
                self::clearPaginationFromRedis($this->patientId, $this->procedures);

                // Buscar desde el inicio (sin afterDate)
                $slots = $handler->handle($this->procedures, $this->espacios, $this->patientAge, $this->isContrasted, $this->isSedated, $this->patientId, null);
            }

            $selectionText = '';
            $lastSlotDatetime = null;

            if (!empty($slots)) {
                $selectionText = "Hemos encontrado los siguientes horarios disponibles 👇\n\n";
                foreach ($slots as $index => $slot) {
                    $date = Carbon::parse($slot->fecha)->locale('es')->isoFormat('D [de] MMMM');
                    $optionNumber = $index + 1;
                    $selectionText .= "{$optionNumber}. {$date} a las {$slot->hora} con el Dr. {$slot->doctorName}.\n";
                }

                // Obtener fecha y hora del último slot para paginación
                $lastSlot = end($slots);
                $lastSlotDatetime = $lastSlot->fecha . ' ' . $lastSlot->hora;

                $selectionText .= "\n0. Ver otras fechas disponibles\n";
                $selectionText .= "\nPor favor responde únicamente con el número de la opción que deseas programar ✍️\n";
                $selectionText .= "(Ejemplo: escribe 1 si deseas el primer horario)\n";
                $selectionText .= "(Ejemplo: escribe 2 si deseas el segundo horario)";
            } else {
                $selectionText = "Lo sentimos, no hemos encontrado horarios disponibles para los procedimientos seleccionados. Por favor, intenta de nuevo en 24 horas";
            }

            // Guardar last_slot_datetime en Redis para paginación
            if ($lastSlotDatetime && !empty($this->patientId) && !empty($this->procedures[0]['cups'])) {
                $redisKey = $this->buildRedisKey();
                Redis::setex($redisKey, self::REDIS_TTL_HOURS * 3600, $lastSlotDatetime);
                Log::info('Stored last_slot_datetime in Redis for pagination', [
                    'redis_key' => $redisKey,
                    'last_slot_datetime' => $lastSlotDatetime,
                    'ttl_hours' => self::REDIS_TTL_HOURS
                ]);
            }

            $payload = [
                'status' => 'ok',
                'message' => 'Available slots listed successfully',
                'selection_text' => $selectionText,
                'total' => is_array($slots) ? count($slots) : 0,
                'data' => $slots,
                'last_slot_datetime' => $lastSlotDatetime
            ];

            $notifier->notifyFromConfig($this->resumeKey, $payload, 'GetAvailableSlotsByCupJob - ');
        } catch (\Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $errorPayload = ['status' => 'error', 'message' => 'No pudimos buscar los horarios disponibles en este momento. Inténtalo de nuevo más tarde.'];
                $notifier->notifyFromConfig($this->resumeKey, $errorPayload, 'GetAvailableSlotsByCupJob - FINAL ATTEMPT - ');
            }
            throw $e;
        }
    }

    /**
     * Construye la clave Redis para almacenar el último slot datetime
     * Usa todos los CUPS ordenados para generar una clave única
     */
    private function buildRedisKey(): string
    {
        return self::buildRedisKeyFromProcedures($this->patientId, $this->procedures);
    }

    /**
     * Construye la clave Redis a partir de un array de procedures
     * @param string $patientId ID del paciente
     * @param array $procedures Array de procedimientos con clave 'cups'
     * @return string Clave Redis
     */
    public static function buildRedisKeyFromProcedures(string $patientId, array $procedures): string
    {
        $cupsCodes = array_map(fn($p) => $p['cups'] ?? '', $procedures);
        sort($cupsCodes);
        $cupsHash = implode('_', $cupsCodes);
        return self::REDIS_KEY_PREFIX . $patientId . ':' . $cupsHash;
    }

    /**
     * Recupera el último slot datetime desde Redis para paginación
     * @param string $patientId ID del paciente
     * @param array $procedures Array de procedimientos con clave 'cups'
     * @return string|null Datetime en formato 'Y-m-d H:i' o null si no existe
     */
    public static function getLastSlotDatetimeFromRedis(string $patientId, array $procedures): ?string
    {
        $redisKey = self::buildRedisKeyFromProcedures($patientId, $procedures);
        $value = Redis::get($redisKey);

        if ($value) {
            Log::info('Retrieved last_slot_datetime from Redis', [
                'redis_key' => $redisKey,
                'last_slot_datetime' => $value
            ]);
        }

        return $value ?: null;
    }

    /**
     * Elimina el registro de paginación de Redis
     * @param string $patientId ID del paciente
     * @param array $procedures Array de procedimientos con clave 'cups' o 'code'
     */
    public static function clearPaginationFromRedis(string $patientId, array $procedures): void
    {
        // Normalizar: puede venir como 'cups' o 'code'
        $normalizedProcedures = array_map(function($p) {
            return ['cups' => $p['cups'] ?? $p['code'] ?? ''];
        }, $procedures);

        $redisKey = self::buildRedisKeyFromProcedures($patientId, $normalizedProcedures);
        Redis::del($redisKey);
        Log::info('Cleared slots pagination from Redis', [
            'redis_key' => $redisKey,
            'patient_id' => $patientId
        ]);
    }
}
