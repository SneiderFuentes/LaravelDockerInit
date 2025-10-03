<?php

namespace Core\Jobs;

use Core\BoundedContext\CommunicationManagement\Application\Services\MakeAutomaticCallService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MakePendingAppointmentCall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 60; // 1 minuto

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $appointmentData,
        private string $centerKey
    ) {}

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('MakePendingAppointmentCall job failed', [
            'center_key' => $this->centerKey,
            'patient_id' => $this->appointmentData['patient_id'] ?? 'N/A',
            'patient_name' => $this->appointmentData['patient_name'] ?? 'N/A',
            'patient_phone' => $this->appointmentData['phone'] ?? 'N/A',
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(MakeAutomaticCallService $callService): void
    {
            // Realizar llamada automática para recordatorio de cita PENDIENTE
            $success = $callService->makePendingAppointmentCall($this->appointmentData);

            // Log final con información esencial
            Log::info('PENDING_APPOINTMENT_CALL_RESULT', [
                'status' => $success ? 'CALL_SUCCESS' : 'CALL_FAILED',
                'appointment_data' => $this->appointmentData
            ]);
    }
}
