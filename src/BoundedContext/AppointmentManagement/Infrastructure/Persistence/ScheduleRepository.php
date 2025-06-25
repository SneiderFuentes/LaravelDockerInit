<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use DateTime;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\BaseRepository;
use Core\Shared\Infrastructure\Mapping\ArrayMapper;

class ScheduleRepository extends BaseRepository implements ScheduleRepositoryInterface
{
    public function __construct(
        GetSubaccountConfigService $configService
    ) {
        parent::__construct($configService);
    }

    public function findAvailableSlots(string $doctorId, int $duracion, ?array $horariosEspecificos = null): array
    {
        // TODO: Implementar consulta a la base de datos de agendas y cálculo de slots
        return [];
    }

    public function findByScheduleId($scheduleId, ?string $type = null): ?array
    {
        $config = $this->getConfig();
        $workingDaysConfig = $config->tables()['schedules'];
        $table = $workingDaysConfig['table'];
        $mapping = $workingDaysConfig['mapping'];
        $connection = $config->connection();

        $query = DB::connection($connection)
            ->table($table)
            ->where($mapping['id'], $scheduleId);

        if ($type !== null) {
            if ($type === 'procedimiento' || $type === 'nocturno') {
                $query->where($mapping['name'], 'like', '%' . $type . '%');
            }
        }

        $row = $query->first();

        return $row ? ArrayMapper::mapToLogicalFields($row, $mapping) : null;
    }

    public function findFutureWorkingDaysByDoctors(array $doctorDocuments): array
    {
        $config = $this->getConfig();
        $workingDaysConfig = $config->tables()['working_days'];
        $table = $workingDaysConfig['table'];
        $mapping = $workingDaysConfig['mapping'];
        $today = (new DateTime())->format('Y-m-d');
        $connection = $config->connection();
        $rows = DB::connection($connection)
            ->table($table)
            ->whereIn($mapping['doctor_document'], $doctorDocuments)
            ->where($mapping['date'], '>', $today)
            ->orderBy($mapping['date'], 'asc')
            ->get();
        return $rows->map(fn($row) => array_combine(
            array_keys($mapping),
            array_map(fn($key) => $row->{$mapping[$key]} ?? null, array_keys($mapping))
        ))->toArray();
    }
}
