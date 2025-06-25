<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Services;

use Core\BoundedContext\CommunicationManagement\Domain\Entities\Call;
use Core\BoundedContext\CommunicationManagement\Domain\Entities\Message;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\CallStatus;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\CallType;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageStatus;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageType;

interface CommunicationService
{
    /**
     * Envía un mensaje WhatsApp al paciente.
     */
    public function sendWhatsApp(
        string $appointmentId,
        string $patientId,
        string $phoneNumber,
        string $content
    ): Message;

    /**
     * Send a WhatsApp template message
     *
     * @param string $appointmentId
     * @param string $patientId
     * @param string $phoneNumber
     * @param string $templateName
     * @param array $parameters
     * @param string|null $subaccountKey
     * @return string Message ID
     */
    public function sendWhatsAppTemplate(
        string $appointmentId,
        string $patientId,
        string $phoneNumber,
        string $templateName,
        array $parameters,
        ?string $subaccountKey = null
    ): string;

    /**
     * Envía un mensaje SMS al paciente.
     */
    public function sendSMS(
        string $appointmentId,
        string $patientId,
        string $phoneNumber,
        string $content
    ): Message;

    /**
     * Inicia una llamada telefónica al paciente.
     */
    public function initiateCall(
        string $appointmentId,
        string $patientId,
        string $phoneNumber,
        CallType $type,
        ?string $flowId = null
    ): Call;

    /**
     * Procesa una actualización de estado de mensaje (webhook).
     */
    public function processMessageStatusUpdate(
        string $messageId,
        MessageStatus $newStatus,
        array $statusData = []
    ): Message;

    /**
     * Procesa una actualización de estado de llamada (webhook).
     */
    public function processCallStatusUpdate(
        string $callId,
        CallStatus $newStatus,
        array $statusData = []
    ): Call;

    /**
     * Procesa una respuesta a un mensaje.
     */
    public function processMessageResponse(
        string $messageId,
        string $response
    ): Message;
}
