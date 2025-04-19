<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent;

use Core\BoundedContext\CommunicationManagement\Domain\Entities\Message;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\MessageRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageStatus;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageType;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent\Models\MessageModel;
use DateTime;
use Illuminate\Support\Str;

final class EloquentMessageRepository implements MessageRepositoryInterface
{
    public function save(Message $message): void
    {
        $model = new MessageModel();
        $model->id = $message->getId();
        $model->appointment_id = $message->getAppointmentId();
        $model->patient_id = $message->getPatientId();
        $model->phone_number = $message->getPhoneNumber();
        $model->content = $message->getContent();
        $model->message_type = $message->getType()->value();
        $model->status = $message->getStatus()->value();
        $model->message_id = $message->getMessageId();
        $model->message_response = $message->getMessageResponse();
        $model->sent_at = $message->getSentAt();
        $model->delivered_at = $message->getDeliveredAt();
        $model->read_at = $message->getReadAt();
        $model->created_at = $message->getCreatedAt();
        $model->updated_at = $message->getUpdatedAt();
        $model->save();
    }

    public function findById(string $id): ?Message
    {
        $model = MessageModel::find($id);

        if (null === $model) {
            return null;
        }

        return $this->mapModelToEntity($model);
    }

    public function findByMessageId(string $messageId): ?Message
    {
        $model = MessageModel::where('message_id', $messageId)->first();

        if (null === $model) {
            return null;
        }

        return $this->mapModelToEntity($model);
    }

    public function findByAppointmentId(string $appointmentId): array
    {
        $models = MessageModel::where('appointment_id', $appointmentId)->get();

        return $models->map(function ($model) {
            return $this->mapModelToEntity($model);
        })->toArray();
    }

    public function findByPatientId(string $patientId): array
    {
        $models = MessageModel::where('patient_id', $patientId)->get();

        return $models->map(function ($model) {
            return $this->mapModelToEntity($model);
        })->toArray();
    }

    public function update(Message $message): void
    {
        $model = MessageModel::find($message->getId());

        if (null === $model) {
            throw new \InvalidArgumentException("Message with id {$message->getId()} not found");
        }

        $model->appointment_id = $message->getAppointmentId();
        $model->patient_id = $message->getPatientId();
        $model->phone_number = $message->getPhoneNumber();
        $model->content = $message->getContent();
        $model->message_type = $message->getType()->value();
        $model->status = $message->getStatus()->value();
        $model->message_id = $message->getMessageId();
        $model->message_response = $message->getMessageResponse();
        $model->sent_at = $message->getSentAt();
        $model->delivered_at = $message->getDeliveredAt();
        $model->read_at = $message->getReadAt();
        $model->updated_at = $message->getUpdatedAt();
        $model->save();
    }

    public function findAll(): array
    {
        $models = MessageModel::all();

        return $models->map(function ($model) {
            return $this->mapModelToEntity($model);
        })->toArray();
    }

    private function mapModelToEntity(MessageModel $model): Message
    {
        return new Message(
            $model->id,
            $model->appointment_id,
            $model->patient_id,
            $model->phone_number,
            $model->content,
            MessageType::fromString($model->message_type),
            MessageStatus::fromString($model->status),
            $model->message_id,
            $model->message_response,
            $model->sent_at ? new DateTime($model->sent_at) : null,
            $model->delivered_at ? new DateTime($model->delivered_at) : null,
            $model->read_at ? new DateTime($model->read_at) : null,
            new DateTime($model->created_at),
            new DateTime($model->updated_at)
        );
    }
}
