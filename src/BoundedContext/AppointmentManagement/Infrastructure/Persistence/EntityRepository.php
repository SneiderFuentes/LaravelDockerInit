<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\EntityRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\BaseRepository;
use Core\Shared\Infrastructure\Mapping\ArrayMapper;

class EntityRepository extends BaseRepository implements EntityRepositoryInterface
{
    public function __construct(
        GetSubaccountConfigService $configService
    ) {
        parent::__construct($configService);
    }

    public function findById(string $entityId): ?array
    {
        $config = $this->getConfig();
        $table = $config->tables()['entities']['table'];
        $mapping = $config->tables()['entities']['mapping'];
        $connection = $config->connection();
        $entity = DB::connection($connection)
            ->table($table)
            ->where($mapping['id'], $entityId)
            ->first();
        return $entity ? ArrayMapper::mapToLogicalFields($entity, $mapping) : null;
    }

    public function findByCode(string $code): ?array
    {
        $config = $this->getConfig();
        $table = $config->tables()['entities']['table'];
        $mapping = $config->tables()['entities']['mapping'];
        $connection = $config->connection();
        $entity = DB::connection($connection)
            ->table($table)
            ->where($mapping['code'], $code)
            ->first();
        return $entity ? ArrayMapper::mapToLogicalFields($entity, $mapping) : null;
    }

    public function findAllActive(): array
    {
        $config = $this->getConfig();
        $table = $config->tables()['entities']['table'];
        $mapping = $config->tables()['entities']['mapping'];
        $connection = $config->connection();
        $rows = DB::connection($connection)
            ->table($table)
            ->where($mapping['is_active'], -1)
            ->get();
        return $rows->map(fn($row) => ArrayMapper::mapToLogicalFields($row, $mapping))->toArray();
    }
}
