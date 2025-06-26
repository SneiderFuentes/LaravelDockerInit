<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleConfigRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\BaseRepository;
use Core\Shared\Infrastructure\Mapping\ArrayMapper;

class ScheduleConfigRepository extends BaseRepository implements ScheduleConfigRepositoryInterface
{
    public function __construct(
        GetSubaccountConfigService $configService
    ) {
        parent::__construct($configService);
    }

    public function findByScheduleId($agendaId, ?string $doctorDocument = null): ?array
    {
        $config = $this->getConfig();
        $table = $config->tables()['schedule_configs']['table'];
        $mapping = $config->tables()['schedule_configs']['mapping'];
        $connection = $config->connection();
        $row = DB::connection($connection)
            ->table($table)
            ->where($mapping['agenda_id'], $agendaId)
            ->when($doctorDocument, function ($query) use ($doctorDocument, $mapping) {
                $query->where($mapping['doctor_document'], $doctorDocument);
            })
            ->first();
        return $row ? ArrayMapper::mapToLogicalFields($row, $mapping) : null;
    }

    public function getAppointmentDuration($agendaId): int
    {
        $config = $this->getConfig();
        $mapping = $config->tables()['schedule_configs']['mapping'];
        $row = $this->findByScheduleId($agendaId);
        if ($row && isset($row['appointment_duration'])) {
            return (int)$row['appointment_duration'];
        }
        return 30; // valor por defecto
    }
}
