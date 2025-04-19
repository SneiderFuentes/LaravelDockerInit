<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Adapters\Bird;

use Core\BoundedContext\CommunicationManagement\Domain\Exceptions\CommunicationException;
use Core\BoundedContext\CommunicationManagement\Domain\Ports\MessageGatewayInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

class BirdMessageAdapter implements MessageGatewayInterface
{
    private HttpClient $httpClient;
    private string $apiKey;
    private string $apiUrl;

    public function __construct(HttpClient $httpClient, string $apiKey, string $apiUrl)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }

    public function sendTextMessage(PhoneNumber $to, string $message): string
    {
        try {
            $response = $this->httpClient->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/messages', [
                'to' => $to->fullNumber(),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ]);

            if ($response->failed()) {
                throw new CommunicationException(
                    'Failed to send text message: ' . $response->body(),
                    (string)$response->status()
                );
            }

            $data = $response->json();
            return $data['id'] ?? '';
        } catch (\Exception $e) {
            if (!$e instanceof CommunicationException) {
                Log::error('Error sending text message: ' . $e->getMessage());
                throw new CommunicationException('Error sending text message: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    public function sendTemplateMessage(PhoneNumber $to, string $templateId, array $parameters): string
    {
        try {
            // Transform parameters to Bird API format
            $components = $this->formatTemplateParameters($parameters);

            $response = $this->httpClient->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/messages', [
                'to' => $to->fullNumber(),
                'type' => 'template',
                'template' => [
                    'name' => $templateId,
                    'language' => [
                        'code' => 'es'
                    ],
                    'components' => $components
                ]
            ]);

            if ($response->failed()) {
                throw new CommunicationException(
                    'Failed to send template message: ' . $response->body(),
                    (string)$response->status()
                );
            }

            $data = $response->json();
            return $data['id'] ?? '';
        } catch (\Exception $e) {
            if (!$e instanceof CommunicationException) {
                Log::error('Error sending template message: ' . $e->getMessage());
                throw new CommunicationException('Error sending template message: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    private function formatTemplateParameters(array $parameters): array
    {
        $components = [];

        if (!empty($parameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(function ($key, $value) {
                    return [
                        'type' => 'text',
                        'text' => $value
                    ];
                }, array_keys($parameters), $parameters)
            ];
        }

        return $components;
    }
}
