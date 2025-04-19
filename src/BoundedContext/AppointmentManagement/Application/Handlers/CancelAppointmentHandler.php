<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Handlers;

use Core\BoundedContext\AppointmentManagement\Application\Commands\CancelAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDto;
use Core\BoundedContext\AppointmentManagement\Domain\Events\AppointmentCancelled;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentNotFoundException;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Illuminate\Support\Facades\Event;

final class CancelAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryInterface $repository
    ) {}

    public function handle(CancelAppointmentCommand $command): AppointmentDto
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

        $cancelledAppointment = $appointment->cancel();

        $this->repository->save($cancelledAppointment);

        // Disparar evento de dominio
        Event::dispatch(new AppointmentCancelled(
            $cancelledAppointment->id(),
            $cancelledAppointment->centerKey(),
            $cancelledAppointment->patientPhone(),
            $cancelledAppointment->scheduledAt()->format('Y-m-d H:i:s'),
            $command->reason()
        ));

        return AppointmentDto::fromEntity($cancelledAppointment);
    }
}
