<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Adapters\Bird;

use Core\BoundedContext\CommunicationManagement\Domain\Exceptions\CommunicationException;
use Core\BoundedContext\CommunicationManagement\Domain\Ports\CallGatewayInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

class BirdCallAdapter implements CallGatewayInterface
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

    public function placeCall(PhoneNumber $to, string $flowId, array $parameters = []): string
    {
        try {
            $response = $this->httpClient->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/calls', [
                'to' => $to->fullNumber(),
                'flow_id' => $flowId,
                'parameters' => $parameters
            ]);

            if ($response->failed()) {
                throw new CommunicationException(
                    'Failed to place call: ' . $response->body(),
                    (string)$response->status()
                );
            }

            $data = $response->json();
            return $data['id'] ?? '';
        } catch (\Exception $e) {
            if (!$e instanceof CommunicationException) {
                Log::error('Error placing call: ' . $e->getMessage());
                throw new CommunicationException('Error placing call: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    public function getCallStatus(string $callId): string
    {
        try {
            $response = $this->httpClient->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->apiUrl . '/calls/' . $callId);

            if ($response->failed()) {
                throw new CommunicationException(
                    'Failed to get call status: ' . $response->body(),
                    (string)$response->status()
                );
            }

            $data = $response->json();
            return $data['status'] ?? 'unknown';
        } catch (\Exception $e) {
            if (!$e instanceof CommunicationException) {
                Log::error('Error getting call status: ' . $e->getMessage());
                throw new CommunicationException('Error getting call status: ' . $e->getMessage());
            }
            throw $e;
        }
    }
}
