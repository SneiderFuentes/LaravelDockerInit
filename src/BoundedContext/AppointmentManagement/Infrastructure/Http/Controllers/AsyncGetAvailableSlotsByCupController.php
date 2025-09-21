<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\GetAvailableSlotsByCupJob;
use Illuminate\Support\Str;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;

class AsyncGetAvailableSlotsByCupController
{
    use DispatchesJobsSafely;

    public function __invoke(Request $request)
    {
        $validatedData = $request->validate([
            'appointment_slot_estimate' => 'required|integer|min:1',
            'procedures' => 'required|array|min:1',
            'procedures.*.cups' => 'required|string',
            'patient_age' => 'required|integer|min:0|max:120',
        ]);

        $procedures = $validatedData['procedures'];
        $espacios = $validatedData['appointment_slot_estimate'];
        $patientAge = $validatedData['patient_age'];
        $resumeKey = Str::uuid()->toString();
        $job = new GetAvailableSlotsByCupJob($procedures, $espacios, $resumeKey, $patientAge);
        $job->onQueue('notifications');

        $this->dispatchSafely(
            $job,
            '----OBTENER ESPACIOS DISPONIBLES',
            $validatedData
        );

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
