<?php

namespace Core\BoundedContext\FlowManagement\Domain\Entities;

use Core\BoundedContext\FlowManagement\Domain\ValueObjects\FlowId;
use Core\BoundedContext\FlowManagement\Domain\ValueObjects\ChannelType;

class FlowDefinition
{
    private FlowId $id;
    private string $name;
    private ChannelType $channelType;
    private array $steps;
    private bool $active;

    public function __construct(
        FlowId $id,
        string $name,
        ChannelType $channelType,
        array $steps,
        bool $active = true
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->channelType = $channelType;
        $this->steps = $steps;
        $this->active = $active;
    }

    public function id(): FlowId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function channelType(): ChannelType
    {
        return $this->channelType;
    }

    public function steps(): array
    {
        return $this->steps;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate(): self
    {
        $this->active = true;
        return $this;
    }

    public function deactivate(): self
    {
        $this->active = false;
        return $this;
    }

    public function updateSteps(array $steps): self
    {
        $this->steps = $steps;
        return $this;
    }
}
