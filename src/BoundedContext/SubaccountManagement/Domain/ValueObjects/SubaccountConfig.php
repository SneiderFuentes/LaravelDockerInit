<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Domain\ValueObjects;

use InvalidArgumentException;

final class SubaccountConfig
{
    private string $key;
    private string $name;
    private string $connection;
    private array $connections;
    private array $tables;
    private ?string $apiHeader;
    private ?string $apiKey;

    public function __construct(
        string $key,
        string $name,
        string $connection,
        array $tables,
        array $connections = [],
        ?string $apiHeader = null,
        ?string $apiKey = null
    ) {
        if (empty($connection)) {
            throw new InvalidArgumentException('Connection cannot be empty');
        }

        if (empty($tables)) {
            throw new InvalidArgumentException('Tables configuration cannot be empty');
        }

        $this->key = $key;
        $this->name = $name;
        $this->connection = $connection;
        $this->tables = $tables;
        $this->connections = $connections;
        $this->apiHeader = $apiHeader;
        $this->apiKey = $apiKey;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function connection(): string
    {
        return $this->connection;
    }

    public function connections(): array
    {
        return $this->connections;
    }

    public function tables(): array
    {
        return $this->tables;
    }

    public function tableName(string $type): string
    {
        if (!isset($this->tables[$type]) || !isset($this->tables[$type]['table'])) {
            throw new InvalidArgumentException("Table '{$type}' not found");
        }

        return $this->tables[$type]['table'];
    }

    public function tableConfig(string $type): array
    {
        return $this->tables[$type] ?? [];
    }

    public function mapping(string $type): array
    {
        if (!isset($this->tables[$type]) || !isset($this->tables[$type]['mapping'])) {
            throw new InvalidArgumentException("Mapping for table '{$type}' not found");
        }

        return $this->tables[$type]['mapping'];
    }

    public function apiHeader(): ?string
    {
        return $this->apiHeader;
    }

    public function apiKey(): ?string
    {
        return $this->apiKey;
    }

    public static function fromArray(array $config): self
    {
        if (!isset($config['connection']) || !isset($config['tables'])) {
            throw new InvalidArgumentException('Invalid configuration format');
        }

        return new self(
            $config['key'],
            $config['name'],
            $config['connection'],
            $config['tables'],
            $config['connections'] ?? [],
            $config['api_header'] ?? null,
            $config['api_key'] ?? null
        );
    }
}
