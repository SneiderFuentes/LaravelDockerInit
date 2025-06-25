<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\PatientDTO;

class CreatePatientHandler
{
    public function __construct(private PatientRepositoryInterface $repository) {}

    public function handle(array $data): int
    {
        // Validar que no exista un paciente con el mismo documento
        $existing = $this->repository->findByDocument($data['document_number']);
        if ($existing) {
            throw new \InvalidArgumentException('Ya existe un paciente con ese número de documento.');
        }
        $name = $data['first_name'] !== 'NA' ? $data['first_name'] . ' ' : '';
        $second_name = $data['second_name'] !== 'NA' ? $data['second_name'] . ' ' : '';
        $first_surname = $data['first_surname'] !== 'NA' ? $data['first_surname'] . ' ' : '';
        $second_surname = $data['second_surname'] !== 'NA' ? $data['second_surname'] . ' ' : '';
        $data['full_name'] = $name . $second_name . $first_surname . $second_surname;
        $id = $this->repository->createPatient($data);
        return $id;
    }
}
