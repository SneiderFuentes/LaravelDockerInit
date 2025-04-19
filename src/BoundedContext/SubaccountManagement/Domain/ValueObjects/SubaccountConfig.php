<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Domain\ValueObjects;

use InvalidArgumentException;

final class SubaccountConfig
{
    private string $connection;
    private array $tables;

    public function __construct(string $connection, array $tables)
    {
        if (empty($connection)) {
            throw new InvalidArgumentException('Connection cannot be empty');
        }

        if (empty($tables)) {
            throw new InvalidArgumentException('Tables configuration cannot be empty');
        }

        $this->connection = $connection;
        $this->tables = $tables;
    }

    public function connection(): string
    {
        return $this->connection;
    }

    public function tables(): array
    {
        return $this->tables;
    }

    public function mapping(string $tableKey): array
    {
        if (!isset($this->tables[$tableKey]) || !isset($this->tables[$tableKey]['mapping'])) {
            throw new InvalidArgumentException("Mapping for table '{$tableKey}' not found");
        }

        return $this->tables[$tableKey]['mapping'];
    }

    public function tableName(string $tableKey): string
    {
        if (!isset($this->tables[$tableKey]) || !isset($this->tables[$tableKey]['table'])) {
            throw new InvalidArgumentException("Table '{$tableKey}' not found");
        }

        return $this->tables[$tableKey]['table'];
    }

    public static function fromArray(array $config): self
    {
        if (!isset($config['connection']) || !isset($config['tables'])) {
            throw new InvalidArgumentException('Invalid configuration format');
        }

        return new self($config['connection'], $config['tables']);
    }
}
