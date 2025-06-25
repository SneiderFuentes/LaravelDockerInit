<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Commands;

class GetAvailableSlotsByCupCommand
{
    public function __construct(
        public array $procedures,
        public int $espacios
    ) {}
}
