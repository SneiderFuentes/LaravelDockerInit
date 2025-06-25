<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\GetActiveEntitiesJob;
use Illuminate\Support\Str;

class AsyncGetActiveEntitiesController
{
    public function __invoke(): JsonResponse
    {
        $resumeKey = Str::uuid()->toString();
        $job = new GetActiveEntitiesJob($resumeKey);

        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }

        Bus::dispatch($job);

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
