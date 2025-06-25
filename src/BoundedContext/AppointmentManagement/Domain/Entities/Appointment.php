<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Entities;

use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\ConfirmationChannelType;
use DateTime;
use InvalidArgumentException;

final class Appointment
{
    private function __construct(
        private string $id,
        private string $patientId,
        private string $patientName,
        private string $patientPhone,
        private string $doctorId,
        private DateTime $date,
        private string $timeSlot,
        private string $entity,
        private int $agendaId,
        private AppointmentStatus $status,
        private ?DateTime $confirmationDate,
        private ?DateTime $cancellationDate,
        private ?string $cancellationReason,
        private ?string $confirmationChannelId = null,
        private ?ConfirmationChannelType $confirmationChannelType = null,
        private ?int $cupId = null
    ) {}

    public static function create(
        string $id,
        string $patientId,
        string $patientName,
        string $patientPhone,
        string $doctorId,
        DateTime $date,
        string $timeSlot,
        string $entity,
        int $agendaId,
        AppointmentStatus $status = AppointmentStatus::Pending,
        ?DateTime $confirmationDate = null,
        ?DateTime $cancellationDate = null,
        ?string $cancellationReason = null,
        ?string $confirmationChannelId = null,
        ?ConfirmationChannelType $confirmationChannelType = null,
        ?int $cupId = null
    ): self {
        if (empty($id)) {
            throw new InvalidArgumentException('Appointment id cannot be empty');
        }

        if (empty($patientId)) {
            throw new InvalidArgumentException('Patient id cannot be empty');
        }

        if (empty($patientName)) {
            throw new InvalidArgumentException('Patient name cannot be empty');
        }

        if (empty($patientPhone)) {
            throw new InvalidArgumentException('Patient phone cannot be empty');
        }

        if (empty($doctorId)) {
            throw new InvalidArgumentException('Doctor id cannot be empty');
        }

        if (empty($date)) {
            throw new InvalidArgumentException('Date cannot be empty');
        }

        if (empty($timeSlot)) {
            throw new InvalidArgumentException('Time slot cannot be empty');
        }

        if (empty($entity)) {
            throw new InvalidArgumentException('Entity cannot be empty');
        }

        if (empty($agendaId)) {
            throw new InvalidArgumentException('Agenda id cannot be empty');
        }

        return new self(
            $id,
            $patientId,
            $patientName,
            $patientPhone,
            $doctorId,
            $date,
            $timeSlot,
            $entity,
            $agendaId,
            $status,
            $confirmationDate,
            $cancellationDate,
            $cancellationReason,
            $confirmationChannelId,
            $confirmationChannelType,
            $cupId
        );
    }

    public static function createWithDetails(
        object $row,
        ?array $doctorData = null,
        ?array $cupData = null
    ): self {
        $status = AppointmentStatus::Pending;
        if ($row->confirmed == -1) {
            $status = AppointmentStatus::Confirmed;
        } elseif ($row->canceled == -1) {
            $status = AppointmentStatus::Cancelled;
        }

        $dateTime = DateTime::createFromFormat('Y-m-d', $row->date);
        if ($row->time_slot) {
            $hour = substr($row->time_slot, -4, 2);
            $minute = substr($row->time_slot, -2);
            $dateTime->setTime((int)$hour, (int)$minute);
        }

        $confirmationDate = $row->confirmation_date ? new DateTime($row->confirmation_date) : null;
        $cancellationDate = $row->cancel_date ? new DateTime($row->cancel_date) : null;
        $channelType = $row->confirmation_channel ? ConfirmationChannelType::from($row->confirmation_channel) : null;

        $instance = new self(
            (string)$row->id,
            (string)$row->patient_id,
            (string)$row->patient_name,
            (string)$row->patient_phone,
            (string)$row->doctor_document,
            $dateTime,
            $row->time_slot,
            $row->entity,
            (int)$row->agenda_id,
            $status,
            $confirmationDate,
            $cancellationDate,
            null, // cancellation_reason
            $row->confirmation_channel_id ?? null,
            $channelType,
            isset($row->cup_id) ? (int)$row->cup_id : null
        );
        // Agregar propiedades enriquecidas
        $instance->doctorData = $doctorData;
        $instance->cupData = $cupData;
        return $instance;
    }

    public function id(): string
    {
        return $this->id;
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

    public function doctorId(): string
    {
        return $this->doctorId;
    }

    public function date(): DateTime
    {
        return $this->date;
    }

    public function timeSlot(): string
    {
        return $this->timeSlot;
    }

    public function entity(): string
    {
        return $this->entity;
    }

    public function agendaId(): int
    {
        return $this->agendaId;
    }

    public function status(): AppointmentStatus
    {
        return $this->status;
    }

    public function confirmationDate(): ?DateTime
    {
        return $this->confirmationDate;
    }

    public function cancellationDate(): ?DateTime
    {
        return $this->cancellationDate;
    }

    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function confirmationChannelId(): ?string
    {
        return $this->confirmationChannelId;
    }

    public function confirmationChannelType(): ?string
    {
        return $this->confirmationChannelType?->value;
    }

    public function cupId(): ?int
    {
        return $this->cupId;
    }

    public function confirm(?string $channelId = null, ?ConfirmationChannelType $channelType = null): void
    {
        if ($this->status === AppointmentStatus::Cancelled) {
            throw new \InvalidArgumentException('Cannot confirm a cancelled appointment');
        }

        if ($this->status === AppointmentStatus::Confirmed) {
            throw new \InvalidArgumentException('Appointment is already confirmed');
        }

        $this->status = AppointmentStatus::Confirmed;
        $this->confirmationDate = new DateTime();
        $this->confirmationChannelId = $channelId;
        $this->confirmationChannelType = $channelType;
    }

    public function cancel(string $reason, ?string $channelId = null, ?ConfirmationChannelType $channelType = null): void
    {
        if ($this->status === AppointmentStatus::Cancelled) {
            throw new \InvalidArgumentException('Appointment is already cancelled');
        }

        $this->status = AppointmentStatus::Cancelled;
        $this->cancellationDate = new DateTime();
        $this->cancellationReason = $reason;
        $this->confirmationChannelId = $channelId;
        $this->confirmationChannelType = $channelType;
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

    public function getConfirmationChannelType(): ?ConfirmationChannelType
    {
        return $this->confirmationChannelType;
    }

    // Propiedades públicas para exponer los datos enriquecidos
    public ?array $doctorData = null;
    public ?array $cupData = null;
}
