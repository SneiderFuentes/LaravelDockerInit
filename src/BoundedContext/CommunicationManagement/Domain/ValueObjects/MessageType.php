<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\ValueObjects;

use InvalidArgumentException;

final class MessageType
{
    public const WHATSAPP = 'whatsapp';
    public const SMS = 'sms';

    private string $value;

    private function __construct(string $value)
    {
        $this->ensureIsValidType($value);
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function whatsapp(): self
    {
        return new self(self::WHATSAPP);
    }

    public static function sms(): self
    {
        return new self(self::SMS);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isWhatsapp(): bool
    {
        return $this->value === self::WHATSAPP;
    }

    public function isSms(): bool
    {
        return $this->value === self::SMS;
    }

    private function ensureIsValidType(string $value): void
    {
        $validTypes = [
            self::WHATSAPP,
            self::SMS
        ];

        if (!in_array($value, $validTypes)) {
            throw new InvalidArgumentException(sprintf(
                'El valor "%s" no es un tipo de mensaje válido. Tipos válidos: %s',
                $value,
                implode(', ', $validTypes)
            ));
        }
    }
}
