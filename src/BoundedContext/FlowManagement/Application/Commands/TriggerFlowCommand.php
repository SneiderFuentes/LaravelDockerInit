<?php

namespace Core\BoundedContext\FlowManagement\Application\Commands;

class TriggerFlowCommand
{
    private string $flowId;
    private string $channelType;
    private string $phoneNumber;
    private array $parameters;

    public function __construct(
        string $flowId,
        string $channelType,
        string $phoneNumber,
        array $parameters = []
    ) {
        $this->flowId = $flowId;
        $this->channelType = $channelType;
        $this->phoneNumber = $phoneNumber;
        $this->parameters = $parameters;
    }

    public function flowId(): string
    {
        return $this->flowId;
    }

    public function channelType(): string
    {
        return $this->channelType;
    }

    public function phoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }
}
