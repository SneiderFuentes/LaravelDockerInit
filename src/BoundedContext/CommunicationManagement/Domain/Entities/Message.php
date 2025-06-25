<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Entities;

use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageStatus;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageType;
use DateTime;

class Message
{
    public function __construct(
        private string $id,
        private string $appointmentId,
        private string $patientId,
        private string $phoneNumber,
        private string $content,
        private MessageType $type,
        private MessageStatus $status,
        private ?string $messageId = null,
        private ?string $messageResponse = null,
        private ?string $subaccountKey = null,
        private ?DateTime $sentAt = null,
        private ?DateTime $deliveredAt = null,
        private ?DateTime $readAt = null,
        private ?DateTime $createdAt = null,
        private ?DateTime $updatedAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAppointmentId(): string
    {
        return $this->appointmentId;
    }

    public function getPatientId(): string
    {
        return $this->patientId;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): MessageType
    {
        return $this->type;
    }

    public function getStatus(): MessageStatus
    {
        return $this->status;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getMessageResponse(): ?string
    {
        return $this->messageResponse;
    }

    public function getSubaccountKey(): ?string
    {
        return $this->subaccountKey;
    }

    public function getSentAt(): ?DateTime
    {
        return $this->sentAt;
    }

    public function getDeliveredAt(): ?DateTime
    {
        return $this->deliveredAt;
    }

    public function getReadAt(): ?DateTime
    {
        return $this->readAt;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function markAsSent(string $messageId, DateTime $sentAt): self
    {
        $message = clone $this;
        $message->messageId = $messageId;
        $message->sentAt = $sentAt;
        $message->status = MessageStatus::sent();
        $message->updatedAt = new DateTime();

        return $message;
    }

    public function markAsDelivered(DateTime $deliveredAt): self
    {
        $message = clone $this;
        $message->deliveredAt = $deliveredAt;
        $message->status = MessageStatus::delivered();
        $message->updatedAt = new DateTime();

        return $message;
    }

    public function markAsRead(DateTime $readAt): self
    {
        $message = clone $this;
        $message->readAt = $readAt;
        $message->status = MessageStatus::read();
        $message->updatedAt = new DateTime();

        return $message;
    }

    public function markAsFailed(): self
    {
        $message = clone $this;
        $message->status = MessageStatus::failed();
        $message->updatedAt = new DateTime();

        return $message;
    }

    public function setResponse(string $response): self
    {
        $message = clone $this;
        $message->messageResponse = $response;
        $message->updatedAt = new DateTime();

        return $message;
    }
}
