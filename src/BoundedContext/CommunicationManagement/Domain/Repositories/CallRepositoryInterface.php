<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Repositories;

use Core\BoundedContext\CommunicationManagement\Domain\Entities\Call;

interface CallRepositoryInterface
{
    public function save(Call $call): void;

    public function findById(string $id): ?Call;

    public function findByCallId(string $callId): ?Call;

    public function findByAppointmentId(string $appointmentId): ?Call;

    public function findByPatientId(string $patientId): array;

    public function update(Call $call): void;
}
