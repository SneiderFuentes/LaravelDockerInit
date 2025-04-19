<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Repositories;

use Core\BoundedContext\CommunicationManagement\Domain\Entities\Message;

interface MessageRepositoryInterface
{
    public function save(Message $message): void;

    public function findById(string $id): ?Message;

    public function findByMessageId(string $messageId): ?Message;

    public function findByAppointmentId(string $appointmentId): array;

    public function findByPatientId(string $patientId): array;

    public function update(Message $message): void;

    /**
     * Retrieve all messages
     *
     * @return Message[]
     */
    public function findAll(): array;
}
