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
}
