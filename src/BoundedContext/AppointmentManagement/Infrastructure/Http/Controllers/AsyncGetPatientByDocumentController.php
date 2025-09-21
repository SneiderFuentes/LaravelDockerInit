<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\GetPatientByDocumentJob;
use Illuminate\Support\Str;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;

class AsyncGetPatientByDocumentController
{
    use DispatchesJobsSafely;

    public function __invoke(string $centerKey, string $document): JsonResponse
    {
        $resumeKey = Str::uuid()->toString();
        $job = new GetPatientByDocumentJob($document, $resumeKey);
        $job->onQueue('notifications');

        $this->dispatchSafely(
            $job,
            '----OBTENER PACIENTE POR DOCUMENTO',
            ['document' => $document]
        );

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
