<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Repositories;

use Core\BoundedContext\AppointmentManagement\Domain\Entities\Appointment;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use DateTime;

interface AppointmentRepositoryInterface
{
    /**
     * Find an appointment by its id in a specific center
     */
    public function findById(string $id, string $centerKey): ?Appointment;

    /**
     * Save an appointment (create or update)
     */
    public function save(Appointment $appointment): void;

    /**
     * Find all pending appointments for a given date range
     *
     * @return Appointment[]
     */
    public function findPendingInDateRange(
        string $centerKey,
        DateTime $startDate,
        DateTime $endDate
    ): array;

    /**
     * Find appointments by status
     *
     * @return Appointment[]
     */
    public function findByStatus(
        string $centerKey,
        AppointmentStatus $status,
        ?DateTime $date = null
    ): array;

    /**
     * Count appointments by status
     */
    public function countByStatus(string $centerKey, AppointmentStatus $status): int;

    /**
     * Find scheduled appointments that need reminders
     *
     * @return array
     */
    public function findScheduledAppointments(): array;

    /**
     * Find unconfirmed appointments that need calls
     *
     * @return array
     */
    public function findUnconfirmedAppointments(): array;
}
