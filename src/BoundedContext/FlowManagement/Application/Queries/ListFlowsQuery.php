<?php

namespace Core\BoundedContext\FlowManagement\Application\Queries;

class ListFlowsQuery
{
    private ?string $channelType;

    public function __construct(?string $channelType = null)
    {
        $this->channelType = $channelType;
    }

    public function channelType(): ?string
    {
        return $this->channelType;
    }
}
