<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDTO;
use Core\BoundedContext\AppointmentManagement\Application\Queries\ListPendingAppointmentsQuery;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;

final class ListPendingAppointmentsHandler
{
    public function __construct(
        private AppointmentRepositoryInterface $repository
    ) {}

    /**
     * @return AppointmentDTO[]
     */
    public function handle(ListPendingAppointmentsQuery $query): array
    {
        // Si no se especifica fecha, buscamos las citas del día siguiente
        if ($query->startDate === null) {
            $query->startDate = new \DateTime('tomorrow');
            $query->endDate = (new \DateTime('tomorrow'))->setTime(23, 59, 59);
        }

        // Si no se especifica fecha fin, usamos el mismo día de inicio hasta el final del día
        if ($query->endDate === null) {
            $query->endDate = (clone $query->startDate)->setTime(23, 59, 59);
        }

        $appointments = $this->repository->findPendingInDateRange(
            $query->centerKey,
            $query->startDate,
            $query->endDate
        );

        return array_map(
            fn($appointment) => AppointmentDTO::fromDomain($appointment),
            $appointments
        );
    }
}
