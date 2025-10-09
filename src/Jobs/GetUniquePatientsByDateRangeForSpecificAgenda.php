<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use DateTime;

class GetUniquePatientsByDateRangeForSpecificAgenda implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        private string $centerKey,
        private DateTime $startTime,
        private DateTime $endTime,
        private int $agendaId,
        private string $doctorDocument,
        private ?string $previousDate = null
    ) {}

    public function failed(\Throwable $exception): void
    {
        Log::error('GetUniquePatientsByDateRangeForSpecificAgenda job failed completely', [
            'center_key' => $this->centerKey,
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime->format('Y-m-d H:i:s'),
            'agenda_id' => $this->agendaId,
            'doctor_document' => $this->doctorDocument,
            'previous_date' => $this->previousDate,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    public function handle(AppointmentRepositoryInterface $appointmentRepository): array
    {
        $uniquePatientIds = $appointmentRepository->findUniquePatientDocumentsInDateRange(
            $this->centerKey,
            $this->startTime,
            $this->endTime,
            $this->agendaId,
            $this->doctorDocument
        );

        $jobsDispatched = 0;
        $appointmentDate = $this->startTime->format('Y-m-d');

        foreach ($uniquePatientIds as $patientId) {
            \Core\Jobs\ProcessPatientAppointmentsForSpecificAgenda::dispatch(
                $patientId,
                $this->centerKey,
                $appointmentDate,
                $this->agendaId,
                $this->doctorDocument,
                $this->previousDate
            )->delay(now()->addSeconds($jobsDispatched * 2));
            $jobsDispatched++;
        }

        Log::info('WHATSAPP_MESSAGES_SPECIFIC_AGENDA_FLOW_SUMMARY', [
            'agenda_id' => $this->agendaId,
            'doctor_document' => $this->doctorDocument,
            'previous_date' => $this->previousDate,
            'unique_patient_ids' => $uniquePatientIds,
            'unique_patients_count' => count($uniquePatientIds),
            'appointment_query_jobs_dispatched' => $jobsDispatched
        ]);

        return [];
    }
}

