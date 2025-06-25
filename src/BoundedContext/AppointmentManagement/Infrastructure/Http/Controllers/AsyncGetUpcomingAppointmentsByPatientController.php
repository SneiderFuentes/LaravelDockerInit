<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Carbon\Carbon;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\GetUpcomingAppointmentsByPatientJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AsyncGetUpcomingAppointmentsByPatientController
{
    public function __invoke(string $centerKey, string $patientId): JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $resumeKey = Str::uuid()->toString();
        $job = new GetUpcomingAppointmentsByPatientJob($patientId, $today, $resumeKey);

        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }
        Log::info('----OBTENER CITAS PENDIENTES Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'patient_id' => $patientId
        ]);
        Bus::dispatch($job);

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
