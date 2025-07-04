<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

use Core\BoundedContext\AppointmentManagement\Domain\Entities\Appointment;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\ConfirmationChannelType;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\SubaccountManagement\Domain\ValueObjects\SubaccountConfig;
use DateTime;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\BaseRepository;
use Carbon\Carbon;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\ScheduleConfigRepository;

final class GenericDbAppointmentRepository extends BaseRepository implements AppointmentRepositoryInterface
{
    private array $connectionCache = [];
    private const CENTER_KEY = 'datosipsndx';

    public function __construct(
        GetSubaccountConfigService $configService,
        private ScheduleConfigRepository $scheduleConfigRepository
    ) {
        parent::__construct($configService);
    }

    /**
     * Get connection for the specific table
     */
    private function getConnection(string $centerKey, string $tableName): ConnectionInterface
    {
        $config = $this->getConfig($centerKey);

        // Check if table has specific connection defined
        $tableConfig = $config->tableConfig($tableName);
        $connectionKey = $tableConfig['connection'] ?? 'default';

        // Get connection name from config
        $connections = $config->connections();
        $connectionName = $connections[$connectionKey] ?? $config->connection();

        $cacheKey = "{$centerKey}_{$connectionName}";

        if (!isset($this->connectionCache[$cacheKey])) {
            $this->connectionCache[$cacheKey] = DB::connection($connectionName);
        }

        return $this->connectionCache[$cacheKey];
    }

