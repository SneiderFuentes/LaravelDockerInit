<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Events;

use Core\Shared\Domain\Bus\Event\DomainEvent;

final class CallInitiated extends DomainEvent
{
    public function __construct(
        private string $id,
        private string $appointmentId,
        private string $patientId,
        private string $phoneNumber,
        private string $callType,
        private string $callId,
        private ?string $flowId,
        private ?string $eventId = null,
        private ?string $occurredOn = null
    ) {
        parent::__construct($id, $eventId, $occurredOn);
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self(
            $aggregateId,
            $body['appointment_id'],
            $body['patient_id'],
            $body['phone_number'],
            $body['call_type'],
            $body['call_id'],
            $body['flow_id'] ?? null,
            $eventId,
            $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'communication.call.initiated';
    }

    public function toPrimitives(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'patient_id' => $this->patientId,
            'phone_number' => $this->phoneNumber,
            'call_type' => $this->callType,
            'call_id' => $this->callId,
            'flow_id' => $this->flowId
        ];
    }

    public function appointmentId(): string
    {
        return $this->appointmentId;
    }

    public function patientId(): string
    {
        return $this->patientId;
    }

    public function phoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function callType(): string
    {
        return $this->callType;
    }

    public function callId(): string
    {
        return $this->callId;
    }

    public function flowId(): ?string
    {
        return $this->flowId;
    }
}
