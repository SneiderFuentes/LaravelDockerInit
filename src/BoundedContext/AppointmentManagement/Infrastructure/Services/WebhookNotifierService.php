<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookNotifierService
{
    public function notify(string $webhookUrl, string $birdKey, string $resumeKey, array $payload, string $logPrefix = ''): void
    {
        $body = [
            'action' => 'resume',
            'resumeKey' => $resumeKey,
            'resumeExtraInput' => $payload,
        ];

        Log::info($logPrefix . 'Webhook Request', [
            'url' => $webhookUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $birdKey,
                'Content-Type' => 'application/json',
            ],
            'body' => $body
        ]);

        $response = Http::retry(3, 1000, function ($exception, $request) {
            // Reintentar en caso de error de conexión o errores del servidor (5xx).
            return $exception instanceof \Illuminate\Http\Client\ConnectionException || $exception->response->serverError();
        })->withHeaders([
            'Authorization' => 'Bearer ' . $birdKey,
            'Content-Type' => 'application/json',
        ])->patch($webhookUrl, $body);

        if ($response->failed()) {
            Log::error($logPrefix . 'Webhook final response after retries indicates failure', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            // Lanzar una excepción para que el Job que lo llamó falle y pueda ser reintentado.
            $response->throw();
        }

        Log::info($logPrefix . 'Webhook Response', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);
    }

    public function notifyFromConfig(string $resumeKey, array $payload, string $logPrefix = ''): void
    {
        $webhookUrl = config('services.messagebird.webhooks.appointment_flow_webhook');
        $birdKey = config('services.messagebird.api_key');
        $this->notify($webhookUrl, $birdKey, $resumeKey, $payload, $logPrefix);
    }
}
