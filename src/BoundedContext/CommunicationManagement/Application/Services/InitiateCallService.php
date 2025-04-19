<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Services;

use Core\BoundedContext\CommunicationManagement\Domain\Ports\CallGatewayInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;

class InitiateCallService
{
    private CallGatewayInterface $callGateway;

    public function __construct(CallGatewayInterface $callGateway)
    {
        $this->callGateway = $callGateway;
    }

    public function initiateCall(string $phoneNumber, string $appointmentId): string
    {
        $phone = new PhoneNumber($phoneNumber);

        return $this->callGateway->placeCall($phone, $appointmentId);
    }
}
