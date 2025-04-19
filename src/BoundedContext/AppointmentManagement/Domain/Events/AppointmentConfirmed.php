<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Events;

final class AppointmentConfirmed
{
    public function __construct(
        private string $appointmentId,
        private string $centerKey,
        private string $patientPhone,
        private string $scheduledAt
    ) {}

    public function appointmentId(): string
    {
        return $this->appointmentId;
    }

    public function centerKey(): string
    {
        return $this->centerKey;
    }

    public function patientPhone(): string
    {
        return $this->patientPhone;
    }

    public function scheduledAt(): string
    {
        return $this->scheduledAt;
    }
}
