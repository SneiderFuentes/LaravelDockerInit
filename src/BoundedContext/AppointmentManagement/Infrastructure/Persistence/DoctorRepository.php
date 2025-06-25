<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\DoctorRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\BaseRepository;
use Core\Shared\Infrastructure\Mapping\ArrayMapper;

class DoctorRepository extends BaseRepository implements DoctorRepositoryInterface
{
    public function __construct(
        GetSubaccountConfigService $configService
    ) {
        parent::__construct($configService);
    }

    public function findDoctorsByCupId(int|string $cupId): array
    {
        $config = $this->getConfig();
        $doctorConfig = $config->tables()['cup_medico'];
        $connection = $config->connection();
        $table = $doctorConfig['table'];
        $mapping = $doctorConfig['mapping'];
        $doctors = DB::connection($connection)
            ->table($table)
            ->where($mapping['cup_id'], $cupId)
            ->where($mapping['is_active'], 1)
            ->get();
        return $doctors->map(fn($row) => array_combine(
            array_keys($mapping),
            array_map(fn($key) => $row->{$mapping[$key]} ?? null, array_keys($mapping))
        ))->toArray();
    }

    public function findByDocumentNumber(string $documentNumber): ?array
    {
        $config = $this->getConfig();
        $doctorConfig = $config->tables()['cup_medico'];
        $connection = $config->connection();
        $table = $doctorConfig['table'];
        $mapping = $doctorConfig['mapping'];
        $doctor = DB::connection($connection)
            ->table($table)
            ->where($mapping['doctor_document'], $documentNumber)
            ->first();
        return $doctor ? ArrayMapper::mapToLogicalFields($doctor, $mapping) : null;
    }
}
