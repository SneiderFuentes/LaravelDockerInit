<?php

namespace Core\BoundedContext\FlowManagement\Application\DTOs;

class TriggerFlowDto
{
    public string $flowId;
    public string $channelType;
    public string $phoneNumber;
    public array $parameters;

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
}
