<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Repositories;

use Core\BoundedContext\CommunicationManagement\Domain\Entities\Message;

interface MessageRepositoryInterface
{
    /**
     * Save a new message
     *
     * @param Message $message
     * @return void
     */
    public function save(Message $message): void;

    /**
     * Update an existing message
     *
     * @param Message $message
     * @return void
     */
    public function update(Message $message): void;

    /**
     * Find a message by its ID
     *
     * @param string $id
     * @return Message|null
     */
    public function findById(string $id): ?Message;

    /**
     * Find a message by its external ID (provider message ID)
     *
     * @param string $externalId
     * @return Message|null
     */
    public function findByExternalId(string $externalId): ?Message;

    /**
     * Find a message by its message ID
     *
     * @param string $messageId
     * @return Message|null
     */
    public function findByMessageId(string $messageId): ?Message;

    /**
     * Find all messages for a specific appointment
     *
     * @param string $appointmentId
     * @return Message[]
     */
    public function findByAppointmentId(string $appointmentId): array;

    /**
     * Find all messages for a specific patient
     *
     * @param string $patientId
     * @return Message[]
     */
    public function findByPatientId(string $patientId): array;

    /**
     * Find all messages for a specific subaccount
     *
     * @param string $subaccountKey
     * @return Message[]
     */
    public function findBySubaccountKey(string $subaccountKey): array;

    /**
     * Retrieve all messages
     *
     * @return Message[]
     */
    public function findAll(): array;

    /**
     * Find messages with confirmed or cancelled responses
     *
     * @param string|null $subaccountKey Optional filter by subaccount key
     * @return Message[]
     */
    public function findActionableResponses(?string $subaccountKey = null): array;
}
