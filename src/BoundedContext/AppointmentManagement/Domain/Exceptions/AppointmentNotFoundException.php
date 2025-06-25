<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Exceptions;

use Exception;

final class AppointmentNotFoundException extends Exception
{
    public static function withId(string $id, string $centerKey): self
    {
        return new self("La cita que intenta acceder no fue encontrada o ya no existe.");
    }
}
