<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\CancelAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ConfirmAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\CreateAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ListAppointmentsHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ListPendingAppointmentsHandler;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\CreateAppointmentJob;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\ConfirmAppointmentJob;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\CancelAppointmentJob;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\IndexAppointmentsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;

final class AsyncAppointmentController extends Controller
{
    use DispatchesJobsSafely;

    public function __construct(
        private ListAppointmentsHandler $listAppointmentsHandler,
        private GetAppointmentHandler $getAppointmentHandler,
        private ConfirmAppointmentHandler $confirmAppointmentHandler,
        private CancelAppointmentHandler $cancelAppointmentHandler,
        private ListPendingAppointmentsHandler $listPendingAppointmentsHandler,
        private CreateAppointmentHandler $createAppointmentHandler
    ) {}

    public function index(Request $request, $centerKey): JsonResponse
    {
        $resumeKey = Str::uuid()->toString();
        $job = new IndexAppointmentsJob(
            $request->all(),
            $centerKey,
            $resumeKey
        );

        $this->dispatchSafely(
            $job,
            '----INDEXAR CITAS',
            $request->all()
        );

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|string',
            'slots' => 'required|array|min:1',
            'selection' => 'required|integer|min:1',
            'procedures' => 'required|array|min:1',
            'espacios' => 'required|integer|min:1',
        ]);

        $selectionIndex = $validated['selection'] - 1;
        $slots = $validated['slots'];

        if (!isset($slots[$selectionIndex])) {
            return response()->json(['error' => 'La selección de horario no es válida.'], 400);
        }

        $selectedSlot = $slots[$selectionIndex];

        $cups = array_map(function ($procedure) {
            return [
                'code' => $procedure['cups'],
                'cantidad' => $procedure['cantidad'],
                'value' => $procedure['price'],
                'client_type' => $procedure['client_type']
            ];
        }, $validated['procedures']);

        $jobData = [
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $selectedSlot['doctorId'],
            'agenda_id' => $selectedSlot['agendaId'],
            'date' => $selectedSlot['fecha'],
            'time' => $selectedSlot['hora'],
            'cups' => $cups,
            'espacios' => $validated['espacios'],
        ];

        $resumeKey = Str::uuid()->toString();
        $job = new CreateAppointmentJob($jobData, $resumeKey);
        $job->onQueue('notifications');

        $this->dispatchSafely(
            $job,
            '----CREAR CITA',
            $jobData
        );

        return response()->json([
            'status' => 'queued',
            'resume_key' => $resumeKey
        ], 202);
    }

    public function confirm(Request $request, string $centerKey, string $id): JsonResponse
    {
        $resumeKey = Str::uuid()->toString();
        $job = new ConfirmAppointmentJob($request->all(), $centerKey, $id, $resumeKey);
        $job->onQueue('notifications');
        $this->dispatchSafely(
            $job,
            '----CONFIRMAR CITA',
            $request->all()
        );

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }

    public function cancel(Request $request, string $centerKey, string $id): JsonResponse
    {
        $resumeKey = Str::uuid()->toString();
        $job = new CancelAppointmentJob($request->all(), $centerKey, $id, $resumeKey);
        $job->onQueue('notifications');

        $this->dispatchSafely(
            $job,
            '----CANCELAR CITA',
            $request->all()
        );

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
