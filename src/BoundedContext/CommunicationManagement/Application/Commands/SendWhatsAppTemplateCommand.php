<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Commands;

class SendWhatsAppTemplateCommand
{
    public function __construct(
        private readonly string $appointmentId,
        private readonly string $patientId,
        private readonly string $phoneNumber,
        private readonly string $templateName,
        private readonly array $parameters
    ) {}

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

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
