<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\GetPatientByDocumentJob;
use Illuminate\Support\Str;

class AsyncGetPatientByDocumentController
{
    public function __invoke(string $centerKey, string $document): JsonResponse
    {
        $resumeKey = Str::uuid()->toString();
        $job = new GetPatientByDocumentJob($document, $resumeKey);
        $job->onQueue('notifications');

        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }
        Log::info('----OBTENER PACIENTE Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'document' => $document,
        ]);
        Bus::dispatch($job);

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
