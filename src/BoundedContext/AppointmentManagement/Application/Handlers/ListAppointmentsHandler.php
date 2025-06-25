<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Application\Queries\ListAppointmentsQuery;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDTO;

final class ListAppointmentsHandler
{
    public function __construct(private AppointmentRepositoryInterface $repository) {}

    public function handle(ListAppointmentsQuery $query): array
    {
        $appointments = $this->repository->findPendingInDateRange(
            $query->centerKey,
            $query->startDate ?? new \DateTime(),
            $query->endDate ?? (new \DateTime('+1 day'))
        );
        return array_map(fn($apt) => AppointmentDTO::fromDomain($apt), $appointments);
    }
}
