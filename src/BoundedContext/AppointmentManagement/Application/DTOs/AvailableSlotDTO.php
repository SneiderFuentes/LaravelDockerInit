<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\DTOs;

class AvailableSlotDTO
{
    public function __construct(
        public int $agendaId,
        public int|string $doctorId,
        public string $doctorName,
        public string $fecha,
        public string $hora,
        public int $duracion
    ) {}
}
