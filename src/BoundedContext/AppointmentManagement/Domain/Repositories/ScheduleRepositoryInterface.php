<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Repositories;

interface ScheduleRepositoryInterface
{
    /**
     * @param string $doctorId
     * @param int $duracion
     * @param array|null $horariosEspecificos
     * @return array
     */
    public function findAvailableSlots(string $doctorId, int $duracion, ?array $horariosEspecificos = null): array;

    public function findFutureWorkingDaysByDoctors(array $doctorDocuments): array;

    public function findByScheduleId($scheduleId, ?string $type = null): ?array;

    public function deleteWorkingDayException(int $agendaId, string $doctorDocument, string $date): bool;

    public function findWorkingDayException(int $agendaId, string $doctorDocument, string $date): ?array;

    public function updateWorkingDayExceptionDate(int $agendaId, string $doctorDocument, string $currentDate, string $newDate): bool;
}
