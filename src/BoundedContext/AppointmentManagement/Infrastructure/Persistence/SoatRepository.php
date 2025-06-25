<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\SoatRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\BaseRepository;

class SoatRepository extends BaseRepository implements SoatRepositoryInterface
{
    public function __construct(
        GetSubaccountConfigService $configService
    ) {
        parent::__construct($configService);
    }

    public function findPrice(string $cupCode, string $tipoPrecio): ?float
    {
        $config = $this->getConfig();
        $table = $config->tables()['soat_codes']['table'];
        $mapping = $config->tables()['soat_codes']['mapping'];
        $connection = $config->connection();
        $row = DB::connection($connection)
            ->table($table)
            ->where($mapping['cup_code'], $cupCode)
            ->first();
        if (!$row) {
            return null;
        }
        // El campo de precio depende del tipo de precio (ej: Tarifa01, Tarifa02, etc)
        $precioField = $mapping[$tipoPrecio] ?? $tipoPrecio;
        return isset($row->{$precioField}) ? (float)$row->{$precioField} : null;
    }
}
