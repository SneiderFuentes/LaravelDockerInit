<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Repositories;

interface ScheduleConfigRepositoryInterface
{
    public function findByScheduleId($agendaId): ?array;
    public function getAppointmentDuration($agendaId): int;
}
