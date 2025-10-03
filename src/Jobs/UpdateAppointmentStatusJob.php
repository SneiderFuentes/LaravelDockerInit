<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\CancelAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ConfirmAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Commands\CancelAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Commands\ConfirmAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\ConfirmationChannelType;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentNotFoundException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateAppointmentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60; // 1 minuto

    private string $appointmentId;
    private string $centerKey;
    private string $action; // 'confirm' o 'cancel'
    private ?string $channelId;
    private ?string $channelType;
    private ?string $reason;

    public function __construct(
        string $appointmentId,
        string $centerKey,
        string $action,
        ?string $channelId = null,
        ?string $channelType = null,
        ?string $reason = null
    ) {
        $this->appointmentId = $appointmentId;
        $this->centerKey = $centerKey;
        $this->action = $action;
        $this->channelId = $channelId;
        $this->channelType = $channelType;
        $this->reason = $reason;
    }

    public function handle(
        ConfirmAppointmentHandler $confirmHandler,
        CancelAppointmentHandler $cancelHandler
    ): void {
        try {
            Log::info("UpdateAppointmentStatusJob - {$this->action} appointment", [
                'appointment_id' => $this->appointmentId,
                'center_key' => $this->centerKey,
                'action' => $this->action,
                'attempts' => $this->attempts()
            ]);

            if ($this->action === 'confirm') {
                $this->confirmAppointment($confirmHandler);
            } elseif ($this->action === 'cancel') {
                $this->cancelAppointment($cancelHandler);
            } else {
                throw new \InvalidArgumentException("Acción no válida: {$this->action}. Debe ser 'confirm' o 'cancel'");
            }

            Log::info("UpdateAppointmentStatusJob - {$this->action} completed successfully", [
                'appointment_id' => $this->appointmentId
            ]);

        } catch (AppointmentNotFoundException $e) {
            Log::error("UpdateAppointmentStatusJob - Appointment not found", [
                'appointment_id' => $this->appointmentId,
                'action' => $this->action,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error("UpdateAppointmentStatusJob - Error during {$this->action}", [
                'appointment_id' => $this->appointmentId,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    private function confirmAppointment(ConfirmAppointmentHandler $handler): void
    {
        $channelType = $this->channelType ? ConfirmationChannelType::from($this->channelType) : null;

        $command = new ConfirmAppointmentCommand(
            $this->appointmentId,
            $this->centerKey,
            $this->channelId,
            $channelType
        );

        $result = $handler->handle($command);

        Log::info("Appointment confirmed successfully", [
            'appointment_id' => $result->id,
            'channel_id' => $this->channelId,
            'channel_type' => $this->channelType
        ]);
    }

    private function cancelAppointment(CancelAppointmentHandler $handler): void
    {
        $channelType = $this->channelType ? ConfirmationChannelType::from($this->channelType) : null;

        $command = new CancelAppointmentCommand(
            $this->appointmentId,
            $this->centerKey,
            $this->reason ?? 'Cancelled from system',
            $this->channelId,
            $channelType
        );

        $result = $handler->handle($command);

        Log::info("Appointment cancelled successfully", [
            'appointment_id' => $result->id,
            'channel_id' => $this->channelId,
            'channel_type' => $this->channelType,
            'reason' => $this->reason
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("UpdateAppointmentStatusJob failed completely", [
            'appointment_id' => $this->appointmentId,
            'center_key' => $this->centerKey,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    }

    /**
     * Despacha job para confirmar cita
     */
    public static function confirm(
        string $appointmentId,
        string $centerKey,
        ?string $channelId = null,
        ?string $channelType = null
    ): void {
        self::dispatch($appointmentId, $centerKey, 'confirm', $channelId, $channelType);
    }

    /**
     * Despacha job para cancelar cita
     */
    public static function cancel(
        string $appointmentId,
        string $centerKey,
        ?string $channelId = null,
        ?string $channelType = null,
        ?string $reason = null
    ): void {
        self::dispatch($appointmentId, $centerKey, 'cancel', $channelId, $channelType, $reason);
    }
}
