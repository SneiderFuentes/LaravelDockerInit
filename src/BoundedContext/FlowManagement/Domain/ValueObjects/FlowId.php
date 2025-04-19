<?php

namespace Core\BoundedContext\FlowManagement\Domain\ValueObjects;

class FlowId
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

    public function equals(FlowId $other): bool
    {
        return $this->value === $other->value;
    }

    public static function generate(): self
    {
        return new self('flow_' . uniqid());
    }
}
