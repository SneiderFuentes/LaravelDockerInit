<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Services;

use Core\BoundedContext\CommunicationManagement\Domain\Ports\MessageGatewayInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;

class SendAppointmentReminderService
{
    private MessageGatewayInterface $messageGateway;

    public function __construct(MessageGatewayInterface $messageGateway)
    {
        $this->messageGateway = $messageGateway;
    }

    public function sendReminder(
        string $phoneNumber,
        string $patientName,
        string $appointmentDate,
        string $appointmentTime,
        string $doctorName
    ): string {
        $phone = new PhoneNumber($phoneNumber);

        $parameters = [
            'patient_name' => $patientName,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'doctor_name' => $doctorName
        ];

        return $this->messageGateway->sendTemplateMessage(
            $phone,
            'appointment_reminder',
            $parameters
        );
    }
}
