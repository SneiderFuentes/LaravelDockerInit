<?php

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Adapters;

use Core\BoundedContext\AppointmentManagement\Domain\Entities\Appointment;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentId;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use Illuminate\Database\ConnectionInterface;
use DateTime;

class GenericDbAppointmentRepository implements AppointmentRepositoryInterface
{
    private ConnectionInterface $connection;
    private string $table = 'appointments';

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function findById(string $id, string $centerKey): ?Appointment
    {
        $record = $this->connection->table($this->table)
            ->where('id', $id)
            ->where('center_key', $centerKey)
            ->first();

        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function save(Appointment $appointment): void
    {
        $exists = $this->connection->table($this->table)
            ->where('id', $appointment->id())
            ->where('center_key', $appointment->centerKey())
            ->exists();

        $data = [
            'patient_name' => $appointment->patientName(),
            'patient_phone' => $appointment->patientPhone(),
            'scheduled_at' => $appointment->scheduledAt()->format('Y-m-d H:i:s'),
            'status' => $appointment->status(),
            'center_key' => $appointment->centerKey(),
            'updated_at' => now()
        ];

        if ($exists) {
            $this->connection->table($this->table)
                ->where('id', $appointment->id())
                ->where('center_key', $appointment->centerKey())
                ->update($data);
        } else {
            $this->connection->table($this->table)->insert(array_merge(
                $data,
                [
                    'id' => $appointment->id(),
                    'created_at' => now()
                ]
            ));
        }
    }

    public function delete(AppointmentId $id): void
    {
        $this->connection->table($this->table)
            ->where('id', $id->value())
            ->delete();
    }

    public function findAll(): array
    {
        $records = $this->connection->table($this->table)->get();

        return $records->map(function ($record) {
            return $this->mapToEntity($record);
        })->toArray();
    }

    public function findPending(): array
    {
        $records = $this->connection->table($this->table)
            ->where('status', 'pending')
            ->get();

        return $records->map(function ($record) {
            return $this->mapToEntity($record);
        })->toArray();
    }

    public function findPendingInDateRange(
        string $centerKey,
        DateTime $startDate,
        DateTime $endDate
    ): array {
        $records = $this->connection->table($this->table)
            ->where('center_key', $centerKey)
            ->where('status', 'pending')
            ->whereBetween('scheduled_at', [
                $startDate->format('Y-m-d H:i:s'),
                $endDate->format('Y-m-d H:i:s')
            ])
            ->get();

        return $records->map(function ($record) {
            return $this->mapToEntity($record);
        })->toArray();
    }

    public function findByStatus(
        string $centerKey,
        AppointmentStatus $status,
        ?DateTime $date = null
    ): array {
        $query = $this->connection->table($this->table)
            ->where('center_key', $centerKey)
            ->where('status', $status);

        if ($date) {
            $startOfDay = (clone $date)->setTime(0, 0, 0);
            $endOfDay = (clone $date)->setTime(23, 59, 59);

            $query->whereBetween('scheduled_at', [
                $startOfDay->format('Y-m-d H:i:s'),
                $endOfDay->format('Y-m-d H:i:s')
            ]);
        }

        $records = $query->get();

        return $records->map(function ($record) {
            return $this->mapToEntity($record);
        })->toArray();
    }

    public function countByStatus(string $centerKey, AppointmentStatus $status): int
    {
        return $this->connection->table($this->table)
            ->where('center_key', $centerKey)
            ->where('status', $status)
            ->count();
    }

    private function mapToEntity($record): Appointment
    {
        return Appointment::create(
            $record->id,
            $record->center_key,
            $record->patient_id,
            $record->patient_name,
            $record->patient_phone,
            new DateTime($record->scheduled_at),
            $this->createStatusFromValue($record->status),
            $record->notes ?? null
        );
    }

    private function createStatusFromValue(string $status): AppointmentStatus
    {
        $statusMethods = [
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'cancelled' => 'cancelled',
            'completed' => 'completed'
        ];

        $method = $statusMethods[$status] ?? 'pending';
        return AppointmentStatus::$method();
    }
}
