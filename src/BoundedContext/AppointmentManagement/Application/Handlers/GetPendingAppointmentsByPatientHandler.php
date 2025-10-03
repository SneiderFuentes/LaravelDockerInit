<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\ScheduleConfigRepository;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDTO;
use Carbon\Carbon;

class GetPendingAppointmentsByPatientHandler
{
    public function __construct(
        private AppointmentRepositoryInterface $repository,
        private ScheduleConfigRepository $scheduleConfigRepository
    ) {}

    /**
     * @param string $patientId
     * @param string $fromDate (YYYY-MM-DD)
     * @return array
     */
    public function handle(string $patientId, string $fromDate): array
    {
        $appointments = $this->repository->findPendingAppointmentsByPatientAndDate($patientId, $fromDate);
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
            // Agrega la cita actual, que es la primera de un posible grupo consecutivo.
            $filtered[] = $appointments[$i];

            $j = $i;
            // Ahora, avanza 'j' mientras las siguientes citas sean consecutivas a la anterior.
            while (isset($appointments[$j + 1])) {
                $currentInSequence = $appointments[$j];
                $nextInSequence = $appointments[$j + 1];

                $currentTime = Carbon::createFromFormat('YmdHi', $currentInSequence->timeSlot());
                $nextTime = Carbon::createFromFormat('YmdHi', $nextInSequence->timeSlot());
                $duration = $this->scheduleConfigRepository->getAppointmentDuration($currentInSequence->agendaId());

                // Calcular la hora esperada de la siguiente cita
                $expectedNextTime = $currentTime->copy()->addMinutes($duration);

                $isConsecutive =
                    $currentInSequence->doctorId() === $nextInSequence->doctorId() &&
                    $currentTime->format('Y-m-d') === $nextTime->format('Y-m-d') &&
                    $nextTime->format('H:i') === $expectedNextTime->format('H:i');

                if ($isConsecutive) {
                    // La siguiente cita es consecutiva, la agregamos al grupo
                    $j++;
                    $filtered[] = $appointments[$j];
                } else {
                    // La secuencia se rompió.
                    break;
                }
            }

            // Saltar el índice del bucle exterior al final de la secuencia encontrada.
            $i = $j + 1;
        }

        return $filtered;
    }
}
