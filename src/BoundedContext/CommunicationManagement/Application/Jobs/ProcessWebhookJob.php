<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Jobs;

use Core\BoundedContext\CommunicationManagement\Application\Services\HandleInboundWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Los datos del webhook a procesar
     *
     * @var array
     */
    private array $webhookData;

    /**
     * Create a new job instance.
     *
     * @param array $webhookData
     * @return void
     */
    public function __construct(array $webhookData)
    {
        $this->webhookData = $webhookData;
    }

    /**
     * Execute the job.
     *
     * @param HandleInboundWebhookService $service
     * @return void
     */
    public function handle(HandleInboundWebhookService $service): void
    {
        $service->handleIncomingMessage($this->webhookData);
    }
}
