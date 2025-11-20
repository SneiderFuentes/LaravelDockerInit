<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Commands;

use DateTime;

final class CreateAppointmentCommand
{
    public function __construct(
        public readonly string $patientId,
        public readonly string $doctorId,
        public readonly int $agendaId,
        public readonly string $date,
        public readonly string $time,
        public readonly array $cups,
        public readonly int $espacios = 1,
        public readonly bool $is_contrasted = false,
        public readonly bool $is_sedated = false
    ) {}
}
