<?php

namespace Core\BoundedContext\FlowManagement\Infrastructure\Adapters;

use Core\BoundedContext\FlowManagement\Domain\Contracts\FlowHandlerInterface;
use Core\BoundedContext\FlowManagement\Domain\Contracts\FlowRegistryInterface;
use Core\BoundedContext\FlowManagement\Domain\ValueObjects\ChannelType;

class InMemoryFlowRegistry implements FlowRegistryInterface
{
    /**
     * @var array<string, array<string, FlowHandlerInterface>>
     */
    private array $handlers = [];

    public function register(FlowHandlerInterface $handler): void
    {
        $flowId = $handler->getFlowId();
        $channelType = $handler->getChannelType()->value();

        if (!isset($this->handlers[$channelType])) {
            $this->handlers[$channelType] = [];
        }

        $this->handlers[$channelType][$flowId] = $handler;
    }

    public function unregister(string $flowId, ChannelType $channelType): void
    {
        $channelTypeValue = $channelType->value();

        if (isset($this->handlers[$channelTypeValue][$flowId])) {
            unset($this->handlers[$channelTypeValue][$flowId]);

            // If no more handlers for this channel, clean up
            if (empty($this->handlers[$channelTypeValue])) {
                unset($this->handlers[$channelTypeValue]);
            }
        }
    }

    public function getHandler(string $flowId, ChannelType $channelType): ?FlowHandlerInterface
    {
        $channelTypeValue = $channelType->value();

        return $this->handlers[$channelTypeValue][$flowId] ?? null;
    }

    public function getAllHandlers(): array
    {
        $allHandlers = [];

        foreach ($this->handlers as $channelHandlers) {
            foreach ($channelHandlers as $handler) {
                $allHandlers[] = $handler;
            }
        }

        return $allHandlers;
    }

    public function getHandlersByChannel(ChannelType $channelType): array
    {
        $channelTypeValue = $channelType->value();

        return $this->handlers[$channelTypeValue] ?? [];
    }
}
