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

    private array $procedures;
    private int $espacios;
    private string $resumeKey;

    public function __construct(array $procedures, int $espacios, string $resumeKey)
    {
        $this->procedures = $procedures;
        $this->espacios = $espacios;
        $this->resumeKey = $resumeKey;
    }

    public function handle(GetAvailableSlotsByCupHandler $handler, WebhookNotifierService $notifier): void
    {
        Log::info('----OBTENER ESPACIOS Job en ejecución', ['attempts' => $this->attempts()]);
        try {
            $slots = $handler->handle($this->procedures, $this->espacios);

            $selectionText = '';
            if (!empty($slots)) {
                $selectionText = "Estos son los horarios que encontramos para ti. Por favor, elige uno enviando el número correspondiente:\n\n";
                foreach ($slots as $index => $slot) {
                    $date = Carbon::parse($slot->fecha)->locale('es')->isoFormat('D [de] MMMM');
                    $optionNumber = $index + 1;
                    $selectionText .= "{$optionNumber}. {$date} a las {$slot->hora} con el Dr. {$slot->doctorName}.\n";
                }
            } else {
                $selectionText = "Lo sentimos, no hemos encontrado horarios disponibles para los procedimientos seleccionados. Por favor, intenta de nuevo en 24 horas";
            }

            $payload = [
                'status' => 'ok',
                'message' => 'Available slots listed successfully',
                'selection_text' => $selectionText,
                'total' => is_array($slots) ? count($slots) : 0,
                'data' => $slots
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
}
