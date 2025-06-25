<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Services\CalculateGlomerularFiltrationService;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateGlomerularFiltrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 90; // 1.5 minutos, más que suficiente para una llamada a la API de chat.

    public function __construct(
        private array $data,
        private string $resumeKey
    ) {}

    public function handle(CalculateGlomerularFiltrationService $calculator, WebhookNotifierService $notifier): void
    {
        Log::info('----CALCULAR FILTRACION GLOMERULAR Job en ejecución', ['attempts' => $this->attempts()]);

        try {
            $payload = $calculator->calculate($this->data);
            $notifier->notifyFromConfig($this->resumeKey, $payload, 'CalculateGlomerularFiltrationJob - ');
        } catch (\Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $errorPayload = ['status' => 'error', 'message' => 'Lo sentimos, no pudimos realizar el cálculo en este momento. Por favor, intenta de nuevo más tarde.'];
                $notifier->notifyFromConfig($this->resumeKey, $errorPayload, 'CalculateGlomerularFiltrationJob - FINAL ATTEMPT - ');
            }
            throw $e;
        }
    }
}
