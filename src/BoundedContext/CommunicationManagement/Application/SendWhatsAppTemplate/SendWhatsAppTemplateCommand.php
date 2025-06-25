<?php

namespace Core\BoundedContext\CommunicationManagement\Application\SendWhatsAppTemplate;

final class SendWhatsAppTemplateCommand
{
    public function __construct(
        public readonly string $appointmentId,
        public readonly string $patientId,
        public readonly string $phoneNumber,
        public readonly string $templateName,
        public readonly array $parameters,
        public readonly ?string $subaccountKey = null
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

    public function subaccountKey(): ?string
    {
        return $this->subaccountKey;
    }
}
