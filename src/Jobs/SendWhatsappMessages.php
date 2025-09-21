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
use Carbon\Carbon;

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
                $startDate = now()->addDay()->startOfDay(); // Mañana desde las 00:00
                $endDate = now()->addDay()->endOfDay(); // Mañana hasta las 23:59

                Log::info('Filtering appointments by date range', [
                    'center' => $subaccount->key(),
                    'start_date' => $startDate->format('Y-m-d H:i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s')
                ]);

                // Obtener citas programadas para este centro específico
                $appointments = $appointmentRepository->findPendingInDateRange(
                    $subaccount->key(),
                    $startDate,
                    $endDate
                );

                Log::info('Found ' . count($appointments) . ' appointments for center: ' . $subaccount->key());
                $totalAppointments += count($appointments);

                foreach ($appointments as $appointment) {
                    try {
                        // Formatear la fecha en español
                        $appointmentDate = Carbon::parse($appointment->date())->locale('es')->isoFormat('D [de] MMMM [de] YYYY');

                        // Formatear la hora
                        $timeSlot = $appointment->timeSlot();
                        $appointmentTime = 'Hora no disponible';
                        if (strlen($timeSlot) === 12) {
                            $hour = substr($timeSlot, 8, 2);
                            $minute = substr($timeSlot, 10, 2);
                            $appointmentTime = $hour . ':' . $minute;
                        }

                        // Obtener procedimientos
                        $procedures = [];
                        if (!empty($appointment->cupData)) {
                            foreach ($appointment->cupData as $cup) {
                                $procedures[] = $cup['name'] ?? 'Procedimiento sin nombre';
                            }
                        }
                        $proceduresText = !empty($procedures) ? implode(' y ', $procedures) : 'Consulta general';

                        // Obtener dirección de la clínica
                        $clinicAddress = 'Calle 34 No 38-47 Barzal'; // Dirección por defecto

                        if (!empty($appointment->cupData)) {
                            foreach ($appointment->cupData as $cup) {
                                if (!empty($cup['address'])) {
                                    $clinicAddress = $cup['address'];
                                    break;
                                }
                            }
                        }

                        // Parsear y normalizar el número de teléfono
                        $originalPhone = $appointment->patientPhone();
                        $parsedPhone = $this->parseColombianPhone($originalPhone);

                        // Si no se puede parsear el número, usar el de test
                        $finalPhone = '+573103343616';//$parsedPhone ?: '+573103343616';

                        if (!$parsedPhone) {
                            Log::warning('Using test phone due to invalid patient phone', [
                                'appointment_id' => $appointment->id(),
                                'original_phone' => $originalPhone,
                                'test_phone' => $finalPhone
                            ]);
                        }

                        // Preparar datos para el flujo de Bird
                        $appointmentData = [
                            'appointment_id' => (int)$appointment->id(),
                            'phone' => $finalPhone,
                            'patient_name' => $appointment->patientName(),
                            'clinic_name' => $subaccount->name(),
                            'clinic_address' => $clinicAddress,
                            'appointment_date' => $appointmentDate,
                            'appointment_time' => $appointmentTime,
                            'procedures' => $proceduresText
                        ];

                        Log::info('Prepared appointment data for Bird flow', [
                            'appointment_id' => $appointment->id(),
                            'original_phone' => $originalPhone,
                            'parsed_phone' => $parsedPhone,
                            'final_phone' => $finalPhone,
                            'appointment_date' => $appointmentDate,
                            'appointment_time' => $appointmentTime,
                            'patient_name' => $appointment->patientName(),
                            'procedures' => $proceduresText
                        ]);

                        // Enviar flujo de confirmación de cita
                        $success = $whatsappService->sendAppointmentConfirmationFlow($appointmentData);

                        if ($success) {
                            $totalMessagesSent++;
                            Log::info('Sent Bird appointment confirmation flow', [
                                'appointment_id' => $appointment->id(),
                                'phone' => $finalPhone,
                                'original_phone' => $originalPhone,
                                'parsed_phone' => $parsedPhone,
                                'center' => $subaccount->key()
                            ]);
                        }

                        // Solo procesar una cita por centro para pruebas
                        break;
                    } catch (\Exception $e) {
                        Log::error('Failed to send Bird appointment confirmation flow', [
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

    /**
     * Parsea y normaliza números de teléfono colombianos
     */
    private function parseColombianPhone(string $phoneString): ?string
    {
        if (empty($phoneString) || $phoneString === 'null' || $phoneString === 'NO TIENE') {
            return null;
        }

        // Limpiar espacios y caracteres especiales excepto +, -, /
        $cleaned = preg_replace('/[^\d+\-\/]/', '', $phoneString);

        // Dividir por separadores comunes
        $numbers = preg_split('/[\-\/]/', $cleaned);

        foreach ($numbers as $number) {
            // Limpiar el número
            $number = preg_replace('/[^\d]/', '', $number);

            // Si tiene indicativo +57, extraer solo los 10 dígitos
            if (strpos($cleaned, '+57') !== false && strlen($number) >= 12) {
                $mobile = substr($number, -10);
                if (strlen($mobile) === 10 && preg_match('/^3\d{9}$/', $mobile)) {
                    return '+57' . $mobile;
                }
            }

            // Si tiene exactamente 10 dígitos y empieza con 3 (móvil colombiano)
            if (strlen($number) === 10 && preg_match('/^3\d{9}$/', $number)) {
                return '+57' . $number;
            }

            // Si tiene 12 dígitos y empieza con 57 (sin el +)
            if (strlen($number) === 12 && preg_match('/^57(\d{10})$/', $number, $matches)) {
                return '+57' . $matches[1];
            }
        }

        Log::warning('Could not parse Colombian phone number', [
            'original_phone' => $phoneString,
            'cleaned' => $cleaned
        ]);

        return null;
    }
}
