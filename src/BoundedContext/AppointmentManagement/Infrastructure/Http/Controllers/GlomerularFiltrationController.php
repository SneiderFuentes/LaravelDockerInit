<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\CalculateGlomerularFiltrationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;

class GlomerularFiltrationController extends Controller
{
    use DispatchesJobsSafely;

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

        $this->dispatchSafely(
            $job,
            '----CALCULAR FILTRACION GLOMERULAR',
            $validatedData
        );

        return response()->json([
            'status'     => 'queued',
            'resume_key' => $resumeKey,
        ], 202);
    }
}
