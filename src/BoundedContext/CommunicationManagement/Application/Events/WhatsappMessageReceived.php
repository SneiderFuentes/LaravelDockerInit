<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Events;

use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;

class WhatsappMessageReceived
{
    private PhoneNumber $from;
    private string $message;
    private string $messageId;
    private array $rawPayload;

    public function __construct(
        PhoneNumber $from,
        string $message,
        string $messageId,
        array $rawPayload
    ) {
        $this->from = $from;
        $this->message = $message;
        $this->messageId = $messageId;
        $this->rawPayload = $rawPayload;
    }

    public function from(): PhoneNumber
    {
        return $this->from;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function messageId(): string
    {
        return $this->messageId;
    }

    public function rawPayload(): array
    {
        return $this->rawPayload;
    }
}
