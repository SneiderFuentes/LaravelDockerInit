<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Application\Commands\ConfirmAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDTO;
use Core\BoundedContext\AppointmentManagement\Domain\Events\AppointmentConfirmed;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentNotFoundException;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Services\ConsecutiveAppointmentService;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use Illuminate\Support\Facades\Event;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\ScheduleConfigRepository;

final class ConfirmAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryInterface $repository,
        private ConsecutiveAppointmentService $consecutiveAppointmentService
    ) {}

    public function handle(ConfirmAppointmentCommand $command): AppointmentDTO
    {
        $mainAppointment = $this->repository->findById($command->appointmentId, $command->centerKey);
        if ($mainAppointment === null) {
            throw new AppointmentNotFoundException($command->appointmentId);
        }

        // Encontrar todas las citas del paciente para ese día
        $allAppointments = $this->repository->findByPatientAndDate(
            $mainAppointment->patientId(),
            $mainAppointment->date()->format('Y-m-d')
        );

        // Encontrar el bloque de citas consecutivas usando el servicio de dominio
        $consecutiveAppointments = $this->consecutiveAppointmentService->findConsecutiveBlock($mainAppointment, $allAppointments);

        // Primero, validar todo el bloque
        foreach ($consecutiveAppointments as $appointment) {
            if ($appointment->status() === AppointmentStatus::Cancelled) {
                throw new \InvalidArgumentException('No puedes confirmar una cita que ya ha sido cancelada.');
            }
        }

        // Si la validación pasa, proceder a confirmar
        foreach ($consecutiveAppointments as $appointment) {
            if ($appointment->status() !== AppointmentStatus::Confirmed) {
                $appointment->confirm($command->confirmationChannelId, $command->confirmationChannelType);
                $this->repository->save($appointment);
            }
        }

        return AppointmentDTO::fromDomain($mainAppointment);
    }
}
