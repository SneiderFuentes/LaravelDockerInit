<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\ScheduleConfigRepository;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDTO;
use Carbon\Carbon;

class GetUpcomingAppointmentsByPatientForSpecificAgendaHandler
{
    public function __construct(
        private AppointmentRepositoryInterface $repository,
        private ScheduleConfigRepository $scheduleConfigRepository
    ) {}

    public function handle(string $patientId, string $fromDate, int $agendaId, string $doctorDocument): array
    {
        $appointments = $this->repository->findByPatientAndDate($patientId, $fromDate, $agendaId, $doctorDocument);
        $filteredAppointments = $this->filterConsecutiveAppointments($appointments);
        return array_map(fn($apt) => AppointmentDTO::fromDomain($apt)->toArray(), $filteredAppointments);
    }

    private function filterConsecutiveAppointments(array $appointments): array
    {
        if (empty($appointments)) {
            return [];
        }

        $filtered = [];
        $i = 0;

        while ($i < count($appointments)) {
            $filtered[] = $appointments[$i];

            $j = $i;
            while (isset($appointments[$j + 1])) {
                $currentInSequence = $appointments[$j];
                $nextInSequence = $appointments[$j + 1];

                $currentTime = Carbon::createFromFormat('YmdHi', $currentInSequence->timeSlot());
                $nextTime = Carbon::createFromFormat('YmdHi', $nextInSequence->timeSlot());
                $duration = $this->scheduleConfigRepository->getAppointmentDuration($currentInSequence->agendaId());

                $expectedNextTime = $currentTime->copy()->addMinutes($duration);

                $isConsecutive =
                    $currentInSequence->doctorId() === $nextInSequence->doctorId() &&
                    $currentTime->format('Y-m-d') === $nextTime->format('Y-m-d') &&
                    $nextTime->format('H:i') === $expectedNextTime->format('H:i');

                if ($isConsecutive) {
                    $j++;
                    $filtered[] = $appointments[$j];
                } else {
                    break;
                }
            }

            $i = $j + 1;
        }

        return $filtered;
    }
}

