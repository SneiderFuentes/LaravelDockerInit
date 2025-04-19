<?php

namespace Core\BoundedContext\FlowManagement\Application\DTOs;

class FlowInfoDto
{
    public string $id;
    public string $name;
    public string $channelType;
    public bool $isActive;
    public array $steps;

    public function __construct(
        string $id,
        string $name,
        string $channelType,
        bool $isActive,
        array $steps = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->channelType = $channelType;
        $this->isActive = $isActive;
        $this->steps = $steps;
    }
}
