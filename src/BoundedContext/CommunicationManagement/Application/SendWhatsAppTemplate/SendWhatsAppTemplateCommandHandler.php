<?php

namespace Core\BoundedContext\CommunicationManagement\Application\SendWhatsAppTemplate;

use Core\BoundedContext\CommunicationManagement\Domain\Services\CommunicationService;
use Core\Shared\Domain\Bus\Command\CommandHandler;
use Core\Shared\Domain\Bus\Event\EventBus;

final class SendWhatsAppTemplateCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly CommunicationService $communicationService,
        private readonly EventBus $eventBus
    ) {}

    public function __invoke(SendWhatsAppTemplateCommand $command): void
    {
        $message = $this->communicationService->sendWhatsAppTemplate(
            $command->appointmentId(),
            $command->patientId(),
            $command->phoneNumber(),
            $command->templateName(),
            $command->parameters()
        );

        // Publicar evento de dominio
        $this->eventBus->publish(
            new \Core\BoundedContext\CommunicationManagement\Domain\Events\MessageSent(
                $message->getId(),
                $message->getAppointmentId(),
                $message->getPatientId(),
                $message->getPhoneNumber(),
                $message->getType()->value(),
                $message->getContent(),
                $message->getMessageId() ?? ''
            )
        );
    }
}
