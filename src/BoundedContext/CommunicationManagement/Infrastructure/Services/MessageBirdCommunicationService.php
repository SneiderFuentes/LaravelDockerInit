<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Services;

use Core\BoundedContext\CommunicationManagement\Domain\Entities\Call;
use Core\BoundedContext\CommunicationManagement\Domain\Entities\Message as DomainMessage;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\CallRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\MessageRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\Services\CommunicationService;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\CallStatus;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\CallType;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageStatus;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageType;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MessageBird\Client;
use MessageBird\Objects\Message as SmsMessage;
use MessageBird\Objects\Conversation\Content;
use MessageBird\Objects\Conversation\Message as ConversationMessage;
use MessageBird\Objects\Conversation\HSM\Message as HsmMessage;
use MessageBird\Objects\Conversation\HSM\Params as HsmParams;
use MessageBird\Objects\Conversation\HSM\Language as HsmLanguage;
use Core\BoundedContext\CommunicationManagement\Application\Services\SendWhatsappMessageService;
use Core\BoundedContext\CommunicationManagement\Domain\Ports\MessageGatewayInterface;

final class MessageBirdCommunicationService implements CommunicationService
{
    private Client $messageBirdClient;

    public function __construct(
        private readonly string $apiKey,
        private readonly MessageRepositoryInterface $messageRepository,
        private readonly CallRepositoryInterface $callRepository,
        private readonly MessageGatewayInterface $messageGateway
    ) {
        $this->messageBirdClient = new Client($this->apiKey);
    }

    public function sendWhatsApp(
        string $appointmentId,
        string $patientId,
        string $phoneNumber,
        string $content
    ): DomainMessage {
        $message = new DomainMessage(
            Str::uuid()->toString(),
            $appointmentId,
            $patientId,
            $phoneNumber,
            $content,
            MessageType::whatsapp(),
            MessageStatus::pending()
        );

        try {
            $conversationMessage = new ConversationMessage();
            $conversationMessage->channelId = config('messagebird.whatsapp.channel_id');
            $conversationMessage->to        = $phoneNumber;
            $conversationMessage->content   = new Content();
            $conversationMessage->content->text = $content;

            // Start a new conversation on WhatsApp
            $result = $this->messageBirdClient
                ->conversations
                ->start($conversationMessage);

            $message = $message->markAsSent($result->id, new DateTime());
            $this->messageRepository->save($message);

            return $message;
        } catch (Exception $e) {
            $message = $message->markAsFailed();
            $this->messageRepository->save($message);

            Log::error("Error sending WhatsApp message: {$e->getMessage()}", [
                'appointment_id' => $appointmentId,
                'patient_id'     => $patientId,
                'phone_number'   => $phoneNumber,
            ]);

            return $message;
        }
    }

    public function sendWhatsAppTemplate(
        string $appointmentId,
        string $patientId,
        string $phoneNumber,
        string $templateName,
        array $parameters,
        ?string $subaccountKey = null
    ): string {
        $service = new SendWhatsappMessageService(
            $this->messageGateway,
            $this->messageRepository
        );

        return $service->sendTemplateMessage(
            $phoneNumber,
            $templateName,
            $parameters,
            $appointmentId,
            $patientId,
            $subaccountKey
        );
    }

    public function sendSMS(
        string $appointmentId,
        string $patientId,
        string $phoneNumber,
        string $content
    ): DomainMessage {
        $message = new DomainMessage(
            Str::uuid()->toString(),
            $appointmentId,
            $patientId,
            $phoneNumber,
            $content,
            MessageType::sms(),
            MessageStatus::pending()
        );

        try {
            $sms = new SmsMessage();
            $sms->originator = config('messagebird.sms.originator');
            $sms->recipients = [$phoneNumber];
            $sms->body       = $content;

            $result = $this->messageBirdClient
                ->messages
                ->create($sms);

            $message = $message->markAsSent($result->getId(), new DateTime());
            $this->messageRepository->save($message);

            return $message;
        } catch (Exception $e) {
            $message = $message->markAsFailed();
            $this->messageRepository->save($message);

            Log::error("Error sending SMS: {$e->getMessage()}", [
                'appointment_id' => $appointmentId,
                'patient_id'     => $patientId,
                'phone_number'   => $phoneNumber,
            ]);

            return $message;
        }
    }

