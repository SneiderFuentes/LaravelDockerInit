<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\ValueObjects;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
    case Completed = 'completed';

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'pending' => self::Pending,
            'confirmed' => self::Confirmed,
            'cancelled' => self::Cancelled,
            'no_show' => self::NoShow,
            'completed' => self::Completed,
            default => throw new \InvalidArgumentException("Invalid appointment status: {$value}"),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Confirmed => 'Confirmada',
            self::Cancelled => 'Cancelada',
            self::NoShow => 'No Asistió',
            self::Completed => 'Completada',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this === self::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }
}
