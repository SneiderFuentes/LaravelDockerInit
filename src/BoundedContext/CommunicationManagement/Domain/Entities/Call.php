<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Entities;

use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\CallStatus;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\CallType;
use DateTime;

class Call
{
    public function __construct(
        private string $id,
        private string $appointmentId,
        private string $patientId,
        private string $phoneNumber,
        private CallStatus $status,
        private CallType $type,
        private ?string $callId = null,
        private ?string $flowId = null,
        private ?DateTime $startTime = null,
        private ?DateTime $endTime = null,
        private ?int $duration = null,
        private ?array $responseData = null,
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

    public function getStatus(): CallStatus
    {
        return $this->status;
    }

    public function getType(): CallType
    {
        return $this->type;
    }

    public function getCallId(): ?string
    {
        return $this->callId;
    }

    public function getFlowId(): ?string
    {
        return $this->flowId;
    }

    public function getStartTime(): ?DateTime
    {
        return $this->startTime;
    }

    public function getEndTime(): ?DateTime
    {
        return $this->endTime;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function updateStatus(CallStatus $status): self
    {
        $call = clone $this;
        $call->status = $status;
        $call->updatedAt = new DateTime();

        return $call;
    }

    public function setCallId(string $callId): self
    {
        $call = clone $this;
        $call->callId = $callId;
        $call->updatedAt = new DateTime();

        return $call;
    }

    public function setStartTime(DateTime $startTime): self
    {
        $call = clone $this;
        $call->startTime = $startTime;
        $call->updatedAt = new DateTime();

        return $call;
    }

    public function setEndTime(DateTime $endTime): self
    {
        $call = clone $this;
        $call->endTime = $endTime;
        $call->updatedAt = new DateTime();

        return $call;
    }

    public function setDuration(int $duration): self
    {
        $call = clone $this;
        $call->duration = $duration;
        $call->updatedAt = new DateTime();

        return $call;
    }

    public function setResponseData(array $responseData): self
    {
        $call = clone $this;
        $call->responseData = $responseData;
        $call->updatedAt = new DateTime();

        return $call;
    }
}
