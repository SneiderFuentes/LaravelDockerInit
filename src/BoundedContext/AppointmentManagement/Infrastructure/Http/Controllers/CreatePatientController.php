<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\CreatePatientHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatePatientController
{
    public function __construct(private CreatePatientHandler $handler) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->all();
        try {
            $patientId = $this->handler->handle($data);
            return response()->json(['patientId' => $patientId], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }
}
