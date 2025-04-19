<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Listeners;

use Core\BoundedContext\CommunicationManagement\Application\Events\WhatsappMessageReceived;
use Core\BoundedContext\FlowManagement\Application\Commands\TriggerFlowCommand;
use Illuminate\Contracts\Bus\Dispatcher;

class ProcessWhatsappMessageListener
{
    private Dispatcher $commandBus;

    public function __construct(Dispatcher $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(WhatsappMessageReceived $event): void
    {
        // Extract intent from message
        $intent = $this->extractIntent($event->message());

        if (empty($intent)) {
            // Handle default flow for messages without clear intent
            $this->commandBus->dispatch(new TriggerFlowCommand(
                'default_conversation',
                'whatsapp',
                $event->from()->fullNumber(),
                [
                    'message' => $event->message(),
                    'raw_payload' => $event->rawPayload()
                ]
            ));
            return;
        }

        // Trigger the appropriate flow based on intent
        $this->commandBus->dispatch(new TriggerFlowCommand(
            $intent,
            'whatsapp',
            $event->from()->fullNumber(),
            [
                'message' => $event->message(),
                'raw_payload' => $event->rawPayload()
            ]
        ));
    }

    private function extractIntent(string $message): string
    {
        $message = strtolower(trim($message));

        $intents = [
            'confirm' => ['confirmar', 'confirmo', 'si', 'sí', 'confirma'],
            'cancel' => ['cancelar', 'cancelo', 'no', 'cancelación'],
            'reschedule' => ['reagendar', 'cambiar', 'cambio', 'mover', 'otro día'],
            'help' => ['ayuda', 'help', 'ayúdame', 'información', 'info']
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return $intent;
                }
            }
        }

        return '';
    }
}
