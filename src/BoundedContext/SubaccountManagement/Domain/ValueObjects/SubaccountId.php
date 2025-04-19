<?php

namespace Core\BoundedContext\SubaccountManagement\Domain\ValueObjects;

class SubaccountId
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

    public function equals(SubaccountId $other): bool
    {
        return $this->value === $other->value;
    }

    public static function generate(): self
    {
        return new self(uniqid('sub_', true));
    }
}
