<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Application\Services\GetAvailableSlotsByCupService;

class GetAvailableSlotsByCupHandler
{
    public function __construct(private GetAvailableSlotsByCupService $service) {}

    public function handle(array $procedures, int $espacios, int $patientAge, bool $isContrasted, bool $isSedated = false, string $patientId = '', ?string $afterDate = null): array
    {
        return $this->service->execute($procedures, $espacios, $patientAge, $isContrasted, $isSedated, $patientId, $afterDate);
    }
}
