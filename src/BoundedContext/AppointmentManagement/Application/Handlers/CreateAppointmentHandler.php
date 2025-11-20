<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\CupProcedureRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Application\Commands\CreateAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDTO;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\EntityRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\SoatRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleConfigRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\GenericDbAppointmentRepository;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentId;
use Core\BoundedContext\AppointmentManagement\Application\Services\CheckExistingAppointmentService;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentSlotNotAvailableException;
use InvalidArgumentException;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CreateAppointmentHandler
{
    public function __construct(
        private PatientRepositoryInterface $patientRepository,
        private EntityRepositoryInterface $entityRepository,
        private SoatRepositoryInterface $soatRepository,
        private ScheduleConfigRepositoryInterface $scheduleConfigRepository,
        private AppointmentRepositoryInterface $appointmentRepository,
        private CupProcedureRepositoryInterface $cupProcedureRepository
    ) {}

    public function handle(CreateAppointmentCommand $command): array
    {
        if (!preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $command->time)) {
            throw new InvalidArgumentException('Formato de hora inválido. Use HH:mm (ej: 14:30)');
        }

        $appointmentDate = new DateTime($command->date);
        $now = new DateTime();
        if ($appointmentDate < $now) {
            throw new InvalidArgumentException('No se pueden crear citas en fechas pasadas');
        }

        // Validar restricciones para exámenes contrastados
        if ($command->is_contrasted) {
            // Verificar que no sea sábado (6 = Sábado)
            $weekday = (int)date('w', strtotime($command->date));
            if ($weekday === 6) {
                throw new InvalidArgumentException(
                    'Los exámenes contrastados no pueden agendarse los sábados'
                );
            }

            // Verificar que sea antes de las 17:00
            if ($command->time >= '17:00') {
                throw new InvalidArgumentException(
                    'Los exámenes contrastados solo pueden agendarse hasta las 5:00 PM'
                );
            }
        }

        // Logging para procedimientos con sedación
        if ($command->is_sedated) {
            Log::info('Creando cita con sedación', [
                'agendaId' => $command->agendaId,
                'patientId' => $command->patientId
            ]);
        }

        $patient = $this->patientRepository->findById($command->patientId);
        if (!$patient) {
            throw new InvalidArgumentException('Paciente no encontrado');
        }

        // Validar si el paciente ya tiene una cita futura para alguno de los CUPS
        foreach ($command->cups as $cupItem) {
            if ($this->appointmentRepository->hasFutureAppointmentsForCup($command->patientId, $cupItem['code'])) {
                $procedure = $this->cupProcedureRepository->findByCode($cupItem['code']);
                $procedureName = $procedure['name'] ?? $cupItem['code'];
                throw new InvalidArgumentException("Ya tienes una cita futura programada para el procedimiento: {$procedureName}.");
            }
        }

        $currentAppointmentTime = new DateTime($command->time);
        $duracionCita = $this->scheduleConfigRepository->getAppointmentDuration($command->agendaId, $command->doctorId);
        $createdAppointments = [];

        for ($i = 0; $i < $command->espacios; $i++) {
            $fullDateTimeForCheck = $appointmentDate->format('Ymd') . $currentAppointmentTime->format('Hi');

            $exists = $this->appointmentRepository->existsAppointment($command->agendaId, $appointmentDate->format('Y-m-d'), $fullDateTimeForCheck);
            if ($exists) {
                // Si la primera ya existe, es un error. Si es una posterior, es un conflicto de concurrencia.
                $errorTime = $currentAppointmentTime->format('H:i');
                throw new AppointmentSlotNotAvailableException("El espacio de las {$errorTime} ya no está disponible. Por favor, intenta con otro horario.");
            }

            $formattedTimeSlot = $currentAppointmentTime->format('Hi');

            $mainEntityCode = $patient['entity_code'];
            if (!empty($command->cups[0]['client_type'])) {
                $mainEntityCode = ($command->cups[0]['client_type'] === 'individual') ? 'PAR01' : $patient['entity_code'];
            }

            $appointment = $this->appointmentRepository->create(
                $command->doctorId,
                $command->patientId,
                $appointmentDate,
                $formattedTimeSlot,
                $mainEntityCode,
                $command->agendaId,
                $command->is_contrasted,
                $command->is_sedated
            );

            if ($i === 0) {
                foreach ($command->cups as $cupItem) {
                    $cupCode = $cupItem['code'] ?? null;
                    if (!$cupCode) continue;

                    $precio = (float)($cupItem['value'] ?? 0.0);
                    $clientType = $cupItem['client_type'] ?? 'individual';
                    $entityCode = ($clientType === 'individual') ? 'PAR01' : $patient['entity_code'];

                    $procedure = $this->cupProcedureRepository->findByCode($cupCode);
                    if (!$procedure) continue;

                    $this->appointmentRepository->createPxcita([
                        'appointment_id' => $appointment->id(),
                        'cup_code' => $cupCode,
                        'precio' => $precio,
                        'servicio_id' => $procedure['service_id'],
                        'cantidad' => (int)($cupItem['cantidad'] ?? 1),
                        'entity_code' => $entityCode,
                    ]);
                }
            }
            $createdAppointments[] = $appointment;

            // Incrementar la hora para la siguiente cita en el bucle
            $currentAppointmentTime->modify("+{$duracionCita} minutes");
        }

        $result = array_map(fn($apt) => AppointmentDTO::fromDomain($apt)->toArray(), $createdAppointments);

        if (!empty($result) && !empty($command->cups)) {
            $allProceduresData = [];
            foreach ($command->cups as $cupItem) {
                if (!empty($cupItem['code'])) {
                    $cupData = $this->cupProcedureRepository->findByCode($cupItem['code']);
                    if ($cupData) {
                        $allProceduresData[] = $cupData;
                    }
                }
            }
            $result[0]['cup_procedure'] = $allProceduresData;
        }

        return $result;
    }
}
