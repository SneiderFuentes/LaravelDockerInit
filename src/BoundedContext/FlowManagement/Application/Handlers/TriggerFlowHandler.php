<?php

namespace Core\BoundedContext\FlowManagement\Application\Handlers;

use Core\BoundedContext\FlowManagement\Application\Commands\TriggerFlowCommand;
use Core\BoundedContext\FlowManagement\Domain\Contracts\FlowRegistryInterface;
use Core\BoundedContext\FlowManagement\Domain\Exceptions\FlowNotFoundException;
use Core\BoundedContext\FlowManagement\Domain\ValueObjects\ChannelType;

class TriggerFlowHandler
{
    private FlowRegistryInterface $flowRegistry;

    public function __construct(FlowRegistryInterface $flowRegistry)
    {
        $this->flowRegistry = $flowRegistry;
    }

    public function handle(TriggerFlowCommand $command)
    {
        $flowId = $command->flowId();
        $channelType = ChannelType::fromString($command->channelType());

        $handler = $this->flowRegistry->getHandler($flowId, $channelType);

        if (!$handler) {
            throw new FlowNotFoundException($flowId, $channelType);
        }

        // Execute the flow handler
        return $handler->process(
            $command->phoneNumber(),
            $command->parameters()
        );
    }
}
