<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs\GetAvailableSlotsByCupJob;
use Illuminate\Support\Str;
use Core\Shared\Infrastructure\Http\Traits\DispatchesJobsSafely;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AsyncGetAvailableSlotsByCupController
{
    use DispatchesJobsSafely;

    public function __invoke(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'appointment_slot_estimate' => 'required|integer|min:1',
                'is_contrasted_resonance' => 'sometimes|boolean',
                'is_sedated' => 'sometimes|boolean',
                'procedures' => 'required|array|min:1',
                'procedures.*.cups' => 'required|string',
                'patient_age' => 'required|integer|min:0|max:120',
                'patient_id' => 'required|integer',
            ]);
        } catch (ValidationException $e) {
            Log::error('----OBTENER ESPACIOS DISPONIBLES - Validation failed', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            throw $e;
        }

        $procedures = $validatedData['procedures'];
        $espacios = $validatedData['appointment_slot_estimate'];
        $isContrasted = $validatedData['is_contrasted_resonance'] ?? false;
        $isSedated = $validatedData['is_sedated'] ?? false;
        $patientAge = $validatedData['patient_age'];
        $patientId = (string)$validatedData['patient_id'];

        // Buscar en Redis si hay paginación guardada para este paciente y CUPS
        $afterDate = null;
        if (!empty($patientId) && !empty($procedures)) {
            $afterDate = GetAvailableSlotsByCupJob::getLastSlotDatetimeFromRedis($patientId, $procedures);
        }

        Log::info('----OBTENER ESPACIOS DISPONIBLES - Request received', [
            'after_date_from_redis' => $afterDate,
            'patient_id' => $patientId,
            'procedures' => $procedures
        ]);
        $resumeKey = Str::uuid()->toString();
        $job = new GetAvailableSlotsByCupJob($procedures, $espacios, $resumeKey, $patientAge, $isContrasted, $isSedated, $patientId, $afterDate);
        $job->onQueue('notifications');

        $this->dispatchSafely(
            $job,
            '----OBTENER ESPACIOS DISPONIBLES',
            $validatedData
        );

        return response()->json(['status' => 'queued', 'resume_key' => $resumeKey], 202);
    }
}
