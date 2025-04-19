<?php

namespace Core\BoundedContext\CommunicationManagement\Application\InitiateCall;

final class InitiateCallCommand
{
    public function __construct(
        private readonly string $appointmentId,
        private readonly string $patientId,
        private readonly string $phoneNumber,
        private readonly string $callType,
        private readonly ?string $flowId = null
    ) {}

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

    public function flowId(): ?string
    {
        return $this->flowId;
    }
}
