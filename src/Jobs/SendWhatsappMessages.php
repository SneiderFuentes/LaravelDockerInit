<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Application\Services\SendWhatsappMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @param AppointmentRepositoryInterface $appointmentRepository
     * @param SendWhatsappMessageService $whatsappService
     * @return void
     */
    public function handle(
        AppointmentRepositoryInterface $appointmentRepository,
        SendWhatsappMessageService $whatsappService
    ): void {
        Log::info('Starting to send WhatsApp messages');

        // Obtener citas programadas para enviar recordatorios
        $appointments = $appointmentRepository->findScheduledAppointments();

        Log::info('Found ' . count($appointments) . ' appointments to send WhatsApp messages');

        foreach ($appointments as $appointment) {
            try {
                $parameters = [
                    'patient_name' => $appointment->patientName(),
                    'appointment_date' => $appointment->scheduledAt()->format('d/m/Y'),
                    'appointment_time' => $appointment->scheduledAt()->format('H:i'),
                    'clinic_name' => 'Clínica Ejemplo'
                ];

                $whatsappService->sendTemplateMessage(
                    $appointment->patientPhone(),
                    'appointment_reminder',
                    $parameters,
                    $appointment->id()->value(),
                    $appointment->patientId()->value()
                );

                Log::info('Sent WhatsApp template message for appointment', [
                    'appointment_id' => $appointment->id()->value(),
                    'phone' => $appointment->patientPhone()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send WhatsApp template message for appointment', [
                    'appointment_id' => $appointment->id()->value(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Finished sending WhatsApp messages');
    }
}
