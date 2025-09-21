<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\GetActiveEntitiesJob;
use Illuminate\Support\Str;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;

class AsyncGetActiveEntitiesController
{
    use DispatchesJobsSafely;

    public function __invoke(): JsonResponse
    {
        $resumeKey = Str::uuid()->toString();
        $job = new GetActiveEntitiesJob($resumeKey);

        $this->dispatchSafely(
            $job,
            '----OBTENER ENTIDADES ACTIVAS'
        );

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
