<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Controllers\Api;

use App\Http\Controllers\Controller;
use Core\BoundedContext\CommunicationManagement\Application\SendWhatsappMessage\SendWhatsappMessageCommand;
use Core\Shared\Domain\Bus\Command\CommandBus;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\MessageRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MessageController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly MessageRepositoryInterface $messageRepository
    ) {}

    /**
     * Enviar un mensaje WhatsApp
     */
    public function sendWhatsapp(Request $request): JsonResponse
    {
        // Validar parámetros de entrada
        $request->validate([
            'appointment_id' => 'required|string',
            'patient_id' => 'required|string',
            'phone_number' => 'required|string',
            'content' => 'required|string',
        ]);

        try {
            // Crear y ejecutar comando
            $command = new SendWhatsappMessageCommand(
                $request->input('appointment_id'),
                $request->input('patient_id'),
                $request->input('phone_number'),
                $request->input('content')
            );

            $this->commandBus->dispatch($command);

            return new JsonResponse(
                [
                    'success' => true,
                    'message' => 'WhatsApp message sent successfully',
                ],
                Response::HTTP_ACCEPTED
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Error sending WhatsApp message',
                    'error' => $e->getMessage()
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Webhook para actualizar el estado de los mensajes
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

        // Extraer mensaje ID y estado
        $messageId = $data['message']['id'] ?? null;
        $status = $data['message']['status'] ?? null;

        if (!$messageId || !$status) {
            return new JsonResponse(['error' => 'Missing message ID or status'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Mapear el estado al objeto de valor
            $messageStatus = $this->mapStatus($status);

            // Buscar el mensaje y actualizar su estado
            $message = $this->messageRepository->findByMessageId($messageId);

            if (!$message) {
                return new JsonResponse(['error' => 'Message not found'], Response::HTTP_NOT_FOUND);
            }

            // Actualizar el estado según sea necesario
            switch ($messageStatus->value()) {
                case MessageStatus::DELIVERED:
                    $message = $message->markAsDelivered(new \DateTime());
                    break;
                case MessageStatus::READ:
                    $message = $message->markAsRead(new \DateTime());
                    break;
                case MessageStatus::FAILED:
                    $message = $message->markAsFailed();
                    break;
            }

            // Guardar la actualización
            $this->messageRepository->update($message);

            return new JsonResponse(['success' => true], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mapear el estado del webhook al objeto de valor
     */
    private function mapStatus(string $status): MessageStatus
    {
        return match ($status) {
            'delivered' => MessageStatus::delivered(),
            'read' => MessageStatus::read(),
            'failed', 'rejected', 'undeliverable' => MessageStatus::failed(),
            default => MessageStatus::pending()
        };
    }
}
