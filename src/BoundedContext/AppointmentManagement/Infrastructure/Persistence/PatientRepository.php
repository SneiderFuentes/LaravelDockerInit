<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\BaseRepository;
use Core\Shared\Infrastructure\Mapping\ArrayMapper;

class PatientRepository extends BaseRepository implements PatientRepositoryInterface
{
    public function __construct(
        GetSubaccountConfigService $configService
    ) {
        parent::__construct($configService);
    }

    public function findById(int|string $patientId): ?array
    {
        $config = $this->getConfig();
        $table = $config->tables()['patients']['table'];
        $mapping = $config->tables()['patients']['mapping'];
        $connection = $config->connection();
        $patient = DB::connection($connection)
            ->table($table)
            ->where($mapping['id'], $patientId)
            ->first();
        return $patient ? ArrayMapper::mapToLogicalFields($patient, $mapping) : null;
    }

    public function findByDocument(string $document): ?array
    {
        $config = $this->getConfig();
        $table = $config->tables()['patients']['table'];
        $mapping = $config->tables()['patients']['mapping'];
        $connection = $config->connection();
        $patient = DB::connection($connection)
            ->table($table)
            ->where($mapping['document_number'], $document)
            ->first();
        return $patient ? ArrayMapper::mapToLogicalFields($patient, $mapping) : null;
    }

    public function createPatient(array $data): int
    {
        $config = $this->getConfig();
        $table = $config->tables()['patients']['table'];
        $mapping = $config->tables()['patients']['mapping'];
        $connection = $config->connection();

        // Mapear los datos de entrada al mapping de la tabla
        $insertData = [
            $mapping['document_type'] => $data['document_type'],
            $mapping['document_number'] => $data['document_number'],
            $mapping['first_name'] => $data['first_name'],
            $mapping['second_name'] => $data['second_name'] ?? null,
            $mapping['first_surname'] => $data['first_surname'],
            $mapping['second_surname'] => $data['second_surname'] ?? null,
            $mapping['full_name'] => $data['full_name'],
            $mapping['gender'] => $data['gender'],
            $mapping['birth_date'] => $data['birth_date'],
            $mapping['birth_place'] => $data['birth_place'],
            $mapping['marital_status'] => $data['marital_status'],
            $mapping['address'] => $data['address'],
            $mapping['phone'] => $data['phone'],
            $mapping['email'] => $data['email'],
            $mapping['occupation'] => $data['occupation'],
            $mapping['entity_code'] => $data['entity_code'],
            $mapping['user_type'] => $data['user_type'],
            $mapping['affiliation_type'] => $data['affiliation_type'],
            $mapping['zone'] => $data['zone'],
            $mapping['level'] => 1,
            $mapping['education_level'] => 1,
            $mapping['country_code'] => 170,
            $mapping['city_code'] => $data['city_code'],
            $mapping['created_at'] => now(),
            $mapping['updated_at'] => now(),
        ];
        // Teléfono secundario y otros opcionales
        if (!empty($data['phone_secondary'])) {
            $insertData[$mapping['phone']] .= ' / ' . $data['phone_secondary'];
        }
        $id = DB::connection($connection)->table($table)->insertGetId($insertData);
        // Retornar el paciente creado
        return $id;
    }
}
