<?php

namespace Core\BoundedContext\FlowManagement\Domain\Contracts;

use Core\BoundedContext\FlowManagement\Domain\ValueObjects\ChannelType;

interface FlowRegistryInterface
{
    /**
     * Register a flow handler
     *
     * @param FlowHandlerInterface $handler
     * @return void
     */
    public function register(FlowHandlerInterface $handler): void;

    /**
     * Unregister a flow handler
     *
     * @param string $flowId
     * @param ChannelType $channelType
     * @return void
     */
    public function unregister(string $flowId, ChannelType $channelType): void;

    /**
     * Get a flow handler for a specific flow ID and channel type
     *
     * @param string $flowId
     * @param ChannelType $channelType
     * @return FlowHandlerInterface|null
     */
    public function getHandler(string $flowId, ChannelType $channelType): ?FlowHandlerInterface;

    /**
     * Get all registered handlers
     *
     * @return array<FlowHandlerInterface>
     */
    public function getAllHandlers(): array;

    /**
     * Get handlers for a specific channel type
     *
     * @param ChannelType $channelType
     * @return array<FlowHandlerInterface>
     */
    public function getHandlersByChannel(ChannelType $channelType): array;
}
