<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Domain\Exceptions;

use Exception;

final class SubaccountNotFoundException extends Exception
{
    public static function withKey(string $key): self
    {
        return new self("Subaccount with key '{$key}' not found");
    }

    public static function withId(string $id): self
    {
        return new self("Subaccount with id '{$id}' not found");
    }
}
