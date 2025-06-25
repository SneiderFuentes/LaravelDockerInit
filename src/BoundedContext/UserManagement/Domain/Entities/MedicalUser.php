<?php

declare(strict_types=1);

namespace Core\BoundedContext\UserManagement\Domain\Entities;

use DateTime;

final class MedicalUser
{
    private function __construct(
        private int $id,
        private string $realName,
        private string $username,
        private bool $isActive,
        private bool $isDoctor,
        private ?string $medicalLicense,
        private ?string $documentNumber,
        private ?int $specialtyId,
        private ?bool $agendaSpecial,
        private ?string $email,
        private ?DateTime $updatedAt,
        private ?DateTime $createdAt,
        private ?bool $activeAppointments,
        private ?string $permissionsAppointments
    ) {}

    public static function create(
        int $id,
        string $realName,
        string $username,
        bool $isActive,
        bool $isDoctor,
        ?string $medicalLicense = null,
        ?string $documentNumber = null,
        ?int $specialtyId = null,
        ?bool $agendaSpecial = null,
        ?string $email = null,
        ?DateTime $updatedAt = null,
        ?DateTime $createdAt = null,
        ?bool $activeAppointments = null,
        ?string $permissionsAppointments = null
    ): self {
        return new self(
            $id,
            $realName,
            $username,
            $isActive,
            $isDoctor,
            $medicalLicense,
            $documentNumber,
            $specialtyId,
            $agendaSpecial,
            $email,
            $updatedAt,
            $createdAt,
            $activeAppointments,
            $permissionsAppointments
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function realName(): string
    {
        return $this->realName;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isDoctor(): bool
    {
        return $this->isDoctor;
    }

    public function medicalLicense(): ?string
    {
        return $this->medicalLicense;
    }

    public function documentNumber(): ?string
    {
        return $this->documentNumber;
    }

    public function specialtyId(): ?int
    {
        return $this->specialtyId;
    }

    public function agendaSpecial(): ?bool
    {
        return $this->agendaSpecial;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function updatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function createdAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function activeAppointments(): ?bool
    {
        return $this->activeAppointments;
    }

    public function permissionsAppointments(): ?string
    {
        return $this->permissionsAppointments;
    }
}
