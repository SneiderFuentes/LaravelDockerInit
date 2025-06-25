<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Events;

final class AppointmentConfirmed
{
    public function __construct(
        public readonly string $appointmentId,
        public readonly string $patientPhone,
        public readonly string $scheduledDateTime
    ) {}

    public function appointmentId(): string
    {
        return $this->appointmentId;
    }

    public function patientPhone(): string
    {
        return $this->patientPhone;
    }

    public function scheduledDateTime(): string
    {
        return $this->scheduledDateTime;
    }
}
