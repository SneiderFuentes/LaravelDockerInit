<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\DTOs;

use Core\BoundedContext\AppointmentManagement\Domain\Entities\Appointment;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use DateTime;

final class AppointmentDto
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

    public static function fromEntity(Appointment $appointment): self
    {
        return new self(
            $appointment->id(),
            $appointment->centerKey(),
            $appointment->patientId(),
            $appointment->patientName(),
            $appointment->patientPhone(),
            $appointment->scheduledAt(),
            $appointment->status(),
            $appointment->notes()
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

    public function scheduledAtFormatted(string $format = 'Y-m-d H:i'): string
    {
        return $this->scheduledAt->format($format);
    }

    public function status(): AppointmentStatus
    {
        return $this->status;
    }

    public function statusValue(): string
    {
        return $this->status->value;
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'center_key' => $this->centerKey,
            'patient_id' => $this->patientId,
            'patient_name' => $this->patientName,
            'patient_phone' => $this->patientPhone,
            'scheduled_at' => $this->scheduledAt->format('Y-m-d H:i:s'),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
        ];
    }
}
