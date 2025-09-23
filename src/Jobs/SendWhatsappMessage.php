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
            'appointment_id' => $this->appointmentData['appointment_id'] ?? 'N/A',
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
        try {
            Log::info('Starting WhatsApp message job', [
                'center_key' => $this->centerKey,
                'appointment_id' => $this->appointmentData['appointment_id'],
                'patient_name' => $this->appointmentData['patient_name'],
                'patient_phone' => $this->appointmentData['phone']
            ]);

            // Enviar flujo de confirmación de cita por WhatsApp
            $success = $whatsappService->sendAppointmentConfirmationFlow($this->appointmentData);

            if ($success) {
                Log::info('WHATSAPP MESSAGE SENT SUCCESSFULLY', [
                    'appointment_id' => $this->appointmentData['appointment_id'],
                    'patient_name' => $this->appointmentData['patient_name'],
                    'patient_phone' => $this->appointmentData['phone'],
                    'appointment_date' => $this->appointmentData['appointment_date'],
                    'appointment_time' => $this->appointmentData['appointment_time'],
                    'clinic' => $this->appointmentData['clinic_name'],
                    'procedures' => $this->appointmentData['procedures'],
                    'center_key' => $this->centerKey,
                    'status' => 'WHATSAPP_SENT_SUCCESS'
                ]);
            } else {
                Log::error('WHATSAPP MESSAGE FAILED TO SEND', [
                    'appointment_id' => $this->appointmentData['appointment_id'],
                    'patient_name' => $this->appointmentData['patient_name'],
                    'patient_phone' => $this->appointmentData['phone'],
                    'appointment_date' => $this->appointmentData['appointment_date'],
                    'appointment_time' => $this->appointmentData['appointment_time'],
                    'clinic' => $this->appointmentData['clinic_name'],
                    'procedures' => $this->appointmentData['procedures'],
                    'center_key' => $this->centerKey,
                    'status' => 'WHATSAPP_SENT_FAILED',
                    'reason' => 'Bird WhatsApp API returned false'
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Critical error in SendWhatsappMessage job', [
                'center_key' => $this->centerKey,
                'appointment_id' => $this->appointmentData['appointment_id'] ?? 'N/A',
                'patient_name' => $this->appointmentData['patient_name'] ?? 'N/A',
                'patient_phone' => $this->appointmentData['phone'] ?? 'N/A',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
