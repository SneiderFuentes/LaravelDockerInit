<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Repositories;

interface DoctorRepositoryInterface
{

    /**
     * @param string $cupId
     * @param string $especialidad
     * @return array
     */
    public function findDoctorsByCupId(int|string $cupId): array;

    public function findByDocumentNumber(string $documentNumber): ?array;
}
