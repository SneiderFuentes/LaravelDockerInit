<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetAvailableSlotsByCupHandler;
use Core\BoundedContext\AppointmentManagement\Application\Commands\GetAvailableSlotsByCupCommand;
use Illuminate\Http\Request;

class GetAvailableSlotsByCupController
{
    public function __construct(private GetAvailableSlotsByCupHandler $handler) {}

    public function __invoke(Request $request)
    {
        $validatedData = $request->validate([
            'appointment_slot_estimate' => 'required|integer|min:1',
            'procedures' => 'required|array|min:1',
            'procedures.*.cups' => 'required|string',
        ]);
        $procedures = $validatedData['procedures'];
        $espacios = $validatedData['appointment_slot_estimate'];
        $command = new GetAvailableSlotsByCupCommand($procedures, $espacios);
        $slots = $this->handler->handle($command->procedures, $command->espacios);
        return response()->json($slots);
    }
}
