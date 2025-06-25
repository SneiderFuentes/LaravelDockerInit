<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\CalculateGlomerularFiltrationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class GlomerularFiltrationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'age' => 'required|integer|min:0',
            'gender' => ['required', Rule::in(['F', 'M'])],
            'creatinine' => 'required|numeric|min:0',
            'height_cm' => 'required|integer|min:0',
            'weight_kg' => 'required|numeric|min:0',
            'underlying_disease_weight_type' => ['required', Rule::in(['true', 'false', 'low', 'normal'])],
        ]);

        $resumeKey = Str::uuid()->toString();
        $job = new CalculateGlomerularFiltrationJob($validatedData, $resumeKey);
        $job->onQueue('ai-logic');

        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 2) : (int)env('JOB_DEV_DELAY_SECONDS', 5);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }
        Log::info('----CALCULAR FILTRACION GLOMERULAR Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'data' => $validatedData
        ]);
        Bus::dispatch($job);

        return response()->json([
            'status'     => 'queued',
            'resume_key' => $resumeKey,
        ], 202);
    }
}
