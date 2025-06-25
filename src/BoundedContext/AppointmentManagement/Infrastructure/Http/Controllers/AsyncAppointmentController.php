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
use Illuminate\Support\Facades\Bus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

final class AsyncAppointmentController extends Controller
{
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

        // Retraso temporal para desarrollo
        if (app()->environment('local', 'development')) {
            $delaySeconds = (int) env('JOB_DELAY_SECONDS', 5);
            $job->delay(now()->addSeconds($delaySeconds));
            Bus::dispatch($job);
        } else {
            Bus::dispatch($job);
        }

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

        // Mapear el array de procedures al formato esperado de cups [code, cantidad]
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

        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }
        Log::info('----CREAR CITA Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'data' => $jobData
        ]);
        Bus::dispatch($job);

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

        // Retraso temporal para desarrollo
        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }
        Log::info('----CONFIRMAR CITA Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'data' => $request->all()
        ]);

        Bus::dispatch($job);

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }

    public function cancel(Request $request, string $centerKey, string $id): JsonResponse
    {
        $resumeKey = Str::uuid()->toString();
        $job = new CancelAppointmentJob($request->all(), $centerKey, $id, $resumeKey);
        $job->onQueue('notifications');

        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }
        Log::info('----CANCELAR CITA Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'data' => $request->all()
        ]);
        Bus::dispatch($job);

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
