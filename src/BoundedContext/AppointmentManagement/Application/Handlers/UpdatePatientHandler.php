<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\EntityRepositoryInterface;

class UpdatePatientHandler
{
    public function __construct(private PatientRepositoryInterface $repository, private EntityRepositoryInterface $entityRepository) {}

    public function handle(array $data): int
    {
        $existing = $this->repository->findByDocument($data['document_number']);
        if (!$existing) {
            throw new \InvalidArgumentException('No existe un paciente con ese número de documento.');
        }

        $cityCode = $existing['city_code'];

        $sanMunicipalityCodes = [
            '50001', // Villavicencio
            '50006', // Acacías
            '50313', // Granada
            '50573', // Puerto López
            '50223', // Cubarral
            '50568', // Puerto Gaitán
            '50680', // San Carlos de Guaroa
            '50226', // Cumaral
            '50711', // San Martín
            '50150', // Castilla La Nueva
            '50124', // Cabuyaro
            '50110', // Barranca de Upía
            '50590'  // Puerto Rico
        ];

        $entityCode = $this->entityRepository->getEntityCodeByIndex(intval($data['entity_code']));

        if ($entityCode === 'SAN01' || $entityCode === 'SAN02') {
            if (in_array($cityCode, $sanMunicipalityCodes)) {
                $entityCode = 'SAN02';
            }
        }

        $updateData = [
            'entity_code' => $entityCode
        ];

        $id = $this->repository->updatePatient($existing['id'], $updateData);
        return $id;
    }
}

