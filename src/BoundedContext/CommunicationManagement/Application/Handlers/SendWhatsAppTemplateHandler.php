<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Handlers;

use Core\BoundedContext\CommunicationManagement\Application\Commands\SendWhatsAppTemplateCommand;
use Core\BoundedContext\CommunicationManagement\Domain\Services\CommunicationService;

class SendWhatsAppTemplateHandler
{
    public function __construct(
        private CommunicationService $communicationService
    ) {}

    public function handle(SendWhatsAppTemplateCommand $command): string
    {
        return $this->communicationService->sendWhatsAppTemplate(
            $command->getAppointmentId(),
            $command->getPatientId(),
            $command->getPhoneNumber(),
            $command->getTemplateName(),
            $command->getParameters()
        );
    }
}
