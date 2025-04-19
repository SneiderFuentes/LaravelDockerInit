<?php

namespace Core\BoundedContext\AppointmentManagement\Domain\ValueObjects;

class AppointmentId
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(AppointmentId $other): bool
    {
        return $this->value === $other->value;
    }

    public static function generate(): self
    {
        return new self(uniqid('apt_', true));
    }
}
