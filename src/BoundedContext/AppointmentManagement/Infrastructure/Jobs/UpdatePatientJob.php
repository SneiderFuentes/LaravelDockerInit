<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\UpdatePatientHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;

class UpdatePatientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 60;

    public array $data;
    public string $resumeKey;

    public function __construct(array $data, string $resumeKey)
    {
        $this->data = $data;
        $this->resumeKey = $resumeKey;
    }

    public function handle(UpdatePatientHandler $handler, PatientRepositoryInterface $repository, WebhookNotifierService $notifier)
    {
        try {
            Log::info('----ACTUALIZAR PACIENTE Job en ejecución', ['attempts' => $this->attempts()]);
            $patientId = $handler->handle($this->data);

            $patient = $repository->findByDocument($this->data['document_number']);

            $payload = [
                'status' => 'ok',
                'patient_id' => $patientId,
                'patient' => $patient,
                'message' => 'Patient updated successfully'
            ];

            $notifier->notifyFromConfig($this->resumeKey, $payload, 'UpdatePatientJob - ');
        } catch (\InvalidArgumentException $e) {
            $payload = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            $notifier->notifyFromConfig($this->resumeKey, $payload, 'UpdatePatientJob - ');
            return;
        } catch (\Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $errorPayload = ['status' => 'error', 'message' => 'No pudimos actualizar el paciente en este momento. Inténtalo de nuevo más tarde.'];
                $notifier->notifyFromConfig($this->resumeKey, $errorPayload, 'UpdatePatientJob - FINAL ATTEMPT - ');
            }
            throw $e;
        }
    }
}

