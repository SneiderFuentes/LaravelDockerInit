<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Application\Services\SendWhatsappMessageService;
use Core\BoundedContext\SubaccountManagement\Domain\Repositories\SubaccountRepositoryInterface;
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
     * @param SubaccountRepositoryInterface $subaccountRepository
     * @return void
     */
    public function handle(
        AppointmentRepositoryInterface $appointmentRepository,
        SendWhatsappMessageService $whatsappService,
        SubaccountRepositoryInterface $subaccountRepository
    ): void {
        Log::info('Starting to send WhatsApp messages');

        // Obtener la lista de subcuentas (centros)
        $subaccounts = $subaccountRepository->findAll();
        Log::info('Found ' . count($subaccounts) . ' subaccounts to process');

        $totalAppointments = 0;
        $totalMessagesSent = 0;

        foreach ($subaccounts as $subaccount) {
            Log::info('Processing center: ' . $subaccount->key());

            try {
                // Obtener citas programadas para este centro específico
                $appointments = $appointmentRepository->findPendingInDateRange(
                    $subaccount->key(),
                    now()->startOfDay(),
                    now()->addDays(1)->endOfDay()
                );

                Log::info('Found ' . count($appointments) . ' appointments for center: ' . $subaccount->key());
                $totalAppointments += count($appointments);

                foreach ($appointments as $appointment) {
                    try {
                        $parameters = [
                            'patient_name' => $appointment->patientName(),
                            'appointment_date' => $appointment->date()->format('d/m/Y'),
                            'appointment_time' => $appointment->timeSlot(),
                            'clinic_name' => $subaccount->name(),
                            'doctor_data' => $appointment->doctorData,
                            'procedure_data' => $appointment->cupData
                        ];

                        $whatsappService->sendTemplateMessage(
                            $appointment->patientPhone(),
                            'appointment_reminder',
                            $parameters,
                            $appointment->id(),
                            $appointment->patientId(),
                            $subaccount->key()
                        );

                        $totalMessagesSent++;

                        Log::info('Sent WhatsApp template message for appointment', [
                            'appointment_id' => $appointment->id(),
                            'phone' => $appointment->patientPhone(),
                            'center' => $subaccount->key()
                        ]);
                        break;
                    } catch (\Exception $e) {
                        Log::error('Failed to send WhatsApp template message for appointment', [
                            'appointment_id' => $appointment->id(),
                            'error' => $e->getMessage(),
                            'center' => $subaccount->key()
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to process center: ' . $subaccount->key(), [
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Finished sending WhatsApp messages', [
            'total_appointments' => $totalAppointments,
            'total_messages_sent' => $totalMessagesSent
        ]);
    }
}
