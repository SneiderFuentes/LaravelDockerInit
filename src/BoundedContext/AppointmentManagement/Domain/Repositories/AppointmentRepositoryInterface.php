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
        int $agendaId,
        bool $is_contrasted,
        bool $is_sedated = false
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
    public function findByPatientAndDate(int|string $patientId, string $date, ?int $agendaId = null, ?string $doctorDocument = null): array;

    /**
     * Obtiene IDs únicos de pacientes con citas PENDIENTES en el rango de fechas
     * (no canceladas y no confirmadas)
     * @return array
     */
    public function findUniquePendingPatientDocumentsInDateRange(
        string $centerKey,
        DateTime $startDate,
        DateTime $endDate
    ): array;

    /**
     * Obtiene citas PENDIENTES de un paciente para una fecha específica
     * (no canceladas y no confirmadas)
     * @return array
     */
    public function findPendingAppointmentsByPatientAndDate(int|string $patientId, string $date): array;

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

    /**
     * Find unique patient documents (cedulas) that have appointments in a given date range
     *
     * @param string $centerKey
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param int|null $agendaId
     * @param string|null $doctorDocument
     * @return array Array of unique patient documents with appointment data
     */
    public function findUniquePatientDocumentsInDateRange(
        string $centerKey,
        DateTime $startDate,
        DateTime $endDate,
        ?int $agendaId = null,
        ?string $doctorDocument = null
    ): array;

    /**
     * Find appointments by date range and entity
     *
     * @param string $centerKey
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param string $entity
     * @return array
     */
    public function findByDateAndEntity(
        string $centerKey,
        DateTime $startDate,
        DateTime $endDate,
        string $entity
    ): array;

    public function sumQuantitiesByAppointmentIdsAndCups(
        array $appointmentIds,
        array $cupCodes,
        string $centerKey
    ): int;

    /**
     * Cancel all appointments for a specific agenda, doctor and date
     */
    public function cancelAppointmentsByAgendaAndDate(int $agendaId, string $doctorDocument, string $date): int;

    /**
     * Update appointments date for a specific agenda, doctor and date
     */
    public function updateAppointmentsDate(int $agendaId, string $doctorDocument, string $currentDate, string $newDate): int;

    /**
     * Find the last doctor who attended a patient for specific CUPS codes (consultation)
     * Returns the doctor document number or null if no previous appointment found
     *
     * @param string $patientId
     * @param array $cupCodes Array of CUPS codes to search for (e.g., ['890374', '890274'])
     * @return string|null Doctor document number
     */
    public function findLastDoctorForPatientByCups(string $patientId, array $cupCodes): ?string;
}
