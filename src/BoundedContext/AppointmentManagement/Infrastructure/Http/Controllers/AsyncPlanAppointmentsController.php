<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\PlanAppointmentsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AsyncPlanAppointmentsController extends Controller
{
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
            (string) $validated['patient_id'] ?? null,
            $validated['client_type'],
            $planId,
            $resumeKey
        );
        $job->onQueue('ai-logic');

        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }
        Log::info('----PLANIFICAR CITAS Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'data' => $validated
        ]);
        Bus::dispatch($job);

        return response()->json([
            'plan_id'   => $planId,
            'status'     => 'queued',
            'resume_key' => $resumeKey,
        ], 202);
    }
}
