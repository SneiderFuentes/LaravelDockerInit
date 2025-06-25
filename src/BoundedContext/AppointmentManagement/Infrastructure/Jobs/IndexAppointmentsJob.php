<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\ListPendingAppointmentsHandler;
use Core\BoundedContext\AppointmentManagement\Application\Queries\ListPendingAppointmentsQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DateTime;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;

/**
 * Estructura JSON enviada al webhook:
 * {
 *   "status": "ok" | "error",
 *   "message": "...",
 *   "data": [...],
 *   ...extra data...
 * }
 */
class IndexAppointmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    private array $requestData;
    private string $centerKey;
    private string $resumeKey;

    public function __construct(array $requestData, string $centerKey, string $resumeKey)
    {
        $this->requestData = $requestData;
        $this->centerKey = $centerKey;
        $this->resumeKey = $resumeKey;
    }

    public function handle(ListPendingAppointmentsHandler $handler, WebhookNotifierService $notifier): void
    {
        $payload = [];
        try {
            $startDate = isset($this->requestData['start_date']) ? new \DateTime($this->requestData['start_date']) : null;
            $endDate = isset($this->requestData['end_date']) ? new \DateTime($this->requestData['end_date']) : null;
            $query = new ListPendingAppointmentsQuery(
                $this->centerKey,
                $startDate,
                $endDate
            );
            $appointments = $handler->handle($query);
            $payload = [
                'status' => 'ok',
                'message' => 'Appointments listed successfully',
                'data' => array_map(fn($dto) => $dto->toArray(), $appointments)
            ];
        } catch (\Throwable $e) {
            $payload = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            Log::error('IndexAppointmentsJob error: ' . $e->getMessage());
        }
        $notifier->notifyFromConfig($this->resumeKey, $payload, 'IndexAppointmentsJob - ');
    }
}
