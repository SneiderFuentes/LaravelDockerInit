<?php

namespace Core\BoundedContext\FlowManagement\Domain\Contracts;

use Core\BoundedContext\FlowManagement\Domain\ValueObjects\ChannelType;

interface FlowHandlerInterface
{
    /**
     * Get the flow identifier this handler can process
     *
     * @return string
     */
    public function getFlowId(): string;

    /**
     * Get the channel type this handler supports
     *
     * @return ChannelType
     */
    public function getChannelType(): ChannelType;

    /**
     * Process an incoming trigger with the given parameters
     *
     * @param string $phoneNumber
     * @param array $parameters
     * @return mixed
     */
    public function process(string $phoneNumber, array $parameters = []);
}
