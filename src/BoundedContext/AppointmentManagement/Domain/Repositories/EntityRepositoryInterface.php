<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Repositories;

interface EntityRepositoryInterface
{
    public function findById(string $entityId): ?array;
    public function findByCode(string $code): ?array;
    /**
     * Devuelve todas las entidades activas
     * @return array
     */
    public function findAllActive(): array;
}
