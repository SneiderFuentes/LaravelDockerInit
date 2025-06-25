<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Services;

use Core\BoundedContext\AppointmentManagement\Domain\Entities\Appointment;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\ScheduleConfigRepository;
use Carbon\Carbon;

class ConsecutiveAppointmentService
{
    public function __construct(private ScheduleConfigRepository $scheduleConfigRepository) {}

    /**
     * Finds the entire block of consecutive appointments to which the main appointment belongs.
     *
     * @param Appointment $mainAppointment The appointment to find the block for.
     * @param Appointment[] $allAppointmentsForDay A sorted list of all appointments for that day.
     * @return Appointment[]
     */
    public function findConsecutiveBlock(Appointment $mainAppointment, array $allAppointmentsForDay): array
    {
        $mainIndex = -1;
        foreach ($allAppointmentsForDay as $index => $apt) {
            if ($apt->id() === $mainAppointment->id()) {
                $mainIndex = $index;
                break;
            }
        }

        if ($mainIndex === -1) {
            return [$mainAppointment]; // Should not happen if list is correct, but as a safeguard.
        }

        $consecutiveBlock = [$mainAppointment];

        // Search backwards
        for ($i = $mainIndex - 1; $i >= 0; $i--) {
            if ($this->areAppointmentsConsecutive($allAppointmentsForDay[$i], $consecutiveBlock[0])) {
                array_unshift($consecutiveBlock, $allAppointmentsForDay[$i]);
            } else {
                break;
            }
        }

        // Search forwards
        for ($i = $mainIndex + 1; $i < count($allAppointmentsForDay); $i++) {
            if ($this->areAppointmentsConsecutive(end($consecutiveBlock), $allAppointmentsForDay[$i])) {
                $consecutiveBlock[] = $allAppointmentsForDay[$i];
            } else {
                break;
            }
        }

        return $consecutiveBlock;
    }

    /**
     * Checks if two appointments are consecutive.
     *
     * @param Appointment $first
     * @param Appointment $second
     * @return bool
     */
    private function areAppointmentsConsecutive(Appointment $first, Appointment $second): bool
    {
        $firstTime = Carbon::createFromFormat('YmdHi', $first->timeSlot());
        $secondTime = Carbon::createFromFormat('YmdHi', $second->timeSlot());

        $duration = $this->scheduleConfigRepository->getAppointmentDuration($first->agendaId());
        $expectedNextTime = $firstTime->copy()->addMinutes($duration);

        return $first->doctorId() === $second->doctorId() &&
            $firstTime->format('Y-m-d') === $secondTime->format('Y-m-d') &&
            $secondTime->format('H:i') === $expectedNextTime->format('H:i');
    }
}
