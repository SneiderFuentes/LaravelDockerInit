<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Domain\Repositories;

use Core\BoundedContext\SubaccountManagement\Domain\Entities\Subaccount;

interface SubaccountRepositoryInterface
{
    /**
     * Find a Subaccount by its key
     */
    public function findByKey(string $key): ?Subaccount;

    /**
     * Find a Subaccount by its id
     */
    public function findById(string $id): ?Subaccount;

    /**
     * Save a Subaccount (create or update)
     */
    public function save(Subaccount $subaccount): void;

    /**
     * Find all Subaccounts
     *
     * @return Subaccount[]
     */
    public function findAll(): array;
}
