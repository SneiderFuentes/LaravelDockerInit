<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Application\DTOs;

use Core\BoundedContext\SubaccountManagement\Domain\Entities\Subaccount;
use DateTime;

final class SubaccountDto
{
    private function __construct(
        private string $id,
        private string $key,
        private string $name,
        private array $config,
        private DateTime $createdAt,
        private DateTime $updatedAt
    ) {}

    public static function fromEntity(Subaccount $subaccount): self
    {
        return new self(
            $subaccount->id(),
            $subaccount->key(),
            $subaccount->name(),
            [
                'connection' => $subaccount->config()->connection(),
                'tables' => $subaccount->config()->tables(),
                'connections' => $subaccount->config()->connections(),
                'apiHeader' => $subaccount->config()->apiHeader(),
                'apiKey' => $subaccount->config()->apiKey(),
            ],

            $subaccount->createdAt(),
            $subaccount->updatedAt()
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function config(): array
    {
        return $this->config;
    }

    public function createdAt(): DateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'config' => $this->config,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
