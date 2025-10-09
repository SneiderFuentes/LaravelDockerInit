<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetUpcomingAppointmentsByPatientForSpecificAgendaHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPatientAppointmentsForSpecificAgenda implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        private string $patientId,
        private string $centerKey,
        private string $appointmentDate,
        private int $agendaId,
        private string $doctorDocument,
        private ?string $previousDate = null
    ) {}

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPatientAppointmentsForSpecificAgenda job failed completely', [
            'patient_id' => $this->patientId,
            'center_key' => $this->centerKey,
            'appointment_date' => $this->appointmentDate,
            'agenda_id' => $this->agendaId,
            'doctor_document' => $this->doctorDocument,
            'previous_date' => $this->previousDate,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    public function handle(GetUpcomingAppointmentsByPatientForSpecificAgendaHandler $handler): void
    {
        $appointments = $handler->handle(
            $this->patientId,
            $this->appointmentDate,
            $this->agendaId,
            $this->doctorDocument
        );

        if (empty($appointments)) {
            return;
        }

        $this->processPatientAppointments($appointments);
    }

    private function processPatientAppointments(array $appointments): void
    {
        $firstAppointment = $appointments[0];
        $patientName = $firstAppointment['patient_name'] ?? 'Paciente sin nombre';
        $originalPhone = $firstAppointment['patient_phone'] ?? '';

        $parsedPhone = $this->parseColombianPhone($originalPhone);

        if (!$parsedPhone) {
            return;
        }

        $proceduresList = $this->extractProceduresList($appointments);
        $clinicAddress = $this->extractClinicAddress($appointments);

        $consolidatedData = [
            'appointment_id' => $appointments[0]['id'],
            'phone' => $parsedPhone,
            'patient_id' => $this->patientId,
            'patient_name' => $patientName,
            'appointment_date_new' => $appointments[0]['date'],
            'appointment_time_new' => $appointments[0]['time_slot'],
            'appointment_date_cancel' => $this->previousDate,
            'appointment_time_cancel' => $appointments[0]['time_slot'],
            'clinic_name' => 'Neuro Electro Diagnostico del llano',
            'clinic_address' => $clinicAddress,
            'procedures' => $proceduresList,
            'total_appointments' => count($appointments),
        ];

        Log::info('Consolidated data for specific agenda', [
            'consolidated_data' => $consolidatedData,
            'previous_date' => $this->previousDate
        ]);

        \Core\Jobs\SendWhatsappMessage::dispatch($consolidatedData, $this->centerKey)
            ->delay(now()->addSeconds(2));
    }

    private function parseColombianPhone(string $phoneString): ?string
    {
        if (empty($phoneString) || $phoneString === 'null' || $phoneString === 'NO TIENE') {
            return null;
        }

        $cleaned = preg_replace('/[^\d+\-\/]/', '', $phoneString);
        $numbers = preg_split('/[\-\/]/', $cleaned);

        foreach ($numbers as $number) {
            $number = preg_replace('/[^\d]/', '', $number);

            if (strpos($cleaned, '+57') !== false && strlen($number) >= 12) {
                $mobile = substr($number, -10);
                if (strlen($mobile) === 10 && preg_match('/^3\d{9}$/', $mobile)) {
                    return '+57' . $mobile;
                }
            }

            if (strlen($number) === 10 && preg_match('/^3\d{9}$/', $number)) {
                return '+57' . $number;
            }

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

        $uniqueProcedures = array_unique($procedures);

        if (empty($uniqueProcedures)) {
            return "No hay procedimientos asignados";
        }

        $proceduresList = "*Procedimientos a realizar:* ";
        foreach ($uniqueProcedures as $index => $procedure) {
            $proceduresList .= ($index + 1) . ". " . $procedure . " • ";
        }
        $proceduresList = rtrim($proceduresList, " • ");

        return trim($proceduresList);
    }

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

        return "Dirección no disponible";
    }
}

