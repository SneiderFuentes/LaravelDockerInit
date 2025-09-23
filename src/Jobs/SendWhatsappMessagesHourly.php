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
use DateTime;

class SendWhatsappMessagesHourly implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 180;// 3 minutos para consultas con JOINs completos

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $centerKey,
        private DateTime $startTime,
        private DateTime $endTime
    ) {}

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendWhatsappMessagesHourly job failed completely', [
            'center_key' => $this->centerKey,
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime->format('Y-m-d H:i:s'),
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(
        AppointmentRepositoryInterface $appointmentRepository,
        SendWhatsappMessageService $whatsappService,
        SubaccountRepositoryInterface $subaccountRepository
    ): void {
        try {
            Log::info('Starting hourly WhatsApp job', [
                'center_key' => $this->centerKey,
                'start_time' => $this->startTime->format('Y-m-d H:i:s'),
                'end_time' => $this->endTime->format('Y-m-d H:i:s')
            ]);

            // Obtener subcuenta
            $subaccount = $subaccountRepository->findByKey($this->centerKey);
            if (!$subaccount) {
                Log::error('Subaccount not found', ['center_key' => $this->centerKey]);
                return;
            }

            // Consultar citas para esta hora específica con timeout extendido
            $startTime = microtime(true);

            // Configurar timeout específico para consultas con JOINs
            ini_set('max_execution_time', 180); // 3 minutos

            $appointments = $appointmentRepository->findPendingInDateRange(
                $this->centerKey,
                $this->startTime,
                $this->endTime
            );

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('Hourly query completed', [
                'center_key' => $this->centerKey,
                'query_time_ms' => $queryTime,
                'appointments_found' => count($appointments)
            ]);

            $messagesSent = 0;
            $skippedAppointments = 0;

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

                    // Si no se puede parsear el número, saltar esta cita
                    if (!$parsedPhone) {
                        $skippedAppointments++;
                        Log::warning('MESSAGE NOT SENT - Invalid patient phone number', [
                            'appointment_id' => $appointment->id(),
                            'patient_name' => $appointment->patientName(),
                            'patient_phone_original' => $originalPhone,
                            'appointment_date' => $appointmentDate,
                            'appointment_time' => $appointmentTime,
                            'clinic' => $subaccount->name(),
                            'reason' => 'Unable to parse Colombian mobile number',
                            'status' => 'SKIPPED'
                        ]);
                        continue;
                    }

                    // Preparar datos para el flujo de Bird
                    $appointmentData = [
                        'appointment_id' => (int)$appointment->id(),
                        'phone' => $parsedPhone,
                        'patient_name' => $appointment->patientName(),
                        'clinic_name' => $subaccount->name(),
                        'clinic_address' => $clinicAddress,
                        'appointment_date' => $appointmentDate,
                        'appointment_time' => $appointmentTime,
                        'procedures' => $proceduresText
                    ];

                    // Despachar job para enviar mensaje de WhatsApp
                    try {
                        \Core\Jobs\SendWhatsappMessage::dispatch($appointmentData, $this->centerKey)
                            ->delay(now()->addSeconds(2)); // Delay de 2 segundos para evitar saturación

                        $messagesSent++;
                        Log::info('WhatsApp message job dispatched', [
                            'appointment_id' => $appointment->id(),
                            'patient_name' => $appointment->patientName(),
                            'patient_phone' => $parsedPhone,
                            'center_key' => $this->centerKey
                        ]);
                    } catch (\Exception $whatsappException) {
                        Log::error('Failed to dispatch WhatsApp message job', [
                            'appointment_id' => $appointment->id(),
                            'patient_name' => $appointment->patientName(),
                            'error' => $whatsappException->getMessage()
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('MESSAGE EXCEPTION - Failed to send Bird appointment confirmation flow', [
                        'appointment_id' => $appointment->id(),
                        'patient_name' => $appointment->patientName(),
                        'appointment_date' => $appointmentDate ?? 'N/A',
                        'appointment_time' => $appointmentTime ?? 'N/A',
                        'clinic' => $subaccount->name(),
                        'status' => 'EXCEPTION_ERROR',
                        'error_message' => $e->getMessage(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine()
                    ]);
                }
            }

            Log::info('Hourly job completed', [
                'center_key' => $this->centerKey,
                'hour_range' => $this->startTime->format('H:i') . ' - ' . $this->endTime->format('H:i'),
                'total_appointments' => count($appointments),
                'messages_sent' => $messagesSent,
                'skipped_appointments' => $skippedAppointments
            ]);

        } catch (\Throwable $e) {
            Log::error('Critical error in SendWhatsappMessagesHourly job', [
                'center_key' => $this->centerKey,
                'start_time' => $this->startTime->format('Y-m-d H:i:s'),
                'end_time' => $this->endTime->format('Y-m-d H:i:s'),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
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
