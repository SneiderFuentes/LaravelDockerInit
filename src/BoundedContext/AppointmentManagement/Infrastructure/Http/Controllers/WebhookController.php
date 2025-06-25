<?php

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Registrar el webhook recibido
        Log::info('Webhook recibido', [
            'headers' => $request->headers->all(),
            'payload' => $request->all()
        ]);

        try {
            // Validar la firma del webhook si está configurado
            $webhookSecret = env('FLOW_APPOINTMENT_ORDER_WEBHOOK_SECRET');

            if ($webhookSecret) {
                $signature = $request->header('Flow-Secret');

                if ($signature !== $webhookSecret) {
                    Log::warning('Firma de webhook inválida', [
                        'signature' => $signature
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Firma inválida'
                    ], 401);
                }
            }

            // Procesar el payload
            $payload = $request->all();

            // Registrar la respuesta
            Log::info('Webhook procesado exitosamente', [
                'payload' => $payload
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook recibido y procesado correctamente'
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
