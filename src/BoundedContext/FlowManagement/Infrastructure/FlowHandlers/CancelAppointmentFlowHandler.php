<?php

namespace Core\BoundedContext\FlowManagement\Infrastructure\FlowHandlers;

use Core\BoundedContext\AppointmentManagement\Application\Commands\CancelAppointmentCommand;
use Core\BoundedContext\FlowManagement\Domain\Contracts\FlowHandlerInterface;
use Core\BoundedContext\FlowManagement\Domain\ValueObjects\ChannelType;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;

class CancelAppointmentFlowHandler implements FlowHandlerInterface
{
    private Dispatcher $commandBus;
    private string $flowId = 'cancel_appointment';

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
            Log::info('Processing cancel appointment flow', [
                'phone' => $phoneNumber,
                'parameters' => $parameters
            ]);

            // Extract appointment ID and reason from parameters
            $appointmentId = $parameters['appointment_id'] ?? null;
            $reason = $parameters['reason'] ?? $parameters['message'] ?? null;

            if (!$appointmentId) {
                Log::error('Missing appointment_id in parameters', [
                    'phone' => $phoneNumber,
                    'parameters' => $parameters
                ]);

                // Return a message indicating a problem
                return [
                    'status' => 'error',
                    'message' => 'No se pudo encontrar la cita para cancelar. Por favor, contacte a soporte.'
                ];
            }

            // Dispatch command to cancel appointment
            $this->commandBus->dispatch(new CancelAppointmentCommand($appointmentId, $reason));

            // Return success response
            return [
                'status' => 'success',
                'message' => 'Su cita ha sido cancelada. Lamentamos los inconvenientes y esperamos poder atenderle en otra ocasión.'
            ];
        } catch (\Exception $e) {
            Log::error('Error cancelling appointment', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'parameters' => $parameters
            ]);

            return [
                'status' => 'error',
                'message' => 'Hubo un problema al cancelar su cita. Por favor, intente nuevamente o contacte a soporte.'
            ];
        }
    }
}
