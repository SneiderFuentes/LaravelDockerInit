<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Core\Jobs\SendRescheduleNotifications;

class RescheduleAgendaController
{
    public function __construct(
        private ScheduleRepositoryInterface $scheduleRepository,
        private AppointmentRepositoryInterface $appointmentRepository
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agenda_id' => 'required|integer',
            'doctor_document' => 'required|string',
            'current_date' => 'required|date',
            'new_date' => 'required|date|after:current_date',
            'new_agenda_id' => 'nullable|integer',
            'notify_patients' => 'required|boolean',
        ]);

        $agendaId = $validated['agenda_id'];
        $doctorDocument = $validated['doctor_document'];
        $currentDate = $validated['current_date'];
        $newDate = $validated['new_date'];
        $newAgendaId = $validated['new_agenda_id'] ?? null;
        $notifyPatients = $validated['notify_patients'];

        if ($newAgendaId) {
            return $this->handleRescheduleWithNewAgenda(
                $agendaId,
                $newAgendaId,
                $doctorDocument,
                $currentDate,
                $newDate,
                $notifyPatients
            );
        }

        return $this->handleRescheduleSameAgenda(
            $agendaId,
            $doctorDocument,
            $currentDate,
            $newDate,
            $notifyPatients
        );
    }

    private function handleRescheduleWithNewAgenda(
        int $oldAgendaId,
        int $newAgendaId,
        string $doctorDocument,
        string $currentDate,
        string $newDate,
        bool $notifyPatients
    ): JsonResponse {
        // 1. Validar que la nueva agenda exista
        $newAgenda = $this->scheduleRepository->findByScheduleId($newAgendaId);
        if (!$newAgenda) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nueva agenda no encontrada'
            ], 404);
        }

        // 2. Validar que exista la excepción de días (working_days) para ese doctor, agenda y fecha nueva
        $workingDayException = $this->scheduleRepository->findWorkingDayException($newAgendaId, $doctorDocument, $newDate);
        if (!$workingDayException) {
            return response()->json([
                'status' => 'error',
                'message' => 'No existe disponibilidad para ese doctor en esa agenda en la fecha nueva'
            ], 404);
        }

        // 3. Cancelar citas de la agenda anterior
        $cancelledCount = $this->appointmentRepository->cancelAppointmentsByAgendaAndDate(
            $oldAgendaId,
            $doctorDocument,
            $currentDate
        );

        // 4. Eliminar registro de excepción de días
        $deletedWorkingDay = $this->scheduleRepository->deleteWorkingDayException(
            $oldAgendaId,
            $doctorDocument,
            $currentDate
        );

        Log::info('Agenda rescheduled successfully', [
            'old_agenda_id' => $oldAgendaId,
            'new_agenda_id' => $newAgendaId,
            'doctor_document' => $doctorDocument,
            'current_date' => $currentDate,
            'new_date' => $newDate,
            'cancelled_appointments' => $cancelledCount,
            'working_day_deleted' => $deletedWorkingDay,
            'notify_patients' => $notifyPatients
        ]);

        // Notificar a pacientes si se solicita
        if ($notifyPatients) {
            SendRescheduleNotifications::dispatch(
                $newAgendaId,
                $doctorDocument,
                $newDate,
                $currentDate
            )->delay(now()->addSeconds(5));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Agenda reprogramada exitosamente',
            'data' => [
                'cancelled_appointments' => $cancelledCount,
                'working_day_exception_deleted' => $deletedWorkingDay,
                'notify_patients' => $notifyPatients
            ]
        ], 200);
    }

    private function handleRescheduleSameAgenda(
        int $agendaId,
        string $doctorDocument,
        string $currentDate,
        string $newDate,
        bool $notifyPatients
    ): JsonResponse {
        // 1. Validar que exista el registro en working_days para la fecha actual
        $currentWorkingDay = $this->scheduleRepository->findWorkingDayException($agendaId, $doctorDocument, $currentDate);
        if (!$currentWorkingDay) {
            return response()->json([
                'status' => 'error',
                'message' => 'No existe registro de excepción de días para esa agenda, doctor y fecha actual'
            ], 404);
        }

        // 2. Actualizar la fecha del registro en working_days
        $workingDayUpdated = $this->scheduleRepository->updateWorkingDayExceptionDate(
            $agendaId,
            $doctorDocument,
            $currentDate,
            $newDate
        );

        // 3. Actualizar todas las citas de esa agenda/doctor/fecha actual a la nueva fecha
        $updatedAppointments = $this->appointmentRepository->updateAppointmentsDate(
            $agendaId,
            $doctorDocument,
            $currentDate,
            $newDate
        );

        Log::info('Agenda rescheduled to same agenda', [
            'agenda_id' => $agendaId,
            'doctor_document' => $doctorDocument,
            'current_date' => $currentDate,
            'new_date' => $newDate,
            'working_day_updated' => $workingDayUpdated,
            'appointments_updated' => $updatedAppointments,
            'notify_patients' => $notifyPatients
        ]);

        // Notificar a pacientes si se solicita
        if ($notifyPatients) {
            SendRescheduleNotifications::dispatch(
                $agendaId,
                $doctorDocument,
                $newDate,
                $currentDate
            )->delay(now()->addSeconds(5));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Agenda reprogramada exitosamente en la misma agenda',
            'data' => [
                'working_day_exception_updated' => $workingDayUpdated,
                'appointments_updated' => $updatedAppointments,
                'notify_patients' => $notifyPatients
            ]
        ], 200);
    }
}

