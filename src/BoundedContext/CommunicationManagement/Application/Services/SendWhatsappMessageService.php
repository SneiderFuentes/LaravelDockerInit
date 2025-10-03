<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Services;

use Core\BoundedContext\CommunicationManagement\Domain\Ports\MessageGatewayInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageType;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageStatus;
use Core\BoundedContext\CommunicationManagement\Domain\Entities\Message;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\MessageRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DateTime;

class SendWhatsappMessageService
{
    private MessageGatewayInterface $messageGateway;
    private MessageRepositoryInterface $messageRepository;

    public function __construct(MessageGatewayInterface $messageGateway, MessageRepositoryInterface $messageRepository)
    {
        $this->messageGateway = $messageGateway;
        $this->messageRepository = $messageRepository;
    }

    public function sendMessage(string $phoneNumber, string $content): string
    {
        $phone = new PhoneNumber($phoneNumber);

        return $this->messageGateway->sendTextMessage($phone, $content);
    }

    public function sendTemplateMessage(string $phoneNumber, string $templateName, array $parameters, string $appointmentId, string $patientId, ?string $subaccountKey = null): string
    {
        // $phone = new PhoneNumber($phoneNumber);
        $phone = new PhoneNumber("573103343616");


        $message = new Message(
            Str::uuid()->toString(),
            $appointmentId,
            $patientId,
            $phoneNumber,
            json_encode($parameters),
            MessageType::whatsapp(),
            MessageStatus::pending(),
            null,
            null,
            $subaccountKey,
            null,
            null,
            null,
            new DateTime(),
            new DateTime()
        );

        // Guardar el mensaje en la base de datos
        $this->messageRepository->save($message);

        // Enviar el mensaje
        $messageId = $this->messageGateway->sendTemplateMessage($phone, $templateName, $parameters);
        //$messageId = random_int(1, 100);

        // Actualizar el ID del mensaje y el estado
        $message = $message->markAsSent($messageId, new DateTime());
        $this->messageRepository->update($message);

        return $messageId;
    }

    /**
     * Envía un flujo de confirmación de cita a través de Bird
     */
    public function sendAppointmentConfirmationFlow(array $appointmentData): bool
    {
            $url = env('BIRD_FLOW_CONFIRM_APPOINTMENT');
            $apiKey = env('FLOW_APPOINMENT_WEBHOOK_API_KEY');

            if (!$url || !$apiKey) {
                Log::error('Missing Bird flow configuration', [
                    'url' => $url ? 'configured' : 'missing',
                    'api_key' => $apiKey ? 'configured' : 'missing'
                ]);
                return false;
            }
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post($url, $appointmentData);

            Log::info('AFTER REQUEST - Bird response received', [
                'appointment_id' => $appointmentData['appointment_id'] ?? 'unknown',
                'patient_name' => $appointmentData['patient_name'] ?? 'unknown',
                'phone_sent' => $appointmentData['phone'] ?? 'unknown',
                'response_status' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => $response->body()
            ]);
            if ($response->successful()) {
                return true;
            } else {
                return false;
            }
    }

    /**
     * Envía un flujo de recordatorio de cita PENDIENTE a través de Bird
     */
    public function sendPendingAppointmentReminderFlow(array $appointmentData): bool
    {
            $url = env('BIRD_FLOW_PENDING_APPOINTMENT');
            $apiKey = env('FLOW_APPOINMENT_WEBHOOK_API_KEY');

            if (!$url || !$apiKey) {
                Log::error('Missing Bird flow configuration for pending appointments', [
                    'url' => $url ? 'configured' : 'missing',
                    'api_key' => $apiKey ? 'configured' : 'missing'
                ]);
                return false;
            }
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post($url, $appointmentData);

            Log::info('AFTER REQUEST - Bird pending response received', [
                'appointment_id' => $appointmentData['appointment_id'] ?? 'unknown',
                'patient_name' => $appointmentData['patient_name'] ?? 'unknown',
                'phone_sent' => $appointmentData['phone'] ?? 'unknown',
                'response_status' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => $response->body()
            ]);
            if ($response->successful()) {
                return true;
            } else {
                return false;
            }
    }
}
