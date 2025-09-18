<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\MunicipalityRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\PatientDTO;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\EntityRepositoryInterface;

class CreatePatientHandler
{
    public function __construct(private PatientRepositoryInterface $repository, private MunicipalityRepositoryInterface $municipalityRepository, private EntityRepositoryInterface $entityRepository) {}

    public function handle(array $data): int
    {
        // Validar que no exista un paciente con el mismo documento
        $existing = $this->repository->findByDocument($data['document_number']);
        if ($existing) {
            throw new \InvalidArgumentException('Ya existe un paciente con ese número de documento.');
        }

        $municipalityName = '';
        $departmentName = '';

        if (isset($data['city_code']) && !empty($data['city_code'])) {
            $cityCodeParts = $this->parseCityCode($data['city_code']);
            $municipalityName = $cityCodeParts['municipality'];
            $departmentName = $cityCodeParts['department'];
        }

        $municipality = null;
        if (!empty($municipalityName) && !empty($departmentName)) {
            $municipality = $this->municipalityRepository->findByMunicipalityAndDepartment($municipalityName, $departmentName);
        }

        if (!$municipality) {
            $cityCode = '50001'; // Código por defecto
        } else {
            $cityCode = $municipality['municipality_code'];
        }

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

        $data['entity_code'] = $entityCode;

        $name = $data['first_name'] !== 'NA' ? $data['first_name'] . ' ' : '';
        $second_name = $data['second_name'] !== 'NA' ? $data['second_name'] . ' ' : '';
        $first_surname = $data['first_surname'] !== 'NA' ? $data['first_surname'] . ' ' : '';
        $second_surname = $data['second_surname'] !== 'NA' ? $data['second_surname'] . ' ' : '';
        $data['full_name'] = $name . $second_name . $first_surname . $second_surname;
        $data['city_code'] = $cityCode;
        $id = $this->repository->createPatient($data);
        return $id;
    }

    /**
     * Parsea el city_code para extraer municipio y departamento
     * Maneja formatos como: "Granada - Meta", "Granada-Meta", "Granada , Meta"
     */
    private function parseCityCode(string $cityCode): array
    {
        // Normalizar el string: convertir a minúsculas y limpiar espacios extra
        $normalized = strtolower(trim($cityCode));

        // Dividir por diferentes separadores: " - ", "-", " , ", ","
        $separators = [' - ', '-', ' , ', ','];
        $municipality = '';
        $department = '';

        foreach ($separators as $separator) {
            if (strpos($normalized, $separator) !== false) {
                $parts = explode($separator, $normalized, 2);
                $municipality = trim($parts[0]);
                $department = isset($parts[1]) ? trim($parts[1]) : '';
                break;
            }
        }

        // Si no se encontró separador, asumir que todo es municipio
        if (empty($municipality) && empty($department)) {
            $municipality = $normalized;
            $department = '';
        }

        return [
            'municipality' => $municipality,
            'department' => $department
        ];
    }
}
