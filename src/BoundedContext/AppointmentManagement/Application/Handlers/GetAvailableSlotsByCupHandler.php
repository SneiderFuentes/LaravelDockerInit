<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Application\Services\GetAvailableSlotsByCupService;

class GetAvailableSlotsByCupHandler
{
    public function __construct(private GetAvailableSlotsByCupService $service) {}

    public function handle(array $procedures, int $espacios): array
    {
        return $this->service->execute($procedures, $espacios);
    }
}
