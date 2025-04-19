<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Controllers\Api;

use App\Http\Controllers\Controller;
use Core\BoundedContext\CommunicationManagement\Application\InitiateCall\InitiateCallCommand;
use Core\Shared\Domain\Bus\Command\CommandBus;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\CallRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\CallStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CallController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly CallRepositoryInterface $callRepository
    ) {}

    /**
     * Iniciar una llamada telefónica
     */
    public function initiateCall(Request $request): JsonResponse
    {
        // Validar parámetros de entrada
        $request->validate([
            'appointment_id' => 'required|string',
            'patient_id' => 'required|string',
            'phone_number' => 'required|string',
            'call_type' => 'required|string|in:appointment_reminder,appointment_confirmation,appointment_cancellation',
            'flow_id' => 'nullable|string',
        ]);

        try {
            // Crear y ejecutar comando
            $command = new InitiateCallCommand(
                $request->input('appointment_id'),
                $request->input('patient_id'),
                $request->input('phone_number'),
                $request->input('call_type'),
                $request->input('flow_id')
            );

            $this->commandBus->dispatch($command);

            return new JsonResponse(
                [
                    'success' => true,
                    'message' => 'Call initiated successfully',
                ],
                Response::HTTP_ACCEPTED
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Error initiating call',
                    'error' => $e->getMessage()
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Webhook para actualizar el estado de las llamadas
     */
    public function webhook(Request $request): JsonResponse
    {
        // Validar firma del webhook si está configurado
        $webhookSecret = config('messagebird.webhook_secret');

        if ($webhookSecret) {
            $signature = $request->header('MessageBird-Signature');
            $timestamp = $request->header('MessageBird-Request-Timestamp');
            $requestBody = $request->getContent();

            $expectedSignature = hash_hmac('sha256', $timestamp . $requestBody, $webhookSecret);

            if ($signature !== $expectedSignature) {
                return new JsonResponse(['error' => 'Invalid webhook signature'], Response::HTTP_UNAUTHORIZED);
            }
        }

        // Procesar datos del webhook
        $data = $request->json()->all();

        // Extraer call ID y estado
        $callId = $data['call']['id'] ?? null;
        $status = $data['call']['status'] ?? null;

        if (!$callId || !$status) {
            return new JsonResponse(['error' => 'Missing call ID or status'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Mapear el estado al objeto de valor
            $callStatus = $this->mapStatus($status);

            // Extraer datos adicionales si existen
            $statusData = [];

            if (isset($data['call']['start_time'])) {
                $statusData['start_time'] = $data['call']['start_time'];
            }

            if (isset($data['call']['end_time'])) {
                $statusData['end_time'] = $data['call']['end_time'];
            }

            if (isset($data['call']['duration'])) {
                $statusData['duration'] = $data['call']['duration'];
            }

            // Buscar la llamada y actualizar su estado
            $call = $this->callRepository->findByCallId($callId);

            if (!$call) {
                return new JsonResponse(['error' => 'Call not found'], Response::HTTP_NOT_FOUND);
            }

            // Actualizar el estado
            $call = $call->updateStatus($callStatus);

            // Actualizar datos adicionales si existen
            if (!empty($statusData)) {
                if (isset($statusData['start_time'])) {
                    $call = $call->setStartTime(new \DateTime($statusData['start_time']));
                }

                if (isset($statusData['end_time'])) {
                    $call = $call->setEndTime(new \DateTime($statusData['end_time']));
                }

                if (isset($statusData['duration'])) {
                    $call = $call->setDuration((int) $statusData['duration']);
                }

                $call = $call->setResponseData($data);
            }

            // Guardar la actualización
            $this->callRepository->update($call);

            return new JsonResponse(['success' => true], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mapear el estado del webhook al objeto de valor
     */
    private function mapStatus(string $status): CallStatus
    {
        return match ($status) {
            'initiated' => CallStatus::initiated(),
            'ringing' => CallStatus::initiated(),
            'answered' => CallStatus::inProgress(),
            'completed' => CallStatus::completed(),
            'busy' => CallStatus::failed(),
            'no-answer' => CallStatus::noAnswer(),
            'failed' => CallStatus::failed(),
            default => CallStatus::pending()
        };
    }
}
