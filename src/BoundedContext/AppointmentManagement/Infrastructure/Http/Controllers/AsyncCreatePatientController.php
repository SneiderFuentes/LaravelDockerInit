<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\CreatePatientJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AsyncCreatePatientController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->all();
        $resumeKey = Str::uuid()->toString();
        $job = new CreatePatientJob($data, $resumeKey);
        $job->onQueue('notifications');

        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }
        Log::info('----CREAR PACIENTE Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'data' => $data
        ]);
        Bus::dispatch($job);

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
