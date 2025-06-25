<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\ConfirmAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Commands\ConfirmAppointmentCommand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\ConfirmationChannelType;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentNotFoundException;
use InvalidArgumentException;

/**
 * Estructura JSON enviada al webhook:
 * {
 *   "status": "ok" | "error",
 *   "appointment_id": "...",
 *   "message": "...",
 *   ...extra data...
 * }
 */
class ConfirmAppointmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 60; // 1 minuto, la confirmación debería ser rápida.

    private array $requestData;
    private string $centerKey;
    private string $id;
    private string $resumeKey;

    public function __construct(array $requestData, string $centerKey, string $id, string $resumeKey)
    {
        $this->requestData = $requestData;
        $this->centerKey = $centerKey;
        $this->id = $id;
        $this->resumeKey = $resumeKey;
    }

    public function handle(ConfirmAppointmentHandler $handler, WebhookNotifierService $notifier): void
    {
        try {
            Log::info('----CONFIRMAR CITA Job en ejecución', ['attempts' => $this->attempts()]);
            $channelTypeString = $this->requestData['channel_type'] ?? null;
            $channelType = $channelTypeString ? ConfirmationChannelType::from($channelTypeString) : null;

            $command = new ConfirmAppointmentCommand(
                $this->id,
                $this->centerKey,
                $this->requestData['channel_id'] ?? null,
                $channelType
            );
            $result = $handler->handle($command);
            $payload = [
                'status' => 'ok',
                'appointment_id' => $result->id ?? null,
                'message' => 'Tu cita ha sido confirmada con exito! Te llevaré al listado de citas',
                'data' => $result->toArray()
            ];

            $notifier->notifyFromConfig($this->resumeKey, $payload, 'ConfirmAppointmentJob - ');
        } catch (AppointmentNotFoundException | InvalidArgumentException $e) {
            if ($this->attempts() >= $this->tries) {
                $errorPayload = ['status' => 'error', 'appointment_id' => $this->id, 'message' => 'No pudimos confirmar la cita en este momento. Inténtalo de nuevo más tarde.'];
                $notifier->notifyFromConfig($this->resumeKey, $errorPayload, 'ConfirmAppointmentJob - FINAL ATTEMPT - ');
            }
            throw $e;
        }
    }
}