    public function findById(string $id, ?string $centerKey = self::CENTER_KEY): ?Appointment
    {
        $config = $this->getConfig($centerKey);
        $mapping = $config->mapping('appointments');
        $patientMapping = $config->mapping('patients');

        $appointmentTable = $config->tableName('appointments');
        $patientTable = $config->tableName('patients');

        // Usar la conexión apropiada para la tabla de citas
        $connection = $this->getConnection($centerKey, 'appointments');
        $appointment = $connection->table($appointmentTable)
            ->select([
                "{$appointmentTable}.{$mapping['id']} as id",
                "{$appointmentTable}.{$mapping['date']} as date",
                "{$appointmentTable}.{$mapping['time_slot']} as time_slot",
                "{$appointmentTable}.{$mapping['doctor_id']} as doctor_document",
                "{$appointmentTable}.{$mapping['confirmed']} as confirmed",
                "{$appointmentTable}.{$mapping['canceled']} as canceled",
                "{$appointmentTable}.{$mapping['entity']} as entity",
                "{$appointmentTable}.{$mapping['confirmation_date']} as confirmation_date",
                "{$appointmentTable}.{$mapping['cancel_date']} as cancel_date",
                "{$appointmentTable}.{$mapping['confirmation_channel']} as confirmation_channel",
                "{$appointmentTable}.{$mapping['confirmation_channel_id']} as confirmation_channel_id",
                "{$appointmentTable}.{$mapping['agenda_id']} as agenda_id",
                "{$patientTable}.{$patientMapping['id']} as patient_id",
                "{$patientTable}.{$patientMapping['full_name']} as patient_name",
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

        return $this->mapToDomainWithDetails($appointment, $config);
    }

    /**
     * Obtiene información del médico desde la base de datos de médicos
     */
    private function getDoctorInfo(string $centerKey, string $doctorId): ?object
    {
        try {
            $config = $this->getConfig($centerKey);
            $doctorMapping = $config->mapping('doctors');
            $doctorTable = $config->tableName('doctors');

            // Usar conexión específica para médicos
            $connection = $this->getConnection($centerKey, 'doctors');

            return $connection->table($doctorTable)
                ->where($doctorMapping['id'], $doctorId)
                ->first();
        } catch (\Exception $e) {
            Log::error("Error al obtener información del médico: " . $e->getMessage(), [
                'doctorId' => $doctorId,
                'centerKey' => $centerKey
            ]);
            return null;
        }
    }

    public function save(Appointment $appointment): void
    {
        $config = $this->getConfig(self::CENTER_KEY);
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $table = $config->tableName('appointments');

        $data = [
            $mapping['confirmed'] => $appointment->status() === AppointmentStatus::Confirmed ? -1 : 0,
            $mapping['canceled'] => $appointment->status() === AppointmentStatus::Cancelled ? -1 : 0,
            $mapping['confirmation_date'] => $appointment->status() === AppointmentStatus::Confirmed ? now() : null,
            $mapping['cancel_date'] => $appointment->status() === AppointmentStatus::Cancelled ? now() : null,
            $mapping['confirmation_channel'] => $appointment->confirmationChannelType(),
            $mapping['confirmation_channel_id'] => $appointment->confirmationChannelId()
        ];

        $connection->table($table)
            ->where($mapping['id'], $appointment->id())
            ->update($data);
    }

    public function create(
        string $doctorId,
        string $patientId,
        DateTime $appointmentDate,
        string $timeSlot,
        string $entity,
        int $agendaId,
        ?int $cupId = null
    ): Appointment {
        $config = $this->getConfig(self::CENTER_KEY);
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $table = $config->tableName('appointments');

        // Validar y armar el timeSlot como YYYYMMDDHHii
        if (preg_match('/^\d{12}$/', $timeSlot)) {
            $formattedTimeSlot = $timeSlot;
        } else {
            $formattedTimeSlot = $appointmentDate->format('Ymd') . preg_replace('/[^0-9]/', '', $timeSlot);
        }

        $data = [
            $mapping['request_date'] => now(),
            $mapping['date'] => $appointmentDate->format('Y-m-d'),
            $mapping['time_slot'] => $formattedTimeSlot,
            $mapping['doctor_id'] => $doctorId,
            $mapping['patient_id'] => $patientId,
            $mapping['entity'] => $entity,
            $mapping['agenda_id'] => $agendaId,
            $mapping['user_request_date'] => $appointmentDate->format('Y-m-d'),
            $mapping['canceled'] => 0,
            $mapping['confirmed'] => 0,
            $mapping['created_by'] => 0
        ];

        if ($cupId !== null) {
            $data[$mapping['cup_id']] = $cupId;
        }

        $id = $connection->table($table)->insertGetId($data);

        return $this->findById((string)$id, self::CENTER_KEY);
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
                "{$appointmentTable}.{$mapping['date']} as date",
                "{$appointmentTable}.{$mapping['time_slot']} as time_slot",
                "{$appointmentTable}.{$mapping['doctor_id']} as doctor_document",
                "{$appointmentTable}.{$mapping['confirmed']} as confirmed",
                "{$appointmentTable}.{$mapping['canceled']} as canceled",
                "{$appointmentTable}.{$mapping['entity']} as entity",
                "{$appointmentTable}.{$mapping['confirmation_date']} as confirmation_date",
                "{$appointmentTable}.{$mapping['cancel_date']} as cancel_date",
                "{$appointmentTable}.{$mapping['confirmation_channel']} as confirmation_channel",
                "{$appointmentTable}.{$mapping['confirmation_channel_id']} as confirmation_channel_id",
                "{$appointmentTable}.{$mapping['agenda_id']} as agenda_id",
                "{$patientTable}.{$patientMapping['id']} as patient_id",
                "{$patientTable}.{$patientMapping['full_name']} as patient_name",
                "{$patientTable}.{$patientMapping['phone']} as patient_phone",
            ])
            ->join(
                $patientTable,
                "{$appointmentTable}.{$mapping['patient_id']}",
                '=',
                "{$patientTable}.{$patientMapping['id']}"
            )
            ->where("{$appointmentTable}.{$mapping['confirmed']}", 0)
            ->where("{$appointmentTable}.{$mapping['canceled']}", 0)
            ->whereBetween(
                "{$appointmentTable}.{$mapping['date']}",
                [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
            )
            ->orderBy("{$appointmentTable}.{$mapping['date']}")
            ->orderBy("{$appointmentTable}.{$mapping['time_slot']}")
            ->get();

        return $results->map(fn($row) => $this->mapToDomainWithDetails($row, $config))->toArray();
    }

    private function mapToDomainWithDetails(object $row, $config): Appointment
    {
        // Consulta y mapeo de doctor
        $doctorData = null;
        if (!empty($row->doctor_document)) {
            $doctorTable = $config->tables()['cup_medico']['table'];
            $doctorMapping = $config->tables()['cup_medico']['mapping'];
            $doctorConnection = $config->connection();
            $doctor = DB::connection($doctorConnection)
                ->table($doctorTable)
                ->where($doctorMapping['doctor_document'], $row->doctor_document)
                ->first();
            if ($doctor) {
                $doctorData = [
                    'full_name' => $doctor->{$doctorMapping['doctor_full_name']},
                    'document_number' => $doctor->{$doctorMapping['doctor_document']},
                ];
            }
        }

        $cupData = [];
        $pxcitaTable = $config->tables()['pxcita']['table'];
        $pxcitaMapping = $config->tables()['pxcita']['mapping'];
        $pxcitas = DB::connection($config->connection())
            ->table($pxcitaTable)
            ->where($pxcitaMapping['appointment_id'], $row->id)
            ->get();

        foreach ($pxcitas as $pxcita) {
            if ($pxcita && !empty($pxcita->{$pxcitaMapping['cup_code']})) {
                $cupCode = $pxcita->{$pxcitaMapping['cup_code']};
                // Buscar el cup en cups_procedimientos por codigo_cups
                $procedureTable = $config->tables()['procedures']['table'];
                $procedureMapping = $config->tables()['procedures']['mapping'];
                $cup = DB::connection($config->connection())
                    ->table($procedureTable)
                    ->where($procedureMapping['code'], $cupCode)
                    ->first();
                if ($cup) {
                    $cupData[] = [
                        'name' => $cup->{$procedureMapping['name']},
                        'description' => $cup->{$procedureMapping['description']},
                        'preparation' => $cup->{$procedureMapping['preparation']},
                        'address' => $cup->{$procedureMapping['address']},
                        'video_url' => $cup->{$procedureMapping['video_url']},
                        'audio_url' => $cup->{$procedureMapping['audio_url']},
                    ];
                }
            }
        }

        // Ahora puedes crear la entidad/DTO con los datos enriquecidos
        return Appointment::createWithDetails($row, $doctorData, $cupData);
    }

    public function findByStatus(string $centerKey, AppointmentStatus $status, ?DateTime $date = null): array
    {
        $config = $this->getConfig(self::CENTER_KEY);
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $patientMapping = $config->mapping('patients');

        $appointmentTable = $config->tableName('appointments');
        $patientTable = $config->tableName('patients');

        $query = $connection->table($appointmentTable)
            ->select([
                "{$appointmentTable}.{$mapping['id']} as id",
                "{$appointmentTable}.{$mapping['date']} as date",
                "{$appointmentTable}.{$mapping['time_slot']} as time_slot",
                "{$appointmentTable}.{$mapping['doctor_id']} as doctor_id",
                "{$appointmentTable}.{$mapping['confirmed']} as confirmed",
                "{$appointmentTable}.{$mapping['canceled']} as canceled",
                "{$appointmentTable}.{$mapping['entity']} as entity",
                "{$appointmentTable}.{$mapping['confirmation_date']} as confirmation_date",
                "{$appointmentTable}.{$mapping['cancel_date']} as cancel_date",
                "{$appointmentTable}.{$mapping['confirmation_channel']} as confirmation_channel",
                "{$appointmentTable}.{$mapping['confirmation_channel_id']} as confirmation_channel_id",
                "{$appointmentTable}.{$mapping['agenda_id']} as agenda_id",
                "{$patientTable}.{$patientMapping['id']} as patient_id",
                "{$patientTable}.{$patientMapping['full_name']} as patient_name",
                "{$patientTable}.{$patientMapping['phone']} as patient_phone",
            ])
            ->join(
                $patientTable,
                "{$appointmentTable}.{$mapping['patient_id']}",
                '=',
                "{$patientTable}.{$patientMapping['id']}"
            );

        // Aplicar filtros según el estado
        switch ($status) {
            case AppointmentStatus::Confirmed:
                $query->where("{$appointmentTable}.{$mapping['confirmed']}", -1);
                break;
            case AppointmentStatus::Cancelled:
                $query->where("{$appointmentTable}.{$mapping['canceled']}", -1);
                break;
            case AppointmentStatus::Pending:
                $query->where("{$appointmentTable}.{$mapping['confirmed']}", 0)
                    ->where("{$appointmentTable}.{$mapping['canceled']}", 0);
                break;
        }

        if ($date !== null) {
            $query->whereDate("{$appointmentTable}.{$mapping['date']}", $date->format('Y-m-d'));
        }

        $results = $query->orderBy("{$appointmentTable}.{$mapping['date']}")
            ->orderBy("{$appointmentTable}.{$mapping['time_slot']}")
            ->get();

        return $results->map(fn($row) => $this->mapToDomain($row))->toArray();
    }

    public function countByStatus(string $centerKey, AppointmentStatus $status): int
    {
        $config = $this->getConfig(self::CENTER_KEY);
        $connection = DB::connection($config->connection());

        $mapping = $config->mapping('appointments');
        $table = $config->tableName('appointments');

        $query = $connection->table($table);

        switch ($status) {
            case AppointmentStatus::Confirmed:
                $query->where($mapping['confirmed'], -1);
                break;
            case AppointmentStatus::Cancelled:
                $query->where($mapping['canceled'], -1);
                break;
            case AppointmentStatus::Pending:
                $query->where($mapping['confirmed'], 0)
                    ->where($mapping['canceled'], 0);
                break;
        }

        return $query->count();
    }

    public function findScheduledAppointments(): array
    {
        return $this->findByStatus(self::CENTER_KEY, AppointmentStatus::Pending);
    }

    public function findUnconfirmedAppointments(): array
    {
        return $this->findByStatus(self::CENTER_KEY, AppointmentStatus::Pending);
    }

    private function mapToDomain(object $row, ?object $doctorInfo = null): Appointment
    {
        $status = AppointmentStatus::Pending;
        if ($row->confirmed == -1) {
            $status = AppointmentStatus::Confirmed;
        } elseif ($row->canceled == -1) {
            $status = AppointmentStatus::Cancelled;
        }

        // Convertir FechaCita (YYYYMMDDHHMM) a DateTime
        $dateTime = DateTime::createFromFormat('Y-m-d', $row->date);
        if ($row->time_slot) {
            $hour = substr($row->time_slot, -4, 2);
            $minute = substr($row->time_slot, -2);
            $dateTime->setTime((int)$hour, (int)$minute);
        }

        $confirmationDate = $row->confirmation_date ? new DateTime($row->confirmation_date) : null;
        $cancellationDate = $row->cancel_date ? new DateTime($row->cancel_date) : null;
        $channelType = $row->confirmation_channel ? ConfirmationChannelType::from($row->confirmation_channel) : null;

        return Appointment::create(
            (string)$row->id,
            (string)$row->patient_id,
            (string)$row->patient_name,
            (string)$row->patient_phone,
            (string)$row->doctor_id,
            $dateTime,
            $row->time_slot,
            $row->entity,
            (int)$row->agenda_id,
            $status,
            $confirmationDate,
            $cancellationDate,
            null, // cancellation_reason
            $row->confirmation_channel_id ?? null,
            $channelType,
            isset($row->cup_id) ? (int)$row->cup_id : null
        );
    }

    public function listAppointmentsWithDetails($config, $appointments)
    {
        $procedureTable = $config->tables()['procedures']['table'];
        $procedureMapping = $config->tables()['procedures']['mapping'];
        $doctorConfig = $config->tables()['doctors'];
        $doctorTable = $doctorConfig['table'];
        $doctorMapping = $doctorConfig['mapping'];
        $doctorConnection = $doctorConfig['connection'] ?? 'default';

        return collect($appointments)->map(function ($row) use ($procedureTable, $procedureMapping, $doctorTable, $doctorMapping, $doctorConnection, $config) {
            // Datos del CUP
            $cupData = [];
            $pxcitaTable = $config->tables()['pxcita']['table'];
            $pxcitaMapping = $config->tables()['pxcita']['mapping'];
            $pxcitas = DB::connection($config->connection())
                ->table($pxcitaTable)
                ->where($pxcitaMapping['appointment_id'], $row->id)
                ->get();

            foreach ($pxcitas as $pxcita) {
                if ($pxcita && !empty($pxcita->{$pxcitaMapping['cup_code']})) {
                    $cupCode = $pxcita->{$pxcitaMapping['cup_code']};
                    // Buscar el cup en cups_procedimientos por codigo_cups
                    $procedureTable = $config->tables()['procedures']['table'];
                    $procedureMapping = $config->tables()['procedures']['mapping'];
                    $cup = DB::connection($config->connection())
                        ->table($procedureTable)
                        ->where($procedureMapping['code'], $cupCode)
                        ->first();
                    if ($cup) {
                        $cupData[] = [
                            'name' => $cup->{$procedureMapping['name']},
                            'description' => $cup->{$procedureMapping['description']},
                            'preparation' => $cup->{$procedureMapping['preparation']},
                            'address' => $cup->{$procedureMapping['address']},
                            'video_url' => $cup->{$procedureMapping['video_url']},
                            'audio_url' => $cup->{$procedureMapping['audio_url']},
                        ];
                    }
                }
            }
            // Datos del doctor
            $doctorData = null;
            if (!empty($row->doctor_document)) {
                $doctor = DB::connection($doctorConnection)
                    ->table($doctorTable)
                    ->where($doctorMapping['doctor_document'], $row->doctor_document)
                    ->first();
                if ($doctor) {
                    $doctorData = [
                        'full_name' => $doctor->{$doctorMapping['doctor_full_name']},
                        'document_number' => $doctor->{$doctorMapping['doctor_document']},
                    ];
                }
            }
            // Puedes ahora pasar $cupData y $doctorData a tu mapToDomain
            return $this->mapToDomain($row, $doctorData, $cupData);
        })->toArray();
    }

    /**
     * Verifica si ya existe una cita para la misma agenda, fecha y hora
     */
    public function existsAppointment(int $agendaId, string $date, string $timeSlot): bool
    {
        $config = $this->getConfig(self::CENTER_KEY);
        $connection = DB::connection($config->connection());
        $mapping = $config->mapping('appointments');
        $table = $config->tableName('appointments');
        $count = $connection->table($table)
            ->where($mapping['agenda_id'], $agendaId)
            ->where($mapping['date'], $date)
            ->where($mapping['time_slot'], $timeSlot)
            ->where($mapping['canceled'], 0)
            ->count();
        return $count > 0;
    }

    /**
     * Inserta un registro en la tabla pxcita
     */
    public function createPxcita(array $data): void
    {
        $config = $this->getConfig(self::CENTER_KEY);
        $connection = DB::connection($config->connection());
        $pxcitaTable = $config->tables()['pxcita']['table'];
        $pxcitaMapping = $config->tables()['pxcita']['mapping'];
        $insertData = [
            $pxcitaMapping['appointment_id'] => $data['appointment_id'],
            $pxcitaMapping['cup_code'] => $data['cup_code'],
            $pxcitaMapping['unit_value'] => $data['precio'],
            $pxcitaMapping['service_id'] => $data['servicio_id'],
        ];
        if (isset($data['cantidad'])) {
            $insertData[$pxcitaMapping['quantity']] = $data['cantidad'];
        }
        $connection->table($pxcitaTable)->insert($insertData);
    }

    public function findByAgendaAndDate(int|string $agendaId, string $date): array
    {
        $config = $this->getConfig(self::CENTER_KEY);
        $connection = DB::connection($config->connection());
        $mapping = $config->mapping('appointments');
        $table = $config->tableName('appointments');
        $results = $connection->table($table)
            ->where($mapping['agenda_id'], $agendaId)
            ->where($mapping['date'], $date)
            ->where($mapping['canceled'], 0)
            ->get();
        return $results->map(fn($row) => [
            'id' => $row->{$mapping['id']},
            'time_slot' => substr($row->{$mapping['time_slot']}, -4, 2) . ':' . substr($row->{$mapping['time_slot']}, -2, 2),
        ])->toArray();
    }

    public function findByPatientAndDate(int|string $patientId, string $date): array
    {
        $config = $this->getConfig(self::CENTER_KEY);
        $connection = DB::connection($config->connection());
        $mapping = $config->mapping('appointments');
        $patientMapping = $config->mapping('patients');
        $appointmentTable = $config->tableName('appointments');
        $patientTable = $config->tableName('patients');

        $results = $connection->table($appointmentTable)
            ->select([
                "{$appointmentTable}.{$mapping['id']} as id",
                "{$appointmentTable}.{$mapping['date']} as date",
                "{$appointmentTable}.{$mapping['time_slot']} as time_slot",
                "{$appointmentTable}.{$mapping['doctor_id']} as doctor_document",
                "{$appointmentTable}.{$mapping['confirmed']} as confirmed",
                "{$appointmentTable}.{$mapping['canceled']} as canceled",
                "{$appointmentTable}.{$mapping['entity']} as entity",
                "{$appointmentTable}.{$mapping['confirmation_date']} as confirmation_date",
                "{$appointmentTable}.{$mapping['cancel_date']} as cancel_date",
                "{$appointmentTable}.{$mapping['confirmation_channel']} as confirmation_channel",
                "{$appointmentTable}.{$mapping['confirmation_channel_id']} as confirmation_channel_id",
                "{$appointmentTable}.{$mapping['agenda_id']} as agenda_id",
                "{$patientTable}.{$patientMapping['id']} as patient_id",
                "{$patientTable}.{$patientMapping['full_name']} as patient_name",
                "{$patientTable}.{$patientMapping['phone']} as patient_phone",
            ])
            ->join(
                $patientTable,
                "{$appointmentTable}.{$mapping['patient_id']}",
                '=',
                "{$patientTable}.{$patientMapping['id']}"
            )
            ->where("{$appointmentTable}.{$mapping['patient_id']}", $patientId)
            ->where("{$appointmentTable}.{$mapping['canceled']}", 0)
            ->where("{$appointmentTable}.{$mapping['date']}", '>=', $date)
            ->orderBy("{$appointmentTable}.{$mapping['date']}")
            ->orderBy("{$appointmentTable}.{$mapping['time_slot']}")
            ->get();

        return $results->map(fn($row) => $this->mapToDomainWithDetails($row, $config))->toArray();
    }

    public function findConsecutiveAppointments(Appointment $mainAppointment, array $candidateAppointments): array
    {
        $consecutiveBlock = [];
        $duration = $this->scheduleConfigRepository->getAppointmentDuration($mainAppointment->agendaId());

        // Primero, encontrar el inicio del bloque
        $blockStartAppointment = $mainAppointment;
        while (true) {
            $previous = $this->findPreviousAppointmentInList($blockStartAppointment, $candidateAppointments, $duration);
            if ($previous) {
                $blockStartAppointment = $previous;
            } else {
                break;
            }
        }

        // Ahora, desde el inicio, encontrar todas las citas consecutivas hacia adelante
        $consecutiveBlock[] = $blockStartAppointment;
        $current = $blockStartAppointment;
        while (true) {
            $next = $this->findNextAppointmentInList($current, $candidateAppointments, $duration);
            if ($next) {
                $consecutiveBlock[] = $next;
                $current = $next;
            } else {
                break;
            }
        }

        return $consecutiveBlock;
    }

    private function findNextAppointmentInList(Appointment $current, array $appointments, int $duration): ?Appointment
    {
        $formattedTimeSlot = substr($current->timeSlot(), -4, 2) . ':' . substr($current->timeSlot(), -2) . ':00';
        $currentTime = Carbon::createFromFormat('Y-m-d H:i:s', $current->date()->format('Y-m-d') . ' ' . $formattedTimeSlot);
        $expectedNextTime = $currentTime->copy()->addMinutes($duration);

        foreach ($appointments as $next) {
            $nextFormattedTimeSlot = substr($next->timeSlot(), -4, 2) . ':' . substr($next->timeSlot(), -2) . ':00';
            $nextTime = Carbon::createFromFormat('Y-m-d H:i:s', $next->date()->format('Y-m-d') . ' ' . $nextFormattedTimeSlot);
            if ($current->doctorId() === $next->doctorId() && $nextTime->equalTo($expectedNextTime)) {
                return $next;
            }
        }
        return null;
    }

    private function findPreviousAppointmentInList(Appointment $current, array $appointments, int $duration): ?Appointment
    {
        $formattedTimeSlot = substr($current->timeSlot(), -4, 2) . ':' . substr($current->timeSlot(), -2) . ':00';
        $currentTime = Carbon::createFromFormat('Y-m-d H:i:s', $current->date()->format('Y-m-d') . ' ' . $formattedTimeSlot);
        $expectedPreviousTime = $currentTime->copy()->subMinutes($duration);

        foreach ($appointments as $previous) {
            $previousFormattedTimeSlot = substr($previous->timeSlot(), -4, 2) . ':' . substr($previous->timeSlot(), -2) . ':00';
            $previousTime = Carbon::createFromFormat('Y-m-d H:i:s', $previous->date()->format('Y-m-d') . ' ' . $previousFormattedTimeSlot);
            if ($current->doctorId() === $previous->doctorId() && $previousTime->equalTo($expectedPreviousTime)) {
                return $previous;
            }
        }
        return null;
    }

    public function hasFutureAppointmentsForCup(string $patientId, string $cupCode): bool
    {
        $config = $this->getConfig(self::CENTER_KEY);
        $connection = DB::connection($config->connection());

        $appointmentsTable = $config->tableName('appointments');
        $appointmentsMapping = $config->mapping('appointments');

        $pxcitaTable = $config->tables()['pxcita']['table'];
        $pxcitaMapping = $config->tables()['pxcita']['mapping'];

        $today = now()->format('Y-m-d');

        $count = $connection->table($appointmentsTable)
            ->join($pxcitaTable, "{$appointmentsTable}.{$appointmentsMapping['id']}", '=', "{$pxcitaTable}.{$pxcitaMapping['appointment_id']}")
            ->where("{$appointmentsTable}.{$appointmentsMapping['patient_id']}", $patientId)
            ->where("{$pxcitaTable}.{$pxcitaMapping['cup_code']}", $cupCode)
            ->where("{$appointmentsTable}.{$appointmentsMapping['date']}", '>=', $today)
            ->where("{$appointmentsTable}.{$appointmentsMapping['canceled']}", 0)
            ->count();

        return $count > 0;
    }
}
