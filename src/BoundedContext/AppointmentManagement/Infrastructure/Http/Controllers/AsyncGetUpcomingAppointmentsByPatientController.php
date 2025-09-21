<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\GetUpcomingAppointmentsByPatientJob;
use Illuminate\Support\Str;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;

class AsyncGetUpcomingAppointmentsByPatientController
{
    use DispatchesJobsSafely;

    public function __invoke(string $centerKey, string $patientId): JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $resumeKey = Str::uuid()->toString();
        $job = new GetUpcomingAppointmentsByPatientJob($patientId, $today, $resumeKey);

        $this->dispatchSafely(
            $job,
            '----OBTENER CITAS PENDIENTES',
            ['patient_id' => $patientId]
        );

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
