<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Repositories;

interface CupProcedureRepositoryInterface
{
    public function findById(int $cupId): ?array;
    public function findByCode(string $cupCode): ?array;
    public function findAll(): array;
}
