<?php

namespace Core\BoundedContext\CommunicationManagement\Application\SendWhatsappMessage;

use Core\BoundedContext\CommunicationManagement\Domain\Services\CommunicationService;
use Core\Shared\Domain\Bus\Command\CommandHandler;
use Core\Shared\Domain\Bus\Event\EventBus;

final class SendWhatsappMessageCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly CommunicationService $communicationService,
        private readonly EventBus $eventBus
    ) {}

    public function __invoke(SendWhatsappMessageCommand $command): void
    {
        $message = $this->communicationService->sendWhatsApp(
            $command->appointmentId(),
            $command->patientId(),
            $command->phoneNumber(),
            $command->content()
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
