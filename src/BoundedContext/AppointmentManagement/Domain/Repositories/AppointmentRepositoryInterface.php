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
     * Create a new appointment
     */
    public function create(
        string $doctorId,
        string $patientId,
        DateTime $date,
        string $timeSlot,
        string $entity,
        int $agendaId
    ): Appointment;

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

    /**
     * Devuelve las citas de una agenda y fecha específica
     * @return array
     */
    public function findByAgendaAndDate(int|string $agendaId, string $date): array;

    /**
     * Devuelve las citas de un paciente y fecha específica
     * @return array
     */
    public function findByPatientAndDate(int|string $patientId, string $date): array;

    /**
     * Encuentra un bloque de citas consecutivas a partir de una cita principal y una lista de candidatas.
     *
     * @param Appointment $mainAppointment La cita de referencia.
     * @param Appointment[] $candidateAppointments La lista de citas a evaluar.
     * @return Appointment[] Un array con el bloque de citas consecutivas.
     */
    public function findConsecutiveAppointments(Appointment $mainAppointment, array $candidateAppointments): array;

    /**
     * Verifica si un paciente tiene citas futuras no canceladas para un CUPS específico.
     *
     * @param string $patientId
     * @param string $cupCode
     * @return bool
     */
    public function hasFutureAppointmentsForCup(string $patientId, string $cupCode): bool;

    /**
     * Verifica si ya existe una cita para la misma agenda, fecha y hora
     */
    public function existsAppointment(int $agendaId, string $date, string $timeSlot): bool;

    /**
     * Inserta un registro en la tabla pxcita
     */
    public function createPxcita(array $data): void;
}
