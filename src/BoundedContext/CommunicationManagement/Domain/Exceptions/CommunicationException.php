<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Exceptions;

use Exception;

class CommunicationException extends Exception
{
    private ?string $errorCode;

    public function __construct(string $message, ?string $errorCode = null, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
