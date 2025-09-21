<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\PlanAppointmentsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;

class AsyncPlanAppointmentsController extends Controller
{
    use DispatchesJobsSafely;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orden' => 'required|array',
            'orden.procedimientos' => 'required|array',
            'patient_id' => 'sometimes|integer',
            'client_type' => ['required', Rule::in(['affiliate', 'individual'])],
        ]);

        $planId = Str::uuid()->toString();
        $resumeKey = Str::uuid()->toString();

        $job = new PlanAppointmentsJob(
            $validated['orden'],
            (string) ($validated['patient_id'] ?? null),
            $validated['client_type'],
            $planId,
            $resumeKey
        );
        $job->onQueue('ai-logic');

        $this->dispatchSafely(
            $job,
            '----PLANIFICAR CITAS',
            $validated
        );

        return response()->json([
            'plan_id'   => $planId,
            'status'     => 'queued',
            'resume_key' => $resumeKey,
        ], 202);
    }
}
