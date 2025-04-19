<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Services;

use Core\BoundedContext\CommunicationManagement\Application\Events\WhatsappMessageReceived;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;
use Illuminate\Contracts\Events\Dispatcher;

class HandleInboundWebhookService
{
    private Dispatcher $eventDispatcher;

    public function __construct(Dispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handleIncomingMessage(array $payload): void
    {
        $messageType = $payload['type'] ?? 'unknown';

        switch ($messageType) {
            case 'whatsapp':
                $this->processWhatsappMessage($payload);
                break;
            case 'sms':
                $this->processSmsMessage($payload);
                break;
            default:
                // Log unknown message type
                break;
        }
    }

    private function processWhatsappMessage(array $payload): void
    {
        $phoneNumber = new PhoneNumber($payload['from'] ?? '');
        $message = $payload['message'] ?? '';
        $messageId = $payload['message_id'] ?? '';

        $event = new WhatsappMessageReceived(
            $phoneNumber,
            $message,
            $messageId,
            $payload
        );

        $this->eventDispatcher->dispatch($event);
    }

    private function processSmsMessage(array $payload): void
    {
        // Similar to processWhatsappMessage but for SMS
    }
}
