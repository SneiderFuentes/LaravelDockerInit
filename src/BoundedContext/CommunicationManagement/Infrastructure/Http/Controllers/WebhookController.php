<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\CommunicationManagement\Application\Jobs\ProcessWebhookJob;
use Core\BoundedContext\CommunicationManagement\Application\Services\HandleInboundWebhookService;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Http\Requests\BirdWebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private HandleInboundWebhookService $service) {}

    public function handleBirdWebhook(Request $request): JsonResponse
    {
        // Registrar el webhook recibido
        Log::info('Bird Webhook recibido', [
            // 'headers' => $request->headers->all(),
            'payload' => $request->all()
        ]);

        try {
            // Obtener el payload validado
            $payload = $request->all();

            // Log detallado del tipo de webhook
            $webhookType = $payload['type'] ?? 'unknown';
            Log::info("Procesando webhook de tipo: {$webhookType}", [
                'payload' => $payload
            ]);

            //ProcessWebhookJob::dispatch($payload);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook recibido y programado para procesamiento'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al procesar webhook: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al procesar webhook'
            ], 500);
        }
    }
}
