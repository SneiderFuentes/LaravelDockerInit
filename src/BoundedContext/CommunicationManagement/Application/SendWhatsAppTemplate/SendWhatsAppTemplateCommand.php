<?php

namespace Core\BoundedContext\CommunicationManagement\Application\SendWhatsAppTemplate;

final class SendWhatsAppTemplateCommand
{
    public function __construct(
        private readonly string $appointmentId,
        private readonly string $patientId,
        private readonly string $phoneNumber,
        private readonly string $templateName,
        private readonly array $parameters
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

    public function templateName(): string
    {
        return $this->templateName;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }
}
