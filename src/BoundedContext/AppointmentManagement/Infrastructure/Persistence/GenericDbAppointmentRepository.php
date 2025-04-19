<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Core\BoundedContext\AppointmentManagement\Domain\Entities\Appointment;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\SubaccountManagement\Domain\ValueObjects\SubaccountConfig;
use DateTime;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

final class GenericDbAppointmentRepository implements AppointmentRepositoryInterface
{
    private array $configCache = [];

    public function __construct(
        private GetSubaccountConfigService $configService
    ) {}

    public function findById(string $id, string $centerKey): ?Appointment
    {
        $config = $this->getConfig($centerKey);
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $patientMapping = $config->mapping('patients');

        $appointmentTable = $config->tableName('appointments');
        $patientTable = $config->tableName('patients');

        $appointment = $connection->table($appointmentTable)
            ->select([
                "{$appointmentTable}.{$mapping['id']} as id",
                "{$appointmentTable}.{$mapping['scheduled_at']} as scheduled_at",
                "{$appointmentTable}.{$mapping['status']} as status",
                "{$appointmentTable}.{$mapping['notes']} as notes",
                "{$patientTable}.{$patientMapping['id']} as patient_id",
                "{$patientTable}.{$patientMapping['name']} as patient_name",
                "{$patientTable}.{$patientMapping['phone']} as patient_phone",
            ])
            ->join(
                $patientTable,
                "{$appointmentTable}.{$mapping['patient_id']}",
                '=',
                "{$patientTable}.{$patientMapping['id']}"
            )
            ->where("{$appointmentTable}.{$mapping['id']}", $id)
            ->first();

        if ($appointment === null) {
            return null;
        }

        return $this->mapToDomain($appointment, $centerKey);
    }

    public function save(Appointment $appointment): void
    {
        $config = $this->getConfig($appointment->centerKey());
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $table = $config->tableName('appointments');

        $data = [
            $mapping['status'] => $appointment->status()->value,
        ];

        $connection->table($table)
            ->where($mapping['id'], $appointment->id())
            ->update($data);
    }

    public function findPendingInDateRange(string $centerKey, DateTime $startDate, DateTime $endDate): array
    {
        $config = $this->getConfig($centerKey);
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $patientMapping = $config->mapping('patients');

        $appointmentTable = $config->tableName('appointments');
        $patientTable = $config->tableName('patients');

        $results = $connection->table($appointmentTable)
            ->select([
                "{$appointmentTable}.{$mapping['id']} as id",
                "{$appointmentTable}.{$mapping['scheduled_at']} as scheduled_at",
                "{$appointmentTable}.{$mapping['status']} as status",
                "{$appointmentTable}.{$mapping['notes']} as notes",
                "{$patientTable}.{$patientMapping['id']} as patient_id",
                "{$patientTable}.{$patientMapping['name']} as patient_name",
                "{$patientTable}.{$patientMapping['phone']} as patient_phone",
            ])
            ->join(
                $patientTable,
                "{$appointmentTable}.{$mapping['patient_id']}",
                '=',
                "{$patientTable}.{$patientMapping['id']}"
            )
            ->where("{$appointmentTable}.{$mapping['status']}", AppointmentStatus::Pending->value)
            ->whereBetween(
                "{$appointmentTable}.{$mapping['scheduled_at']}",
                [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')]
            )
            ->orderBy("{$appointmentTable}.{$mapping['scheduled_at']}")
            ->get();

        return $results->map(fn($row) => $this->mapToDomain($row, $centerKey))->toArray();
    }

    public function findByStatus(string $centerKey, AppointmentStatus $status, ?DateTime $date = null): array
    {
        $config = $this->getConfig($centerKey);
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $patientMapping = $config->mapping('patients');

        $appointmentTable = $config->tableName('appointments');
        $patientTable = $config->tableName('patients');

        $query = $connection->table($appointmentTable)
            ->select([
                "{$appointmentTable}.{$mapping['id']} as id",
                "{$appointmentTable}.{$mapping['scheduled_at']} as scheduled_at",
                "{$appointmentTable}.{$mapping['status']} as status",
                "{$appointmentTable}.{$mapping['notes']} as notes",
                "{$patientTable}.{$patientMapping['id']} as patient_id",
                "{$patientTable}.{$patientMapping['name']} as patient_name",
                "{$patientTable}.{$patientMapping['phone']} as patient_phone",
            ])
            ->join(
                $patientTable,
                "{$appointmentTable}.{$mapping['patient_id']}",
                '=',
                "{$patientTable}.{$patientMapping['id']}"
            )
            ->where("{$appointmentTable}.{$mapping['status']}", $status->value);

        if ($date !== null) {
            $query->whereDate("{$appointmentTable}.{$mapping['scheduled_at']}", $date->format('Y-m-d'));
        }

        $results = $query->orderBy("{$appointmentTable}.{$mapping['scheduled_at']}")->get();

        return $results->map(fn($row) => $this->mapToDomain($row, $centerKey))->toArray();
    }

    public function countByStatus(string $centerKey, AppointmentStatus $status): int
    {
        $config = $this->getConfig($centerKey);
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $table = $config->tableName('appointments');

        return $connection->table($table)
            ->where($mapping['status'], $status->value)
            ->count();
    }

    public function findScheduledAppointments(): array
    {
        $config = $this->getConfig('default');
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $appointmentTable = $config->tableName('appointments');

        $records = $connection->table($appointmentTable)
            ->where('status', 'scheduled')
            ->get();

        return $records->map(fn($row) => $this->mapToDomain($row, 'default'))->toArray();
    }

    public function findUnconfirmedAppointments(): array
    {
        $config = $this->getConfig('default');
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $appointmentTable = $config->tableName('appointments');

        $records = $connection->table($appointmentTable)
            ->where('status', 'unconfirmed')
            ->get();

        return $records->map(fn($row) => $this->mapToDomain($row, 'default'))->toArray();
    }

    private function getConfig(string $centerKey): SubaccountConfig
    {
        if (!isset($this->configCache[$centerKey])) {
            $this->configCache[$centerKey] = $this->configService->execute($centerKey);
        }

        return $this->configCache[$centerKey];
    }

    private function mapToDomain(object $row, string $centerKey): Appointment
    {
        return Appointment::create(
            $row->id,
            $centerKey,
            $row->patient_id,
            $row->patient_name,
            $row->patient_phone,
            new DateTime($row->scheduled_at),
            AppointmentStatus::fromString($row->status),
            $row->notes ?? null
        );
    }
}
