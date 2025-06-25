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
        $model->subaccount_key = $message->getSubaccountKey();
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
        $model->subaccount_key = $message->getSubaccountKey();
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

    /**
     * Find a message by its external ID (provider message ID)
     *
     * @param string $externalId
     * @return Message|null
     */
    public function findByExternalId(string $externalId): ?Message
    {
        $model = MessageModel::where('message_id', $externalId)->first();

        if (!$model) {
            return null;
        }

        return $this->mapModelToEntity($model);
    }

    /**
     * Find messages with confirmed or cancelled responses
     *
     * @param string|null $subaccountKey Optional filter by subaccount key
     * @return Message[]
     */
    public function findActionableResponses(?string $subaccountKey = null): array
    {
        $confirmedResponses = ['confirmed', 'si', 'sí', 'confirmar', 'confirmo', 'ok', 'yes'];
        $cancelledResponses = ['canceled', 'cancelled', 'no', 'cancelar', 'cancelo', 'cancelado'];

        $query = MessageModel::whereNotNull('message_response')
            ->where(function ($query) use ($confirmedResponses, $cancelledResponses) {
                foreach ($confirmedResponses as $response) {
                    $query->orWhere('message_response', 'like', '%' . $response . '%');
                }
                foreach ($cancelledResponses as $response) {
                    $query->orWhere('message_response', 'like', '%' . $response . '%');
                }
            });

        // Si se proporciona un subaccount_key, filtrar por él
        if ($subaccountKey) {
            $query->where('subaccount_key', $subaccountKey);
        }

        $models = $query->get();

        return $models->map(function ($model) {
            return $this->mapModelToEntity($model);
        })->toArray();
    }

    /**
     * Find messages by subaccount key
     *
     * @param string $subaccountKey
     * @return Message[]
     */
    public function findBySubaccountKey(string $subaccountKey): array
    {
        $models = MessageModel::where('subaccount_key', $subaccountKey)->get();

        return $models->map(function ($model) {
            return $this->mapModelToEntity($model);
        })->toArray();
    }

    private function mapModelToEntity(MessageModel $model): Message
    {
        return new Message(
            (string) $model->id,
            (string) $model->appointment_id,
            (string) $model->patient_id,
            $model->phone_number,
            $model->content,
            MessageType::fromString($model->message_type),
            MessageStatus::fromString($model->status),
            $model->message_id,
            $model->message_response,
            $model->subaccount_key,
            $model->sent_at ? new DateTime($model->sent_at) : null,
            $model->delivered_at ? new DateTime($model->delivered_at) : null,
            $model->read_at ? new DateTime($model->read_at) : null,
            $model->created_at ? new DateTime($model->created_at) : null,
            $model->updated_at ? new DateTime($model->updated_at) : null
        );
    }

    private function mapEntityToModel(Message $message): array
    {
        return [
            'id' => $message->getId(),
            'appointment_id' => $message->getAppointmentId(),
            'patient_id' => $message->getPatientId(),
            'phone_number' => $message->getPhoneNumber(),
            'content' => $message->getContent(),
            'message_type' => $message->getType()->value(),
            'status' => $message->getStatus()->value(),
            'message_id' => $message->getMessageId(),
            'message_response' => $message->getMessageResponse(),
            'subaccount_key' => $message->getSubaccountKey(),
            'sent_at' => $message->getSentAt()?->format('Y-m-d H:i:s'),
            'delivered_at' => $message->getDeliveredAt()?->format('Y-m-d H:i:s'),
            'read_at' => $message->getReadAt()?->format('Y-m-d H:i:s'),
            'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $message->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }
}
