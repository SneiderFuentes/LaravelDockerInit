<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\MunicipalityRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\BaseRepository;
use Core\Shared\Infrastructure\Mapping\ArrayMapper;

class MunicipalityRepository extends BaseRepository implements MunicipalityRepositoryInterface
{
    public function __construct(
        GetSubaccountConfigService $configService
    ) {
        parent::__construct($configService);
    }

    public function findByMunicipalityCode(string $municipalityCode): ?array
    {
        $config = $this->getConfig();
        $table = $config->tables()['municipios']['table'];
        $mapping = $config->tables()['municipios']['mapping'];
        $connection = $config->connection();

        $municipality = DB::connection($connection)
            ->table($table)
            ->where($mapping['municipality_code'], $municipalityCode)
            ->first();

        return $municipality ? ArrayMapper::mapToLogicalFields($municipality, $mapping) : null;
    }

    public function findByDepartmentCode(string $departmentCode): array
    {
        $config = $this->getConfig();
        $table = $config->tables()['municipios']['table'];
        $mapping = $config->tables()['municipios']['mapping'];
        $connection = $config->connection();

        $municipalities = DB::connection($connection)
            ->table($table)
            ->where($mapping['department_code'], $departmentCode)
            ->get();

        return $municipalities->map(function ($municipality) use ($mapping) {
            return ArrayMapper::mapToLogicalFields($municipality, $mapping);
        })->toArray();
    }

    public function findByMunicipalityName(string $municipalityName): ?array
    {
        $config = $this->getConfig();
        $table = $config->tables()['municipios']['table'];
        $mapping = $config->tables()['municipios']['mapping'];
        $connection = $config->connection();

        $municipality = DB::connection($connection)
            ->table($table)
            ->where($mapping['municipality_name'], 'LIKE', "%{$municipalityName}%")
            ->first();

        return $municipality ? ArrayMapper::mapToLogicalFields($municipality, $mapping) : null;
    }

    public function findByDepartmentName(string $departmentName): array
    {
        $config = $this->getConfig();
        $table = $config->tables()['municipios']['table'];
        $mapping = $config->tables()['municipios']['mapping'];
        $connection = $config->connection();

        $municipalities = DB::connection($connection)
            ->table($table)
            ->where($mapping['department_name'], 'LIKE', "%{$departmentName}%")
            ->get();

        return $municipalities->map(function ($municipality) use ($mapping) {
            return ArrayMapper::mapToLogicalFields($municipality, $mapping);
        })->toArray();
    }

    public function findByMunicipalityAndDepartment(string $municipalityName, string $departmentName): ?array
    {
        $config = $this->getConfig();
        $table = $config->tables()['municipios']['table'];
        $mapping = $config->tables()['municipios']['mapping'];
        $connection = $config->connection();

        $municipality = DB::connection($connection)
            ->table($table)
            ->where($mapping['municipality_name'], 'LIKE', "%{$municipalityName}%")
            ->where($mapping['department_name'], 'LIKE', "%{$departmentName}%")
            ->first();

        return $municipality ? ArrayMapper::mapToLogicalFields($municipality, $mapping) : null;
    }

    public function findAll(): array
    {
        $config = $this->getConfig();
        $table = $config->tables()['municipios']['table'];
        $mapping = $config->tables()['municipios']['mapping'];
        $connection = $config->connection();

        $municipalities = DB::connection($connection)
            ->table($table)
            ->get();

        return $municipalities->map(function ($municipality) use ($mapping) {
            return ArrayMapper::mapToLogicalFields($municipality, $mapping);
        })->toArray();
    }
}
