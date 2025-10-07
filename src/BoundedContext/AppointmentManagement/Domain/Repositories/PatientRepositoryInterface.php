<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\Repositories;

interface PatientRepositoryInterface
{
    public function findById(int|string $patientId): ?array;
    public function findByDocument(string $document): ?array;
    /**
     * Crea un paciente y retorna el array del paciente creado
     */
    public function createPatient(array $data): int;
    /**
     * Actualiza un paciente y retorna el ID del paciente actualizado
     */
    public function updatePatient(int|string $patientId, array $data): int;
}
