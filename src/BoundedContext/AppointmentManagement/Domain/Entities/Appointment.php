<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Entities;

use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use DateTime;
use InvalidArgumentException;

final class Appointment
{
    private function __construct(
        private string $id,
        private string $centerKey,
        private string $patientId,
        private string $patientName,
        private string $patientPhone,
        private DateTime $scheduledAt,
        private AppointmentStatus $status,
        private ?string $notes
    ) {}

    public static function create(
        string $id,
        string $centerKey,
        string $patientId,
        string $patientName,
        string $patientPhone,
        DateTime $scheduledAt,
        AppointmentStatus $status = AppointmentStatus::Pending,
        ?string $notes = null
    ): self {
        if (empty($id)) {
            throw new InvalidArgumentException('Appointment id cannot be empty');
        }

        if (empty($centerKey)) {
            throw new InvalidArgumentException('Center key cannot be empty');
        }

        if (empty($patientId)) {
            throw new InvalidArgumentException('Patient id cannot be empty');
        }

        return new self(
            $id,
            $centerKey,
            $patientId,
            $patientName,
            $patientPhone,
            $scheduledAt,
            $status,
            $notes
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function centerKey(): string
    {
        return $this->centerKey;
    }

    public function patientId(): string
    {
        return $this->patientId;
    }

    public function patientName(): string
    {
        return $this->patientName;
    }

    public function patientPhone(): string
    {
        return $this->patientPhone;
    }

    public function scheduledAt(): DateTime
    {
        return $this->scheduledAt;
    }

    public function status(): AppointmentStatus
    {
        return $this->status;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function confirm(): self
    {
        if (!$this->status->isPending()) {
            throw new InvalidArgumentException("Cannot confirm appointment with status {$this->status->value}");
        }

        $clone = clone $this;
        $clone->status = AppointmentStatus::Confirmed;

        return $clone;
    }

    public function cancel(): self
    {
        if (!$this->status->isPending()) {
            throw new InvalidArgumentException("Cannot cancel appointment with status {$this->status->value}");
        }

        $clone = clone $this;
        $clone->status = AppointmentStatus::Cancelled;

        return $clone;
    }

    public function markAsNoShow(): self
    {
        if (!$this->status->isPending()) {
            throw new InvalidArgumentException("Cannot mark as no-show appointment with status {$this->status->value}");
        }

        $clone = clone $this;
        $clone->status = AppointmentStatus::NoShow;

        return $clone;
    }

    public function complete(): self
    {
        if (!$this->status->isConfirmed()) {
            throw new InvalidArgumentException("Cannot complete appointment with status {$this->status->value}");
        }

        $clone = clone $this;
        $clone->status = AppointmentStatus::Completed;

        return $clone;
    }
}
