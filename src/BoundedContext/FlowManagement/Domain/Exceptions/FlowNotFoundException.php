<?php

namespace Core\BoundedContext\FlowManagement\Domain\Exceptions;

use Core\BoundedContext\FlowManagement\Domain\ValueObjects\ChannelType;
use Exception;

class FlowNotFoundException extends Exception
{
    private string $flowId;
    private ChannelType $channelType;

    public function __construct(string $flowId, ChannelType $channelType)
    {
        $this->flowId = $flowId;
        $this->channelType = $channelType;

        parent::__construct(
            sprintf(
                'Flow with id "%s" for channel "%s" not found',
                $flowId,
                $channelType->value()
            )
        );
    }

    public function flowId(): string
    {
        return $this->flowId;
    }

    public function channelType(): ChannelType
    {
        return $this->channelType;
    }
}
