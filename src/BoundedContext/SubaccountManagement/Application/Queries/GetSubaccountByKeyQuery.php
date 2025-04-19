<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Application\Queries;

final class GetSubaccountByKeyQuery
{
    public function __construct(
        private string $key
    ) {
        if (empty($key)) {
            throw new \InvalidArgumentException('Subaccount key cannot be empty');
        }
    }

    public function key(): string
    {
        return $this->key;
    }
}
