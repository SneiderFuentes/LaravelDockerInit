<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetPendingAppointmentsByPatientHandler;
use Core\BoundedContext\SubaccountManagement\Domain\Entities\Subaccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessPendingPatientAppointments implements ShouldQueue
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
        Log::error('ProcessPendingPatientAppointments job failed completely', [
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
    public function handle(GetPendingAppointmentsByPatientHandler $handler): void
    {
            // Obtener las citas PENDIENTES del paciente usando el handler
            $appointments = $handler->handle($this->patientId, $this->appointmentDate);

            if (empty($appointments)) {
                return;
            }

            // Procesar las citas PENDIENTES del paciente
            $this->processPendingPatientAppointments($appointments);

    }

    /**
     * Procesar las citas PENDIENTES del paciente y realizar llamada automática
     */
    private function processPendingPatientAppointments(array $appointments): void
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

        // Extraer y formatear procedimientos
        $proceduresList = $this->extractProceduresList($appointments);

        // Extraer dirección del primer procedimiento
        $clinicAddress = $this->extractClinicAddress($appointments);

        // Preparar datos consolidados para el mensaje PENDIENTE
        $consolidatedData = [
            'from' => '+576082795066',
            'appointment_id' => $appointments[0]['id'],
            'phone' => $parsedPhone,
            'patient_id' => $this->patientId,
            'patient_name' => $patientName,
            'appointment_date' => $appointments[0]['date'],
            'appointment_time' => $appointments[0]['time_slot'],
            'clinic_name' => 'Neuro Electro Diagnostico del llano',
            'clinic_address' => $clinicAddress,
            'procedures' => $proceduresList,
            'total_appointments' => count($appointments),
            'appointment_status' => 'PENDING',
        ];
        Log::info('Consolidated pending data', [
            'consolidated_data' => $consolidatedData
        ]);

        // Despachar job para realizar llamada automática para citas PENDIENTES
        \Core\Jobs\MakePendingAppointmentCall::dispatch($consolidatedData, $this->centerKey)
            ->delay(now()->addSeconds(5));
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

    /**
     * Extrae y formatea la lista de procedimientos de las citas como texto
     */
    private function extractProceduresList(array $appointments): string
    {
        $procedures = [];

        foreach ($appointments as $appointment) {
            if (isset($appointment['cup_data']) && is_array($appointment['cup_data'])) {
                foreach ($appointment['cup_data'] as $cup) {
                    if (isset($cup['name']) && !empty($cup['name'])) {
                        $procedures[] = trim($cup['name']);
                    }
                }
            }
        }

        // Eliminar duplicados
        $uniqueProcedures = array_unique($procedures);
        Log::info('uniqueProcedures', ['uniqueProcedures' => $uniqueProcedures]);

        // Formatear como texto numerado
        if (empty($uniqueProcedures)) {
            return "No hay procedimientos asignados";
        }

        $proceduresList = "*Procedimientos a realizar:* ";
        foreach ($uniqueProcedures as $index => $procedure) {
            $proceduresList .= ($index + 1) . ". " . $procedure . " • ";
        }
        // Remover el último separador
        $proceduresList = rtrim($proceduresList, " • ");

        return trim($proceduresList);
    }

    /**
     * Extrae la dirección de la clínica del primer procedimiento (CUP)
     */
    private function extractClinicAddress(array $appointments): string
    {
        foreach ($appointments as $appointment) {
            if (isset($appointment['cup_data']) && is_array($appointment['cup_data'])) {
                foreach ($appointment['cup_data'] as $cup) {
                    if (isset($cup['address']) && !empty($cup['address'])) {
                        return trim($cup['address']);
                    }
                }
            }
        }

        // Si no se encuentra dirección en los CUPS, usar dirección por defecto
        return "Dirección no disponible";
    }
}
