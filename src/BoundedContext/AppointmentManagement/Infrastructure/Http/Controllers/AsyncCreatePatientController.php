<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\CreatePatientJob;
use Illuminate\Support\Str;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;

class AsyncCreatePatientController
{
    use DispatchesJobsSafely;

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->all();
        $resumeKey = Str::uuid()->toString();
        $job = new CreatePatientJob($data, $resumeKey);
        $job->onQueue('notifications');

        $errorResponse = $this->dispatchSafely(
            $job,
            '----CREAR PACIENTE',
            $data
        );

        if ($errorResponse) {
            return $errorResponse;
        }

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
