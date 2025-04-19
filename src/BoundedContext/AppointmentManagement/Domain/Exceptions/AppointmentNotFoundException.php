<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Exceptions;

use Exception;

final class AppointmentNotFoundException extends Exception
{
    public static function withId(string $id, string $centerKey): self
    {
        return new self("Appointment with id '{$id}' not found in center '{$centerKey}'");
    }
}
