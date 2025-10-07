<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\UpdatePatientJob;
use Illuminate\Support\Str;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;

class AsyncUpdatePatientController
{
    use DispatchesJobsSafely;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_number' => 'required|string',
            'entity_code' => 'required|integer',
        ]);

        $resumeKey = Str::uuid()->toString();
        $job = new UpdatePatientJob($validated, $resumeKey);
        $job->onQueue('notifications');

        $errorResponse = $this->dispatchSafely(
            $job,
            '----ACTUALIZAR PACIENTE',
            $validated
        );

        if ($errorResponse) {
            return $errorResponse;
        }

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}

