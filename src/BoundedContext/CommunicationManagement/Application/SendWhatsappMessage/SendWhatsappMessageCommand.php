<?php

namespace Core\BoundedContext\CommunicationManagement\Application\SendWhatsappMessage;

final class SendWhatsappMessageCommand
{
    public function __construct(
        private readonly string $appointmentId,
        private readonly string $patientId,
        private readonly string $phoneNumber,
        private readonly string $content,
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

    public function content(): string
    {
        return $this->content;
    }
}
