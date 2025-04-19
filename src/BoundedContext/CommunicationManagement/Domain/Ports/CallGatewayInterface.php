<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Ports;

use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;

interface CallGatewayInterface
{
    /**
     * Place an outbound call to a phone number
     *
     * @param PhoneNumber $to
     * @param string $flowId
     * @param array $parameters
     * @return string Call ID
     */
    public function placeCall(PhoneNumber $to, string $flowId, array $parameters = []): string;

    /**
     * Get the status of a call
     *
     * @param string $callId
     * @return string
     */
    public function getCallStatus(string $callId): string;
}
