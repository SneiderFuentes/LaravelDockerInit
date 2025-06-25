<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\EntityRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;
use Illuminate\Support\Carbon;

class GetPatientByDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 60; // 1 minuto, es una consulta a BD, debería ser muy rápida.

    public function __construct(
        public string $document,
        public string $resumeKey
    ) {}

    public function handle(PatientRepositoryInterface $patientRepository, EntityRepository $entityRepository, WebhookNotifierService $notifier)
    {
        Log::info('----OBTENER PACIENTE Job en ejecución', ['attempts' => $this->attempts()]);
        try {
            $patient = $patientRepository->findByDocument($this->document);

            if (!$patient) {
                $payload = [
                    'status' => 'not_found',
                    'message' => 'Patient not found'
                ];
                $notifier->notifyFromConfig($this->resumeKey, $payload, 'GetPatientByDocumentJob - ');
                return;
            }

            $patient['age'] = $patient['birth_date'] ? Carbon::parse($patient['birth_date'])->age : null;
            $entity = null;
            $entityName = 'N/A';
            $isEntityActive = false;

            if (!empty($patient['entity_code'])) {
                $entity = $entityRepository->findByCode($patient['entity_code']);
            }

            if ($entity) {
                $entityName = $entity['name'] ?? 'N/A';
                $isEntityActive = ($entity['is_active'] ?? 0) === -1;
            }

            $patient['entity_name'] = $entityName;
            $patient['is_entity_active'] = $isEntityActive;

            $payload = [
                'status' => 'ok',
                'patient_id' => $patient['id'] ?? null,
                'patient' => $patient,
                'message' => 'Patient found successfully'
            ];

            $notifier->notifyFromConfig($this->resumeKey, $payload, 'GetPatientByDocumentJob - ');
        } catch (\Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $errorPayload = ['status' => 'error', 'message' => 'No pudimos consultar la información del paciente. Inténtalo de nuevo más tarde.'];
                $notifier->notifyFromConfig($this->resumeKey, $errorPayload, 'GetPatientByDocumentJob - FINAL ATTEMPT - ');
            }
            throw $e;
        }
    }
}
