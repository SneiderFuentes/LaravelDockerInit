<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\ValueObjects;

use InvalidArgumentException;

final class CallType
{
    public const APPOINTMENT_REMINDER = 'appointment_reminder';
    public const APPOINTMENT_CONFIRMATION = 'appointment_confirmation';
    public const APPOINTMENT_CANCELLATION = 'appointment_cancellation';

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

    public static function appointmentReminder(): self
    {
        return new self(self::APPOINTMENT_REMINDER);
    }

    public static function appointmentConfirmation(): self
    {
        return new self(self::APPOINTMENT_CONFIRMATION);
    }

    public static function appointmentCancellation(): self
    {
        return new self(self::APPOINTMENT_CANCELLATION);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isAppointmentReminder(): bool
    {
        return $this->value === self::APPOINTMENT_REMINDER;
    }

    public function isAppointmentConfirmation(): bool
    {
        return $this->value === self::APPOINTMENT_CONFIRMATION;
    }

    public function isAppointmentCancellation(): bool
    {
        return $this->value === self::APPOINTMENT_CANCELLATION;
    }

    private function ensureIsValidType(string $value): void
    {
        $validTypes = [
            self::APPOINTMENT_REMINDER,
            self::APPOINTMENT_CONFIRMATION,
            self::APPOINTMENT_CANCELLATION
        ];

        if (!in_array($value, $validTypes)) {
            throw new InvalidArgumentException(sprintf(
                'El valor "%s" no es un tipo de llamada válido. Tipos válidos: %s',
                $value,
                implode(', ', $validTypes)
            ));
        }
    }
}
