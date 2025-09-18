<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Repositories;

interface MunicipalityRepositoryInterface
{
    /**
     * Buscar municipio por código de municipio
     */
    public function findByMunicipalityCode(string $municipalityCode): ?array;

    /**
     * Buscar municipios por código de departamento
     */
    public function findByDepartmentCode(string $departmentCode): array;

    /**
     * Buscar municipio por nombre
     */
    public function findByMunicipalityName(string $municipalityName): ?array;

    /**
     * Buscar municipios por nombre de departamento
     */
    public function findByDepartmentName(string $departmentName): array;

    /**
     * Buscar municipio por nombre de municipio y departamento
     */
    public function findByMunicipalityAndDepartment(string $municipalityName, string $departmentName): ?array;

    /**
     * Obtener todos los municipios
     */
    public function findAll(): array;
}
