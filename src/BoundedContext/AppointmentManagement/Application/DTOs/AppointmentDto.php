<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\DTOs;

use Core\BoundedContext\AppointmentManagement\Domain\Entities\Appointment;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\ConfirmationChannelType;
use DateTime;

final class AppointmentDTO
{
    private ?string $cupAddress = null;
    private ?string $cupPreparation = null;
    private ?array $doctorData = null;
    private ?array $cupData = null;

    private function __construct(
        public readonly string $id,
        public readonly string $patientId,
        public readonly string $patientName,
        public readonly string $patientPhone,
        public readonly string $doctorId,
        public readonly DateTime $date,
        public readonly string $timeSlot,
        public readonly string $entity,
        public readonly int $agendaId,
        public readonly AppointmentStatus $status,
        public readonly ?DateTime $confirmationDate,
        public readonly ?DateTime $cancellationDate,
        public readonly ?string $cancellationReason,
        public readonly ?string $confirmationChannelId,
        public readonly ?ConfirmationChannelType $confirmationChannelType,
        public readonly ?int $cupId,
        ?array $doctorData = null,
        ?array $cupData = null
    ) {
        $this->doctorData = $doctorData;
        $this->cupData = $cupData;
    }

    public static function fromDomain(Appointment $appointment): self
    {
        return new self(
            $appointment->id(),
            $appointment->patientId(),
            $appointment->patientName(),
            $appointment->patientPhone(),
            $appointment->doctorId(),
            $appointment->date(),
            $appointment->timeSlot(),
            $appointment->entity(),
            $appointment->agendaId(),
            $appointment->status(),
            $appointment->confirmationDate(),
            $appointment->cancellationDate(),
            $appointment->cancellationReason(),
            $appointment->confirmationChannelId(),
            $appointment->getConfirmationChannelType(),
            $appointment->cupId(),
            $appointment->doctorData,
            $appointment->cupData
        );
    }

    /**
     * Set CUP information for the appointment
     */
    public function setCupInfo(?string $address, ?string $preparation): void
    {
        $this->cupAddress = $address;
        $this->cupPreparation = $preparation;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patientId,
            'patient_name' => $this->patientName,
            'patient_phone' => $this->patientPhone,
            'doctor_id' => $this->doctorId,
            'date' => $this->date->format('Y-m-d'),
            'time_slot' => $this->formatTimeSlot($this->timeSlot),
            'entity' => $this->entity,
            'agenda_id' => $this->agendaId,
            'status' => $this->status->value,
            'confirmation_date' => $this->confirmationDate?->format('Y-m-d H:i:s'),
            'cancellation_date' => $this->cancellationDate?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellationReason,
            'confirmation_channel_id' => $this->confirmationChannelId,
            'confirmation_channel_type' => $this->confirmationChannelType?->value,
            'cup_id' => $this->cupId,
            'doctor_data' => $this->doctorData,
            'cup_data' => $this->cupData,
        ];
    }

    private function formatTimeSlot(string $timeSlot): ?string
    {
        // Asumiendo formato YYYYMMDDHHMM
        if (strlen($timeSlot) === 12) {
            $hour = substr($timeSlot, 8, 2);
            $minute = substr($timeSlot, 10, 2);
            return $hour . ':' . $minute;
        }
        return $timeSlot;
    }
}
