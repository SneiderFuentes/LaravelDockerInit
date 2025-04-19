<?php

namespace Core\BoundedContext\FlowManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\FlowManagement\Application\Commands\TriggerFlowCommand;
use Core\BoundedContext\FlowManagement\Application\Queries\ListFlowsQuery;
use Core\BoundedContext\FlowManagement\Domain\ValueObjects\ChannelType;
use Core\BoundedContext\FlowManagement\Infrastructure\Http\Requests\TriggerFlowRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlowController
{
    private Dispatcher $commandBus;
    private Dispatcher $queryBus;

    public function __construct(Dispatcher $commandBus, Dispatcher $queryBus)
    {
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
    }

    public function triggerFlow(TriggerFlowRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $command = new TriggerFlowCommand(
                $validated['flow_id'],
                $validated['channel_type'],
                $validated['phone_number'],
                $validated['parameters'] ?? []
            );

            $result = $this->commandBus->dispatch($command);

            return new JsonResponse([
                'status' => 'success',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function listFlows(Request $request): JsonResponse
    {
        try {
            $channelType = $request->query('channel_type');

            $query = new ListFlowsQuery($channelType);
            $flows = $this->queryBus->dispatch($query);

            return new JsonResponse([
                'status' => 'success',
                'data' => $flows
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getChannelTypes(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'data' => [
                ['type' => 'sms', 'name' => 'SMS'],
                ['type' => 'whatsapp', 'name' => 'WhatsApp'],
                ['type' => 'voice', 'name' => 'Llamada de voz']
            ]
        ]);
    }
}
