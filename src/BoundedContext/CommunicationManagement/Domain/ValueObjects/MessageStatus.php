<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\ValueObjects;

use InvalidArgumentException;

final class MessageStatus
{
    public const PENDING = 'pending';
    public const SENT = 'sent';
    public const DELIVERED = 'delivered';
    public const READ = 'read';
    public const FAILED = 'failed';

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

    public static function sent(): self
    {
        return new self(self::SENT);
    }

    public static function delivered(): self
    {
        return new self(self::DELIVERED);
    }

    public static function read(): self
    {
        return new self(self::READ);
    }

    public static function failed(): self
    {
        return new self(self::FAILED);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isSent(): bool
    {
        return $this->value === self::SENT;
    }

    public function isDelivered(): bool
    {
        return $this->value === self::DELIVERED;
    }

    public function isRead(): bool
    {
        return $this->value === self::READ;
    }

    public function isFailed(): bool
    {
        return $this->value === self::FAILED;
    }

    private function ensureIsValidStatus(string $value): void
    {
        $validStatuses = [
            self::PENDING,
            self::SENT,
            self::DELIVERED,
            self::READ,
            self::FAILED
        ];

        if (!in_array($value, $validStatuses)) {
            throw new InvalidArgumentException(sprintf(
                'El valor "%s" no es un estado de mensaje válido. Estados válidos: %s',
                $value,
                implode(', ', $validStatuses)
            ));
        }
    }
}
