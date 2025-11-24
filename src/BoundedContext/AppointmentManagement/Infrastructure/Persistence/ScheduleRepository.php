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
            if ($type === 'procedimiento' || $type === 'nocturno' || $type === 'sedacion') {
                $query->whereRaw($mapping['name'] . ' COLLATE utf8_general_ci LIKE ?', ['%' . $type . '%']);
            } else {
                // Buscar agendas que NO contengan procedimiento, nocturno, sedacion
                $query->whereRaw($mapping['name'] . ' COLLATE utf8_general_ci NOT LIKE ?', ['%procedimiento%'])
                      ->whereRaw($mapping['name'] . ' COLLATE utf8_general_ci NOT LIKE ?', ['%nocturno%'])
                      ->whereRaw($mapping['name'] . ' COLLATE utf8_general_ci NOT LIKE ?', ['%sedacion%']);
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

    public function deleteWorkingDayException(int $agendaId, string $doctorDocument, string $date): bool
    {
        $config = $this->getConfig();
        $workingDaysConfig = $config->tables()['working_days'];
        $table = $workingDaysConfig['table'];
        $mapping = $workingDaysConfig['mapping'];
        $connection = $config->connection();

        $deleted = DB::connection($connection)
            ->table($table)
            ->where($mapping['agenda_id'], $agendaId)
            ->where($mapping['doctor_document'], $doctorDocument)
            ->where($mapping['date'], $date)
            ->delete();

        return $deleted > 0;
    }

    public function findWorkingDayException(int $agendaId, string $doctorDocument, string $date): ?array
    {
        $config = $this->getConfig();
        $workingDaysConfig = $config->tables()['working_days'];
        $table = $workingDaysConfig['table'];
        $mapping = $workingDaysConfig['mapping'];
        $connection = $config->connection();

        $row = DB::connection($connection)
            ->table($table)
            ->where($mapping['agenda_id'], $agendaId)
            ->where($mapping['doctor_document'], $doctorDocument)
            ->where($mapping['date'], $date)
            ->first();

        return $row ? ArrayMapper::mapToLogicalFields($row, $mapping) : null;
    }

    public function updateWorkingDayExceptionDate(int $agendaId, string $doctorDocument, string $currentDate, string $newDate): bool
    {
        $config = $this->getConfig();
        $workingDaysConfig = $config->tables()['working_days'];
        $table = $workingDaysConfig['table'];
        $mapping = $workingDaysConfig['mapping'];
        $connection = $config->connection();

        $updated = DB::connection($connection)
            ->table($table)
            ->where($mapping['agenda_id'], $agendaId)
            ->where($mapping['doctor_document'], $doctorDocument)
            ->where($mapping['date'], $currentDate)
            ->update([
                $mapping['date'] => $newDate
            ]);

        return $updated > 0;
    }

    public function findScheduleByDoctorAndType(string $doctorDocument, string $type): ?array
    {
        $config = $this->getConfig();
        $schedulesConfig = $config->tables()['schedules'];
        $table = $schedulesConfig['table'];
        $mapping = $schedulesConfig['mapping'];
        $connection = $config->connection();

        $row = DB::connection($connection)
            ->table($table)
            ->where($mapping['doctor_document'], $doctorDocument)
            ->whereRaw($mapping['name'] . ' COLLATE utf8_general_ci LIKE ?', ['%' . $type . '%'])
            ->first();

        return $row ? ArrayMapper::mapToLogicalFields($row, $mapping) : null;
    }
}