    public function initiateCall(
        string $appointmentId,
        string $patientId,
        string $phoneNumber,
        CallType $type,
        ?string $flowId = null
    ): Call {
        $flowId ??= $this->getFlowIdForCallType($type);

        $call = new Call(
            Str::uuid()->toString(),
            $appointmentId,
            $patientId,
            $phoneNumber,
            CallStatus::pending(),
            $type
        );

        try {
            $response = $this->messageBirdClient
                ->voiceCalls
                ->create([
                    'source'      => config('messagebird.voice.number'),
                    'destination' => $phoneNumber,
                    'callFlow'    => ['id' => $flowId],
                ]);

            $call = $call
                ->setCallId($response->id)
                ->updateStatus(CallStatus::initiated());

            $this->callRepository->save($call);

            return $call;
        } catch (Exception $e) {
            $call = $call->updateStatus(CallStatus::failed());
            $this->callRepository->save($call);

            Log::error("Error initiating call: {$e->getMessage()}", [
                'appointment_id' => $appointmentId,
                'patient_id'     => $patientId,
                'phone_number'   => $phoneNumber,
                'call_type'      => $type->value(),
            ]);

            return $call;
        }
    }

    public function processMessageStatusUpdate(
        string $messageId,
        MessageStatus $newStatus,
        array $statusData = []
    ): DomainMessage {
        // Buscar mensaje por su ID externo
        $message = $this->messageRepository->findByMessageId($messageId);

        if ($message === null) {
            throw new \InvalidArgumentException("Message with external ID {$messageId} not found");
        }

        // Aplicar la actualización según el nuevo estado
        switch ($newStatus->value()) {
            case MessageStatus::DELIVERED:
                $message = $message->markAsDelivered(new DateTime());
                break;
            case MessageStatus::READ:
                $message = $message->markAsRead(new DateTime());
                break;
            case MessageStatus::FAILED:
                $message = $message->markAsFailed();
                break;
        }

        // Guardar la actualización
        $this->messageRepository->update($message);

        return $message;
    }

    public function processCallStatusUpdate(
        string $callId,
        CallStatus $newStatus,
        array $statusData = []
    ): Call {
        // Buscar llamada por su ID externo
        $call = $this->callRepository->findByCallId($callId);

        if ($call === null) {
            throw new \InvalidArgumentException("Call with external ID {$callId} not found");
        }

        // Aplicar la actualización según el nuevo estado
        $call = $call->updateStatus($newStatus);

        // Si hay datos adicionales, actualizarlos
        if (!empty($statusData)) {
            if (isset($statusData['start_time'])) {
                $call = $call->setStartTime(new DateTime($statusData['start_time']));
            }

            if (isset($statusData['end_time'])) {
                $call = $call->setEndTime(new DateTime($statusData['end_time']));
            }

            if (isset($statusData['duration'])) {
                $call = $call->setDuration((int) $statusData['duration']);
            }

            if (!empty($statusData)) {
                $call = $call->setResponseData($statusData);
            }
        }

        // Guardar la actualización
        $this->callRepository->update($call);

        return $call;
    }

    public function processMessageResponse(
        string $messageId,
        string $response
    ): DomainMessage {
        // Buscar mensaje por su ID externo
        $message = $this->messageRepository->findByMessageId($messageId);

        if ($message === null) {
            throw new \InvalidArgumentException("Message with external ID {$messageId} not found");
        }

        // Actualizar el mensaje con la respuesta
        $message = $message->setResponse($response);

        // Guardar la actualización
        $this->messageRepository->update($message);

        return $message;
    }

    private function getFlowIdForCallType(CallType $type): string
    {
        return match ($type->value()) {
            CallType::APPOINTMENT_REMINDER      => config('messagebird.flows.appointment_reminder'),
            CallType::APPOINTMENT_CONFIRMATION  => config('messagebird.flows.appointment_confirmation'),
            CallType::APPOINTMENT_CANCELLATION  => config('messagebird.flows.appointment_cancellation'),
            default => throw new \InvalidArgumentException("Invalid call type: {$type->value()}"),
        };
    }
}
