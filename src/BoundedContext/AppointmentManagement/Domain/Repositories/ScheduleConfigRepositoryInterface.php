<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Repositories;

interface ScheduleConfigRepositoryInterface
{
    public function findByScheduleId($agendaId, ?string $doctorDocument = null): ?array;
    public function getAppointmentDuration($agendaId, ?string $doctorDocument): int;
}
