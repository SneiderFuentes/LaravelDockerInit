<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDto;
use Core\BoundedContext\AppointmentManagement\Application\Queries\ListPendingAppointmentsQuery;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;

final class ListPendingAppointmentsHandler
{
    public function __construct(
        private AppointmentRepositoryInterface $repository
    ) {}

    /**
     * @return AppointmentDto[]
     */
    public function handle(ListPendingAppointmentsQuery $query): array
    {
        $appointments = $this->repository->findPendingInDateRange(
            $query->centerKey(),
            $query->startDate(),
            $query->endDate()
        );

        return array_map(
            fn($appointment) => AppointmentDto::fromEntity($appointment),
            $appointments
        );
    }
}
