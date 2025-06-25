<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetUpcomingAppointmentsByPatientHandler;

class GetUpcomingAppointmentsByPatientController
{
    public function __construct(private GetUpcomingAppointmentsByPatientHandler $handler) {}

    public function __invoke(string $centerKey, string $patientId): JsonResponse
    {
        if (!$patientId) {
            return response()->json(['error' => 'El parámetro patient_id es requerido'], 400);
        }
        $today = Carbon::today()->toDateString();
        $appointments = $this->handler->handle($patientId, $today);
        return response()->json([
            'data' => $appointments,
            'meta' => [
                'total' => count($appointments),
                'from_date' => $today,
            ],
        ]);
    }
}
