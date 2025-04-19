<?php

namespace Core\Shared\Domain\Exceptions;

use Exception;

class ValidationException extends Exception
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Error de validación', int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function errorMessages(): array
    {
        $messages = [];

        foreach ($this->errors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                foreach ($fieldErrors as $error) {
                    $messages[] = $error;
                }
            } else {
                $messages[] = $fieldErrors;
            }
        }

        return $messages;
    }
}
