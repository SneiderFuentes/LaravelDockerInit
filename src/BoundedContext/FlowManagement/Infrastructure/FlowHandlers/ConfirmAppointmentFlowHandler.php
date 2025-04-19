<?php

namespace Core\BoundedContext\FlowManagement\Infrastructure\FlowHandlers;

use Core\BoundedContext\AppointmentManagement\Application\Commands\ConfirmAppointmentCommand;
use Core\BoundedContext\FlowManagement\Domain\Contracts\FlowHandlerInterface;
use Core\BoundedContext\FlowManagement\Domain\ValueObjects\ChannelType;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;

class ConfirmAppointmentFlowHandler implements FlowHandlerInterface
{
    private Dispatcher $commandBus;
    private string $flowId = 'confirm_appointment';

    public function __construct(Dispatcher $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function getFlowId(): string
    {
        return $this->flowId;
    }

    public function getChannelType(): ChannelType
    {
        return ChannelType::whatsapp();
    }

    public function process(string $phoneNumber, array $parameters = [])
    {
        try {
            Log::info('Processing confirm appointment flow', [
                'phone' => $phoneNumber,
                'parameters' => $parameters
            ]);

            // Extract appointment ID from parameters
            $appointmentId = $parameters['appointment_id'] ?? null;
            $centerKey = $parameters['center_key'] ?? null;

            if (!$appointmentId) {
                Log::error('Missing appointment_id in parameters', [
                    'phone' => $phoneNumber,
                    'parameters' => $parameters
                ]);

                // Return a message indicating a problem
                return [
                    'status' => 'error',
                    'message' => 'No se pudo encontrar la cita para confirmar. Por favor, contacte a soporte.'
                ];
            }

            if (!$centerKey) {
                Log::error('Missing center_key in parameters', [
                    'phone' => $phoneNumber,
                    'parameters' => $parameters
                ]);

                return [
                    'status' => 'error',
                    'message' => 'No se pudo identificar el centro médico. Por favor, contacte a soporte.'
                ];
            }

            // Dispatch command to confirm appointment
            $this->commandBus->dispatch(new ConfirmAppointmentCommand($appointmentId, $centerKey));

            // Return success response
            return [
                'status' => 'success',
                'message' => '¡Gracias! Su cita ha sido confirmada.'
            ];
        } catch (\Exception $e) {
            Log::error('Error confirming appointment', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'parameters' => $parameters
            ]);

            return [
                'status' => 'error',
                'message' => 'Hubo un problema al confirmar su cita. Por favor, intente nuevamente o contacte a soporte.'
            ];
        }
    }
}
