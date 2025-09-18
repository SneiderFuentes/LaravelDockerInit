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

    /**
     * Obtiene el código de una entidad por su índice en la lista numerada
     * @param int $index El número de la lista (basado en 1)
     * @return string|null El código de la entidad o null si no existe
     */
    public function getEntityCodeByIndex(int $index): ?string;
}
