<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Application\Services\CheckExistingAppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CheckExistingAppointmentController extends Controller
{
    public function __construct(private CheckExistingAppointmentService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|string',
            'cup_code' => 'required|string',
        ]);

        $hasAppointment = $this->service->execute(
            $validated['patient_id'],
            $validated['cup_code']
        );

        return response()->json(['has_existing_appointment' => $hasAppointment]);
    }
}
