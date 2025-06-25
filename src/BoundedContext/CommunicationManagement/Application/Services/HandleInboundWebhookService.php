<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Services;

use Core\BoundedContext\CommunicationManagement\Application\Events\WhatsappMessageReceived;
use Core\BoundedContext\CommunicationManagement\Domain\Entities\Message;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\MessageRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageStatus;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use DateTime;

class HandleInboundWebhookService
{
    private Dispatcher $eventDispatcher;
    private MessageRepositoryInterface $messageRepository;

    public function __construct(
        Dispatcher $eventDispatcher,
        MessageRepositoryInterface $messageRepository
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->messageRepository = $messageRepository;
    }

    public function handleIncomingMessage(array $payload): void
    {
        // Si es una actualización de estado
        if (isset($payload['type']) && $payload['type'] === 'message_status') {
            $this->updateMessageStatus($payload);
            return;
        }

        // Si es una respuesta a un mensaje previo (confirmación o respuesta del usuario)
        if (
            isset($payload['type']) && $payload['type'] === 'message' &&
            isset($payload['context']['message_id'])
        ) {
            $this->handleUserResponse($payload);
            return;
        }

        $messageType = $payload['message']['type'] ?? 'unknown';

        switch ($messageType) {
            case 'whatsapp':
                $this->processWhatsappMessage($payload);
                break;
            case 'sms':
                $this->processSmsMessage($payload);
                break;
            default:
                // Log unknown message type
                break;
        }
    }

    private function handleUserResponse(array $payload): void
    {
        $originalMessageId = $payload['context']['message_id'] ?? null;
        $responseContent = $payload['message']['content'] ?? '';

        if (!$originalMessageId) {
            Log::warning('Invalid user response webhook: missing message_id', $payload);
            return;
        }

        try {
            $message = $this->messageRepository->findByMessageId($originalMessageId);

            if (!$message) {
                Log::warning("Original message with ID $originalMessageId not found");
                return;
            }

            // Guardar la respuesta del usuario en el mensaje original
            $updatedMessage = $message;

            // Si la respuesta viene directamente como campo 'status' (confirmed/canceled)
            if (isset($payload['status'])) {
                $status = strtolower(trim($payload['status']));
                $responseContent = $status; // Usar el status como contenido si no hay otro
            } else {
                // Si viene en el contenido del mensaje
                $status = strtolower(trim($responseContent));
            }

            // Guardar siempre la respuesta
            if (!empty($responseContent)) {
                $updatedMessage = $updatedMessage->setResponse($responseContent);
            }

            // Procesar según el tipo de respuesta
            if ($status === 'confirmed' || in_array($status, ['si', 'sí', 'confirmar', 'confirmo', 'ok', 'yes'])) {
                $updatedMessage = $updatedMessage->markAsRead(new DateTime());

                Log::info("Cita confirmada por el usuario", [
                    'message_id' => $originalMessageId,
                    'respuesta' => $responseContent,
                    'appointment_id' => $message->getAppointmentId()
                ]);
            } else if ($status === 'canceled' || in_array($status, ['no', 'cancelar', 'cancelo', 'cancelado'])) {
                // Aquí podrías tener una lógica especial para cancelaciones
                // Por ejemplo, marcar como fallido o con un estado especial
                $updatedMessage = $updatedMessage->markAsFailed();

                Log::info("Cita cancelada por el usuario", [
                    'message_id' => $originalMessageId,
                    'respuesta' => $responseContent,
                    'appointment_id' => $message->getAppointmentId()
                ]);
            }

            $this->messageRepository->update($updatedMessage);

            Log::info("Respuesta del usuario registrada", [
                'message_id' => $originalMessageId,
                'respuesta' => $responseContent
            ]);
        } catch (\Exception $e) {
            Log::error("Error guardando respuesta de usuario: " . $e->getMessage(), [
                'message_id' => $originalMessageId,
                'respuesta' => $responseContent ?? 'N/A'
            ]);
        }
    }

    private function updateMessageStatus(array $payload): void
    {
        $messageId = $payload['message_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$messageId || !$status) {
            Log::warning('Invalid status update webhook: missing message_id or status', $payload);
            return;
        }

        try {
            $message = $this->messageRepository->findByMessageId($messageId);

            if (!$message) {
                Log::warning("Message with external ID $messageId not found");
                return;
            }

            $updatedMessage = $this->applyStatusToMessage($message, $status);

            $this->messageRepository->update($updatedMessage);

            Log::info("Message status updated", [
                'message_id' => $messageId,
                'new_status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating message status: " . $e->getMessage(), [
                'message_id' => $messageId,
                'status' => $status
            ]);
        }
    }

    private function applyStatusToMessage(Message $message, string $status): Message
    {
        $now = new DateTime();

        switch ($status) {
            case 'delivered':
                return $message->markAsDelivered($now);
            case 'read':
                return $message->markAsRead($now);
            case 'failed':
                return $message->markAsFailed();
            default:
                return $message;
        }
    }

    private function processWhatsappMessage(array $payload): void
    {
        $phoneNumber = new PhoneNumber($payload['from'] ?? '');
        $message = $payload['message']['content'] ?? '';
        $messageId = $payload['message_id'] ?? '';

        $event = new WhatsappMessageReceived(
            $phoneNumber,
            $message,
            $messageId,
            $payload
        );

        $this->eventDispatcher->dispatch($event);
    }

    private function processSmsMessage(array $payload): void
    {
        // Similar to processWhatsappMessage but for SMS
    }
}
