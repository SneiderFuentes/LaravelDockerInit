<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Commands;

final class CancelAppointmentCommand
{
    public function __construct(
        private string $appointmentId,
        private string $centerKey,
        private ?string $reason = null
    ) {
        if (empty($appointmentId)) {
            throw new \InvalidArgumentException('Appointment id cannot be empty');
        }

        if (empty($centerKey)) {
            throw new \InvalidArgumentException('Center key cannot be empty');
        }
    }

    public function appointmentId(): string
    {
        return $this->appointmentId;
    }

    public function centerKey(): string
    {
        return $this->centerKey;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}
