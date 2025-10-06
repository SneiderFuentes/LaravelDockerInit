<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\SubaccountManagement\Domain\Entities\Subaccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use DateTime;

class GetUniquePendingPatientsByDateRange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 120; // 2 minutos

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
        Log::error('GetUniquePendingPatientsByDateRange job failed completely', [
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
    ): array {
            $uniquePatientIds = $appointmentRepository->findUniquePendingPatientDocumentsInDateRange(
                $this->centerKey,
                $this->startTime,
                $this->endTime
            );

            // Despachar job individual para cada paciente único PENDIENTE
            $jobsDispatched = 0;
            $appointmentDate = $this->startTime->format('Y-m-d'); // Usar la fecha de inicio

            foreach ($uniquePatientIds as $patientId) {
                    \Core\Jobs\ProcessPendingPatientAppointments::dispatch(
                        $patientId,
                        $this->centerKey,
                        $appointmentDate,
                    )->delay(now()->addMinutes($jobsDispatched));
                $jobsDispatched++;
            }

            // Log final con información esencial
            Log::info('PENDING_APPOINTMENTS_FLOW_SUMMARY', [
                'unique_pending_patient_ids' => $uniquePatientIds,
                'unique_pending_patients_count' => count($uniquePatientIds),
                'pending_appointment_query_jobs_dispatched' => $jobsDispatched
            ]);

            return [];
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
