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
        $messageId = $this->communicationService->sendWhatsAppTemplate(
            $command->appointmentId(),
            $command->patientId(),
            $command->phoneNumber(),
            $command->templateName(),
            $command->parameters(),
            $command->subaccountKey()
        );

        // Publicar evento de dominio
        $this->eventBus->publish(
            new \Core\BoundedContext\CommunicationManagement\Domain\Events\MessageSent(
                $messageId,
                $command->appointmentId(),
                $command->patientId(),
                $command->phoneNumber(),
                'whatsapp',
                json_encode($command->parameters()),
                $messageId,
                $command->subaccountKey()
            )
        );
    }
}
