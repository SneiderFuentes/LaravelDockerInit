<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\ParseMedicalOrderVisionJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class AsyncUploadMedicalOrderController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'file_url'     => 'required|string',
            'image_url'    => 'required|string',
            'content_type' => 'required|string',
            'patient_document' => 'required|string',
        ]);
        $fileUrl = $request->input('file_url');
        $imageUrl = $request->input('image_url');
        $url = $fileUrl == 'no_url' ? $imageUrl : $fileUrl;
        $contentType = $request->input('content_type');
        $patientDocument = $request->input('patient_document');
        Log::info('----PATIENT DOCUMENT', ['patient_document' => $patientDocument]);
        $orderId = Str::uuid()->toString();

        if ($url === null || $contentType === null) {
            return response()->json(['error' => 'No file uploaded or an error occurred'], 400);
        }

        $resumeKey = Str::uuid()->toString();
        $job = new ParseMedicalOrderVisionJob($url, $contentType, $orderId, $resumeKey, $patientDocument);
        $job->onQueue('ai-vision');

        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }
        Log::info('----SUBIR ORDEN MEDICA Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'data' => $request->all()
        ]);
        Bus::dispatch($job);

        return response()->json([
            'order_id'   => $orderId,
            'status'     => 'queued',
            'resume_key' => $resumeKey,
        ], 202);
    }
}
