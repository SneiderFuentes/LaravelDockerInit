<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Application\Queries\GetAppointmentQuery;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDTO;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentNotFoundException;

final class GetAppointmentHandler
{
    public function __construct(private AppointmentRepositoryInterface $repository) {}

    public function handle(GetAppointmentQuery $query): AppointmentDTO
    {
        $appointment = $this->repository->findById($query->id, $query->centerKey);
        if (!$appointment) {
            throw AppointmentNotFoundException::withId($query->id, $query->centerKey);
        }
        return AppointmentDTO::fromDomain($appointment);
    }
}
