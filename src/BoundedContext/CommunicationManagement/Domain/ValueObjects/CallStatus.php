<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\ValueObjects;

use InvalidArgumentException;

final class CallStatus
{
    public const PENDING = 'pending';
    public const INITIATED = 'initiated';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const CONFIRMED = 'confirmed';
    public const CANCELLED = 'cancelled';
    public const FAILED = 'failed';
    public const NO_ANSWER = 'no_answer';

    private string $value;

    private function __construct(string $value)
    {
        $this->ensureIsValidStatus($value);
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function initiated(): self
    {
        return new self(self::INITIATED);
    }

    public static function inProgress(): self
    {
        return new self(self::IN_PROGRESS);
    }

    public static function completed(): self
    {
        return new self(self::COMPLETED);
    }

    public static function confirmed(): self
    {
        return new self(self::CONFIRMED);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function failed(): self
    {
        return new self(self::FAILED);
    }

    public static function noAnswer(): self
    {
        return new self(self::NO_ANSWER);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isConfirmed(): bool
    {
        return $this->value === self::CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this->value === self::FAILED;
    }

    private function ensureIsValidStatus(string $value): void
    {
        $validStatuses = [
            self::PENDING,
            self::INITIATED,
            self::IN_PROGRESS,
            self::COMPLETED,
            self::CONFIRMED,
            self::CANCELLED,
            self::FAILED,
            self::NO_ANSWER
        ];

        if (!in_array($value, $validStatuses)) {
            throw new InvalidArgumentException(sprintf(
                'El valor "%s" no es un estado de llamada válido. Estados válidos: %s',
                $value,
                implode(', ', $validStatuses)
            ));
        }
    }
}
