<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Commands;

use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\ConfirmationChannelType;

final class CancelAppointmentCommand
{
    public function __construct(
        public readonly string $appointmentId,
        public readonly string $centerKey,
        public readonly string $reason,
        public readonly ?string $confirmationChannelId = null,
        public readonly ?ConfirmationChannelType $confirmationChannelType = null
    ) {}

    public function appointmentId(): string
    {
        return $this->appointmentId;
    }

    public function centerKey(): string
    {
        return $this->centerKey;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
