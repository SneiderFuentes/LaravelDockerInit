<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Ports;

use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;

interface MessageGatewayInterface
{
    /**
     * Send a text message to a phone number
     *
     * @param PhoneNumber $to
     * @param string $message
     * @return string Message ID
     */
    public function sendTextMessage(PhoneNumber $to, string $message): string;

    /**
     * Send a template message to a phone number
     *
     * @param PhoneNumber $to
     * @param string $templateId
     * @param array $parameters
     * @return string Message ID
     */
    public function sendTemplateMessage(PhoneNumber $to, string $templateId, array $parameters): string;
}
