<?php

namespace Core\BoundedContext\FlowManagement\Application\Handlers;

use Core\BoundedContext\FlowManagement\Application\Commands\RegisterFlowHandlerCommand;
use Core\BoundedContext\FlowManagement\Domain\Contracts\FlowHandlerInterface;
use Core\BoundedContext\FlowManagement\Domain\Contracts\FlowRegistryInterface;
use Illuminate\Contracts\Container\Container;

class RegisterFlowHandlerHandler
{
    private FlowRegistryInterface $flowRegistry;
    private Container $container;

    public function __construct(FlowRegistryInterface $flowRegistry, Container $container)
    {
        $this->flowRegistry = $flowRegistry;
        $this->container = $container;
    }

    public function handle(RegisterFlowHandlerCommand $command): void
    {
        $handlerClass = $command->handlerClass();

        if (!class_exists($handlerClass)) {
            throw new \InvalidArgumentException("Handler class {$handlerClass} does not exist");
        }

        $reflection = new \ReflectionClass($handlerClass);
        if (!$reflection->implementsInterface(FlowHandlerInterface::class)) {
            throw new \InvalidArgumentException(
                "Handler class {$handlerClass} must implement " . FlowHandlerInterface::class
            );
        }

        // Resolve the handler from the container
        $handler = $this->container->make($handlerClass);

        // Register the handler
        $this->flowRegistry->register($handler);
    }
}
