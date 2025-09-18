<?php

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\CupProcedureRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\Shared\Infrastructure\Mapping\ArrayMapper;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\BaseRepository;
use Illuminate\Support\Facades\Log;


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
        $connection = $procedureConfig['connection'] ?? $config->connection17338119();
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
        Log::info('Cup data original', ['cup' => $cup]);
        Log::info('Cup servicio field', ['servicio' => $cup->servicio ?? 'NOT_FOUND']);

        if (!$cup) {
            return null;
        }

        $mappedData = ArrayMapper::mapToLogicalFields($cup, $mapping);
        Log::info('Cup data mapped', ['mapped_data' => $mappedData]);
        Log::info('Mapping config', ['mapping' => $mapping]);
        Log::info('Service name mapping check', [
            'servicio_field_exists' => isset($cup->servicio),
            'servicio_value' => $cup->servicio ?? 'NOT_FOUND',
            'service_name_in_mapping' => isset($mapping['service_name']),
            'service_name_mapped_value' => $mappedData['service_name'] ?? 'NOT_MAPPED'
        ]);

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
