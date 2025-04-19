<?php

namespace Core\BoundedContext\CommunicationManagement\Domain\Entities;

use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\PhoneNumber;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\FlowId;

class CommunicationJob
{
    private string $id;
    private string $type;
    private PhoneNumber $to;
    private FlowId $flowId;
    private array $params;
    private string $status;
    private ?\DateTimeImmutable $processedAt;

    public function __construct(
        string $id,
        string $type,
        PhoneNumber $to,
        FlowId $flowId,
        array $params,
        string $status = 'pending',
        ?\DateTimeImmutable $processedAt = null
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->to = $to;
        $this->flowId = $flowId;
        $this->params = $params;
        $this->status = $status;
        $this->processedAt = $processedAt;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function to(): PhoneNumber
    {
        return $this->to;
    }

    public function flowId(): FlowId
    {
        return $this->flowId;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function processedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function markAsProcessed(): self
    {
        $this->status = 'processed';
        $this->processedAt = new \DateTimeImmutable();
        return $this;
    }

    public function markAsFailed(): self
    {
        $this->status = 'failed';
        $this->processedAt = new \DateTimeImmutable();
        return $this;
    }
}
