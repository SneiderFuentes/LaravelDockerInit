<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Services;

use Core\BoundedContext\CommunicationManagement\Domain\Ports\MessageGatewayInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageType;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageStatus;
use Core\BoundedContext\CommunicationManagement\Domain\Entities\Message;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\MessageRepositoryInterface;
use Illuminate\Support\Str;
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

    public function sendTemplateMessage(string $phoneNumber, string $templateName, array $parameters, string $appointmentId, string $patientId): string
    {
        $phone = new PhoneNumber($phoneNumber);

        // Crear un nuevo mensaje
        $message = new Message(
            Str::uuid()->toString(),
            $appointmentId, // Usar el ID real de la cita
            $patientId, // Usar el ID real del paciente
            $phoneNumber,
            json_encode($parameters), // Almacenar los parámetros como contenido
            MessageType::whatsapp(),
            MessageStatus::pending(),
            null,
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

        // Actualizar el ID del mensaje y el estado
        $message = $message->markAsSent($messageId, new DateTime());
        $this->messageRepository->update($message);

        return $messageId;
    }
}
