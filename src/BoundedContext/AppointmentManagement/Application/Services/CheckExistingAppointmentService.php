<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Services;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;

class CheckExistingAppointmentService
{
    public function __construct(private AppointmentRepositoryInterface $appointmentRepository) {}

    public function execute(string $patientId, string $cupCode): bool
    {
        return $this->appointmentRepository->hasFutureAppointmentsForCup($patientId, $cupCode);
    }
}
