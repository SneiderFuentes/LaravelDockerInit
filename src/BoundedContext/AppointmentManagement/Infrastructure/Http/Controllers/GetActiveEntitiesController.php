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
        $lines = array_map(function($entity, $index) {
            return ($index + 1) . " - {$entity['name']}";
        }, $entities, array_keys($entities));
        $text = implode(PHP_EOL, $lines);
        return response($text, 200, ['Content-Type' => 'text/plain']);
    }
}
