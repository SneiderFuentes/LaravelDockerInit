<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\CommunicationManagement\Application\Services\HandleInboundWebhookService;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Http\Requests\BirdWebhookRequest;
use Illuminate\Http\JsonResponse;

class WebhookController
{
    private HandleInboundWebhookService $service;

    public function __construct(HandleInboundWebhookService $service)
    {
        $this->service = $service;
    }

    public function handleBirdWebhook(BirdWebhookRequest $request): JsonResponse
    {
        $payload = $request->validated();

        try {
            $this->service->handleIncomingMessage($payload);

            return new JsonResponse([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
