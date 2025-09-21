<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\ParseMedicalOrderVisionJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;

class AsyncUploadMedicalOrderController extends Controller
{
    use DispatchesJobsSafely;

    public function __invoke(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'file_url'     => 'required|string',
            'image_url'    => 'required|string',
            'content_type' => 'required|string',
            'patient_document' => 'required|string',
        ]);

        $fileUrl = $validatedData['file_url'];
        $imageUrl = $validatedData['image_url'];
        $url = $fileUrl == 'no_url' ? $imageUrl : $fileUrl;
        $contentType = $validatedData['content_type'];
        $patientDocument = $validatedData['patient_document'];

        $orderId = Str::uuid()->toString();

        if ($url === null || $contentType === null) {
            return response()->json(['error' => 'No file uploaded or an error occurred'], 400);
        }

        $resumeKey = Str::uuid()->toString();
        $job = new ParseMedicalOrderVisionJob($url, $contentType, $orderId, $resumeKey, $patientDocument);
        $job->onQueue('ai-vision');

        $this->dispatchSafely(
            $job,
            '----SUBIR ORDEN MEDICA',
            $validatedData
        );

        return response()->json([
            'order_id'   => $orderId,
            'status'     => 'queued',
            'resume_key' => $resumeKey,
        ], 202);
    }
}
