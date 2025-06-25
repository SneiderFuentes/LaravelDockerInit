<?php

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\CupProcedureRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\Shared\Infrastructure\Mapping\ArrayMapper;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\BaseRepository;

class CupProcedureRepository extends BaseRepository implements CupProcedureRepositoryInterface
{
    public function __construct(
        GetSubaccountConfigService $configService
    ) {
        parent::__construct($configService);
    }

    public function findById(int $cupId): ?array
    {
        $config = $this->getConfig();
        $procedureConfig = $config->tables()['procedures'];
        $connection = $procedureConfig['connection'] ?? $config->connection();
        $table = $procedureConfig['table'];
        $mapping = $procedureConfig['mapping'];
        $cup = DB::connection($connection)
            ->table($table)
            ->where($mapping['id'], $cupId)
            ->first();
        return $cup ? ArrayMapper::mapToLogicalFields($cup, $mapping) : null;
    }

    public function findByCode(string $cupCode): ?array
    {
        $config = $this->getConfig();
        $procedureConfig = $config->tables()['procedures'];
        $connection = $procedureConfig['connection'] ?? $config->connection();
        $table = $procedureConfig['table'];
        $mapping = $procedureConfig['mapping'];
        $cup = DB::connection($connection)
            ->table($table)
            ->where($mapping['code'], $cupCode)
            ->first();

        if (!$cup) {
            return null;
        }

        $mappedData = ArrayMapper::mapToLogicalFields($cup, $mapping);

        return $mappedData;
    }

    public function findAll(): array
    {
        $config = $this->getConfig();
        $procedureConfig = $config->tables()['procedures'];
        $connection = $procedureConfig['connection'] ?? $config->connection();
        $table = $procedureConfig['table'];
        $mapping = $procedureConfig['mapping'];
        $cup = DB::connection($connection)
            ->table($table)
            ->get();

        return $cup->map(function ($cup) use ($mapping) {
            return ArrayMapper::mapToLogicalFields($cup, $mapping);
        })->toArray();
    }
}
