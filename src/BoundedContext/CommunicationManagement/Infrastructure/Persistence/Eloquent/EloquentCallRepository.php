<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent;

use Core\BoundedContext\CommunicationManagement\Domain\Entities\Call;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\CallRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\CallStatus;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\CallType;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent\Models\CallModel;
use DateTime;
use Illuminate\Support\Str;

final class EloquentCallRepository implements CallRepositoryInterface
{
    public function save(Call $call): void
    {
        $model = new CallModel();
        $model->id = $call->getId();
        $model->appointment_id = $call->getAppointmentId();
        $model->patient_id = $call->getPatientId();
        $model->phone_number = $call->getPhoneNumber();
        $model->status = $call->getStatus()->value();
        $model->call_type = $call->getType()->value();
        $model->call_id = $call->getCallId();
        $model->flow_id = $call->getFlowId();
        $model->start_time = $call->getStartTime();
        $model->end_time = $call->getEndTime();
        $model->duration = $call->getDuration();
        $model->response_data = json_encode($call->getResponseData());
        $model->created_at = $call->getCreatedAt();
        $model->updated_at = $call->getUpdatedAt();
        $model->save();
    }

    public function findById(string $id): ?Call
    {
        $model = CallModel::find($id);

        if (null === $model) {
            return null;
        }

        return $this->mapModelToEntity($model);
    }

    public function findByCallId(string $callId): ?Call
    {
        $model = CallModel::where('call_id', $callId)->first();

        if (null === $model) {
            return null;
        }

        return $this->mapModelToEntity($model);
    }

    public function findByAppointmentId(string $appointmentId): ?Call
    {
        $model = CallModel::where('appointment_id', $appointmentId)->first();

        if (null === $model) {
            return null;
        }

        return $this->mapModelToEntity($model);
    }

    public function findByPatientId(string $patientId): array
    {
        $models = CallModel::where('patient_id', $patientId)->get();

        return $models->map(function ($model) {
            return $this->mapModelToEntity($model);
        })->toArray();
    }

    public function update(Call $call): void
    {
        $model = CallModel::find($call->getId());

        if (null === $model) {
            throw new \InvalidArgumentException("Call with id {$call->getId()} not found");
        }

        $model->appointment_id = $call->getAppointmentId();
        $model->patient_id = $call->getPatientId();
        $model->phone_number = $call->getPhoneNumber();
        $model->status = $call->getStatus()->value();
        $model->call_type = $call->getType()->value();
        $model->call_id = $call->getCallId();
        $model->flow_id = $call->getFlowId();
        $model->start_time = $call->getStartTime();
        $model->end_time = $call->getEndTime();
        $model->duration = $call->getDuration();
        $model->response_data = json_encode($call->getResponseData());
        $model->updated_at = $call->getUpdatedAt();
        $model->save();
    }

    private function mapModelToEntity(CallModel $model): Call
    {
        return new Call(
            $model->id,
            $model->appointment_id,
            $model->patient_id,
            $model->phone_number,
            CallStatus::fromString($model->status),
            CallType::fromString($model->call_type),
            $model->call_id,
            $model->flow_id,
            $model->start_time ? new DateTime($model->start_time) : null,
            $model->end_time ? new DateTime($model->end_time) : null,
            $model->duration,
            $model->response_data ? json_decode($model->response_data, true) : null,
            new DateTime($model->created_at),
            new DateTime($model->updated_at)
        );
    }
}
