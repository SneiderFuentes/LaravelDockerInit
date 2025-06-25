<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetActiveEntitiesHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;

class GetActiveEntitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Retraso temporal para desarrollo (en segundos)
    public $delay;

    public function __construct(private string $resumeKey)
    {
        // Retraso configurable por variable de entorno (solo en desarrollo)
        if (app()->environment('local', 'development')) {
            $this->delay = (int) env('JOB_DELAY_SECONDS', 10); // Por defecto 10 segundos
        }
    }

    public function handle(GetActiveEntitiesHandler $handler, WebhookNotifierService $notifier)
    {
        $payload = [];
        try {
            $result = $handler->handle();
            $payload = [
                'status' => 'ok',
                'entities' => $result,
                'message' => 'Active entities retrieved successfully'
            ];
        } catch (\Throwable $e) {
            $payload = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
        $notifier->notifyFromConfig($this->resumeKey, $payload, 'GetActiveEntitiesJob - ');
    }
}
