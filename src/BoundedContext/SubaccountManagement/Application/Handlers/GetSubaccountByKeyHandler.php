<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Application\Handlers;

use Core\BoundedContext\SubaccountManagement\Application\DTOs\SubaccountDto;
use Core\BoundedContext\SubaccountManagement\Application\Queries\GetSubaccountByKeyQuery;
use Core\BoundedContext\SubaccountManagement\Domain\Exceptions\SubaccountNotFoundException;
use Core\BoundedContext\SubaccountManagement\Domain\Repositories\SubaccountRepositoryInterface;

final class GetSubaccountByKeyHandler
{
    public function __construct(
        private SubaccountRepositoryInterface $repository
    ) {}

    public function handle(GetSubaccountByKeyQuery $query): SubaccountDto
    {
        $subaccount = $this->repository->findByKey($query->key());

        if ($subaccount === null) {
            throw SubaccountNotFoundException::withKey($query->key());
        }

        return SubaccountDto::fromEntity($subaccount);
    }
}
