<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Commands;

use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\ConfirmationChannelType;

final class ConfirmAppointmentCommand
{
    public function __construct(
        public readonly string $appointmentId,
        public readonly string $centerKey,
        public readonly ?string $confirmationChannelId = null,
        public readonly ?ConfirmationChannelType $confirmationChannelType = null
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
}
