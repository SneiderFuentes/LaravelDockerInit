<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Domain\Entities;

use Core\BoundedContext\SubaccountManagement\Domain\ValueObjects\SubaccountConfig;
use DateTime;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class Subaccount
{
    private string $id;
    private string $key;
    private string $name;
    private SubaccountConfig $config;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        string $id,
        string $key,
        string $name,
        SubaccountConfig $config,
        DateTime $createdAt,
        DateTime $updatedAt
    ) {
        if (empty($key)) {
            throw new InvalidArgumentException('Subaccount key cannot be empty');
        }

        if (empty($name)) {
            throw new InvalidArgumentException('Subaccount name cannot be empty');
        }

        $this->id = $id;
        $this->key = $key;
        $this->name = $name;
        $this->config = $config;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function create(
        string $key,
        string $name,
        SubaccountConfig $config
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $key,
            $name,
            $config,
            new DateTime(),
            new DateTime()
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

    public function config(): SubaccountConfig
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

    public function updateName(string $name): self
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Subaccount name cannot be empty');
        }

        $clone = clone $this;
        $clone->name = $name;
        $clone->updatedAt = new DateTime();

        return $clone;
    }

    public function updateConfig(SubaccountConfig $config): self
    {
        $clone = clone $this;
        $clone->config = $config;
        $clone->updatedAt = new DateTime();

        return $clone;
    }
}
