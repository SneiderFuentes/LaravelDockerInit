<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\GetAvailableSlotsByCupJob;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AsyncGetAvailableSlotsByCupController
{
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

        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }
        Log::info('----OBTENER ESPACIOS Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'data' => $validatedData
        ]);
        Bus::dispatch($job);

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
