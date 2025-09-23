<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetUpcomingAppointmentsByPatientHandler;
use Core\BoundedContext\SubaccountManagement\Domain\Entities\Subaccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessPatientAppointments implements ShouldQueue
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
        private string $patientId,
        private string $centerKey,
        private string $appointmentDate,
    ) {}

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPatientAppointments job failed completely', [
            'patient_id' => $this->patientId,
            'center_key' => $this->centerKey,
            'appointment_date' => $this->appointmentDate,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(GetUpcomingAppointmentsByPatientHandler $handler): void
    {
            // Obtener las citas del paciente usando el handler
            $appointments = $handler->handle($this->patientId, $this->appointmentDate);

            if (empty($appointments)) {
                return;
            }

            // Procesar las citas del paciente
            $this->processPatientAppointments($appointments);

    }

    /**
     * Procesar las citas del paciente y enviar mensaje consolidado
     */
    private function processPatientAppointments(array $appointments): void
    {
        // Obtener datos del primer paciente (todos deberían ser del mismo paciente)
        $firstAppointment = $appointments[0];
        $patientName = $firstAppointment['patient_name'] ?? 'Paciente sin nombre';
        $originalPhone = $firstAppointment['patient_phone'] ?? '';

        // Parsear y validar teléfono
        $parsedPhone = $this->parseColombianPhone($originalPhone);

        if (!$parsedPhone) {
            return;
        }

        // Preparar datos consolidados para el mensaje
        $consolidatedData = [
            'appointment_id' => $appointments[0]['id'],
            'phone' => $parsedPhone,
            'patient_id' => $this->patientId,
            'patient_name' => $patientName,
            'appointment_date' => $this->appointmentDate,
            'appointment_time' => $appointments[0]['time_slot'],
            'clinic_name' => 'Neuro Electro Diagnostico del llano',
            'procedures' => $appointments,
            'total_appointments' => count($appointments),
        ];

        // Despachar job para enviar mensaje de WhatsApp consolidado
        \Core\Jobs\SendWhatsappMessage::dispatch($consolidatedData, $this->centerKey)
            ->delay(now()->addSeconds(2));
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
