<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\DTOs;

class PatientDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $document_type,
        public readonly string $document_number,
        public readonly string $first_name,
        public readonly ?string $second_name,
        public readonly string $first_surname,
        public readonly ?string $second_surname,
        public readonly string $full_name,
        public readonly string $gender,
        public readonly string $birth_date,
        public readonly string $birth_place,
        public readonly int $marital_status,
        public readonly string $address,
        public readonly string $phone,
        public readonly string $email,
        public readonly string $occupation,
        public readonly string $entity_code,
        public readonly int $user_type,
        public readonly string $affiliation_type,
        public readonly string $zone,
        public readonly int $level,
        public readonly int $education_level,
        public readonly int $country_code,
        public readonly string $city_code
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['document_type'],
            $data['document_number'],
            $data['first_name'],
            $data['second_name'] ?? null,
            $data['first_surname'],
            $data['second_surname'] ?? null,
            $data['full_name'],
            $data['gender'],
            $data['birth_date'],
            $data['birth_place'],
            (int)$data['marital_status'],
            $data['address'],
            $data['phone'],
            $data['email'],
            $data['occupation'],
            $data['entity_code'],
            (int)$data['user_type'],
            $data['affiliation_type'],
            $data['zone'],
            (int)$data['level'],
            (int)$data['education_level'],
            (int)$data['country_code'],
            $data['city_code']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'first_name' => $this->first_name,
            'second_name' => $this->second_name,
            'first_surname' => $this->first_surname,
            'second_surname' => $this->second_surname,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date,
            'birth_place' => $this->birth_place,
            'marital_status' => $this->marital_status,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'occupation' => $this->occupation,
            'entity_code' => $this->entity_code,
            'user_type' => $this->user_type,
            'affiliation_type' => $this->affiliation_type,
            'zone' => $this->zone,
            'level' => $this->level,
            'education_level' => $this->education_level,
            'country_code' => $this->country_code,
            'city_code' => $this->city_code,
        ];
    }
}
