<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\ValueObjects;

class PhoneNumber
{
    private string $value;
    private ?string $countryCode;

    public function __construct(string $value, ?string $countryCode = null)
    {
        $this->validate($value);
        $this->value = $this->sanitize($value);
        $this->countryCode = $countryCode ?? '52'; // Default to Mexico
    }

    public function value(): string
    {
        return $this->value;
    }

    public function countryCode(): string
    {
        return $this->countryCode;
    }

    public function fullNumber(): string
    {
        return $this->countryCode . $this->value;
    }

    private function validate(string $value): void
    {
        $sanitized = $this->sanitize($value);

        if (strlen($sanitized) < 10) {
            throw new \InvalidArgumentException('Phone number must have at least 10 digits');
        }
    }

    private function sanitize(string $value): string
    {
        // Remove any non-digit character
        return preg_replace('/\D/', '', $value);
    }
}
