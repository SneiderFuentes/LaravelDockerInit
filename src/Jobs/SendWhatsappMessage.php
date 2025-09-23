<?php

namespace Core\Jobs;

use Core\BoundedContext\CommunicationManagement\Application\Services\SendWhatsappMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappMessage implements ShouldQueue
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
        Log::error('SendWhatsappMessage job failed', [
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
    public function handle(SendWhatsappMessageService $whatsappService): void
    {
            // Enviar flujo de confirmación de cita por WhatsApp
            $success = $whatsappService->sendAppointmentConfirmationFlow($this->appointmentData);

            // Log final con información esencial
            Log::info('WHATSAPP_MESSAGE_RESULT', [
                'status' => $success ? 'SENT_SUCCESS' : 'SENT_FAILED',
                'appointment_data' => $this->appointmentData
            ]);
    }
}
