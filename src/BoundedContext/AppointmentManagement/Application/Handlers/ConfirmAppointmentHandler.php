<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Application\Commands\ConfirmAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDto;
use Core\BoundedContext\AppointmentManagement\Domain\Events\AppointmentConfirmed;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentNotFoundException;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Illuminate\Support\Facades\Event;

final class ConfirmAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryInterface $repository
    ) {}

    public function handle(ConfirmAppointmentCommand $command): AppointmentDto
    {
        $appointment = $this->repository->findById(
            $command->appointmentId(),
            $command->centerKey()
        );

        if ($appointment === null) {
            throw AppointmentNotFoundException::withId(
                $command->appointmentId(),
                $command->centerKey()
            );
        }

        $confirmedAppointment = $appointment->confirm();

        $this->repository->save($confirmedAppointment);

        // Disparar evento de dominio
        Event::dispatch(new AppointmentConfirmed(
            $confirmedAppointment->id(),
            $confirmedAppointment->centerKey(),
            $confirmedAppointment->patientPhone(),
            $confirmedAppointment->scheduledAt()->format('Y-m-d H:i:s')
        ));

        return AppointmentDto::fromEntity($confirmedAppointment);
    }
}
