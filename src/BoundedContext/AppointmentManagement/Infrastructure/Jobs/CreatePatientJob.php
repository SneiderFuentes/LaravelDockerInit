<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\CreatePatientHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;

class CreatePatientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 60; // 1 minuto, escritura en BD, debería ser rápida.

    public array $data;
    public string $resumeKey;

    public function __construct(array $data, string $resumeKey)
    {
        $this->data = $data;
        $this->resumeKey = $resumeKey;
    }

    public function handle(CreatePatientHandler $handler, PatientRepositoryInterface $repository, WebhookNotifierService $notifier)
    {
        try {
            Log::info('----CREAR PACIENTE Job en ejecución', ['attempts' => $this->attempts()]);
            $patientId = $handler->handle($this->data);

            // Si llegamos aquí, el paciente fue creado. Cualquier otra excepción habría hecho que el job fallara.
            $patient = $repository->findByDocument($this->data['document_number']);

            $payload = [
                'status' => 'ok',
                'patient_id' => $patientId,
                'patient' => $patient,
                'message' => 'Patient created successfully'
            ];

            $notifier->notifyFromConfig($this->resumeKey, $payload, 'CreatePatientJob - ');
        } catch (\InvalidArgumentException $e) {
            // Este es un resultado de negocio esperado (el paciente ya existe).
            // Notificamos al webhook y terminamos el job exitosamente.
            $payload = [
                'status' => 'exist',
                'message' => $e->getMessage(),
            ];
            $notifier->notifyFromConfig($this->resumeKey, $payload, 'CreatePatientJob - ');
            return;
        } catch (\Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $errorPayload = ['status' => 'error', 'message' => 'No pudimos crear el paciente en este momento. Inténtalo de nuevo más tarde.'];
                $notifier->notifyFromConfig($this->resumeKey, $errorPayload, 'CreatePatientJob - FINAL ATTEMPT - ');
            }
            throw $e;
        }
    }
}
