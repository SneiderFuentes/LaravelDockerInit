<?php

namespace Core\BoundedContext\FlowManagement\Domain\ValueObjects;

class ChannelType
{
    private const SMS = 'sms';
    private const WHATSAPP = 'whatsapp';
    private const VOICE = 'voice';

    private string $value;

    private function __construct(string $value)
    {
        $this->ensureIsValidChannelType($value);
        $this->value = $value;
    }

    public static function sms(): self
    {
        return new self(self::SMS);
    }

    public static function whatsapp(): self
    {
        return new self(self::WHATSAPP);
    }

    public static function voice(): self
    {
        return new self(self::VOICE);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ChannelType $other): bool
    {
        return $this->value === $other->value;
    }

    public function isSms(): bool
    {
        return $this->value === self::SMS;
    }

    public function isWhatsapp(): bool
    {
        return $this->value === self::WHATSAPP;
    }

    public function isVoice(): bool
    {
        return $this->value === self::VOICE;
    }

    private function ensureIsValidChannelType(string $value): void
    {
        $validTypes = [self::SMS, self::WHATSAPP, self::VOICE];

        if (!in_array($value, $validTypes)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid channel type. Valid types are: %s',
                $value,
                implode(', ', $validTypes)
            ));
        }
    }
}
