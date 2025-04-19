<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Events;

final class AppointmentCancelled
{
    public function __construct(
        private string $appointmentId,
        private string $centerKey,
        private string $patientPhone,
        private string $scheduledAt,
        private ?string $reason = null
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

    public function reason(): ?string
    {
        return $this->reason;
    }
}
