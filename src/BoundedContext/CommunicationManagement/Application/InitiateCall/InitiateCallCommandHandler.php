<?php

namespace Core\BoundedContext\CommunicationManagement\Application\InitiateCall;

use Core\BoundedContext\CommunicationManagement\Domain\Services\CommunicationService;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\CallType;
use Core\Shared\Domain\Bus\Command\CommandHandler;
use Core\Shared\Domain\Bus\Event\EventBus;

final class InitiateCallCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly CommunicationService $communicationService,
        private readonly EventBus $eventBus
    ) {}

    public function __invoke(InitiateCallCommand $command): void
    {
        $callType = CallType::fromString($command->callType());

        $call = $this->communicationService->initiateCall(
            $command->appointmentId(),
            $command->patientId(),
            $command->phoneNumber(),
            $callType,
            $command->flowId()
        );

        // Publicar evento de dominio
        $this->eventBus->publish(
            new \Core\BoundedContext\CommunicationManagement\Domain\Events\CallInitiated(
                $call->getId(),
                $call->getAppointmentId(),
                $call->getPatientId(),
                $call->getPhoneNumber(),
                $call->getType()->value(),
                $call->getCallId() ?? '',
                $call->getFlowId(),
            )
        );
    }
}
