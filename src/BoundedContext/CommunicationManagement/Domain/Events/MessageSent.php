<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Events;

use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageType;
use Core\Shared\Domain\Bus\Event\DomainEvent;

final class MessageSent extends DomainEvent
{
    public function __construct(
        private string $id,
        private string $appointmentId,
        private string $patientId,
        private string $phoneNumber,
        private string $messageType,
        private string $content,
        private string $messageId,
        private ?string $subaccountKey = null,
        private ?string $eventId = null,
        private ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
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
            $body['message_type'],
            $body['content'],
            $body['message_id'],
            $body['subaccount_key'] ?? null,
            $eventId,
            $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'message.sent';
    }

    public function toPrimitives(): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointmentId,
            'patient_id' => $this->patientId,
            'phone_number' => $this->phoneNumber,
            'message_type' => $this->messageType,
            'content' => $this->content,
            'message_id' => $this->messageId,
            'subaccount_key' => $this->subaccountKey
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

    public function messageType(): string
    {
        return $this->messageType;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function messageId(): string
    {
        return $this->messageId;
    }

    public function subaccountKey(): ?string
    {
        return $this->subaccountKey;
    }
}
