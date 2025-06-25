<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetActiveEntitiesHandler;
use Illuminate\Http\Request;

class GetActiveEntitiesController
{
    public function __construct(private GetActiveEntitiesHandler $handler) {}

    public function __invoke(Request $request)
    {
        $entities = $this->handler->handle();
        $lines = array_map(fn($e) => "{$e['code']} - {$e['name']}", $entities);
        $text = implode(PHP_EOL, $lines);
        return response($text, 200, ['Content-Type' => 'text/plain']);
    }
}
