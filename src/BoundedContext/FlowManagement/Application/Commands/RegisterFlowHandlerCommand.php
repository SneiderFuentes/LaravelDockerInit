<?php

namespace Core\BoundedContext\FlowManagement\Application\Commands;

class RegisterFlowHandlerCommand
{
    private string $handlerClass;

    public function __construct(string $handlerClass)
    {
        $this->handlerClass = $handlerClass;
    }

    public function handlerClass(): string
    {
        return $this->handlerClass;
    }
}
