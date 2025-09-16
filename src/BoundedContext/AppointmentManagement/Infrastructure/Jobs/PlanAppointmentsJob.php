<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Services\AppointmentGrouperAIService;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\CupProcedureRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\EntityRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\SoatRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PlanAppointmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 120; // 2 minutos, para dar tiempo de sobra a la IA.

    public function __construct(
        private array $data,
        private ?string $patientId,
        private string $clientType,
        private string $planId,
        private string $resumeKey
    ) {}

    public function handle(
        PatientRepositoryInterface $patientRepo,
        EntityRepositoryInterface $entityRepo,
        CupProcedureRepositoryInterface $cupRepo,
        SoatRepositoryInterface $soatRepo,
        AppointmentGrouperAIService $grouper,
        WebhookNotifierService $notifier,
        AppointmentRepositoryInterface $appointmentRepo
    ): void {
        Log::info('----PLANEAR CITAS Job en ejecución', ['attempts' => $this->attempts()]);
        try {
            $enrichResult = $this->enrichProcedures($patientRepo, $entityRepo, $cupRepo, $soatRepo, $appointmentRepo);
            $enrichedProcedures = $enrichResult['enriched'];
            $rejectedProcedures = $enrichResult['rejected'];
            $alreadyScheduledProcedures = $enrichResult['already_scheduled'];

            // 1. Agrupar procedimientos por especialidad_id
            $proceduresByServiceName = [];
            foreach ($enrichedProcedures as $proc) {
                $serviceName = $proc['service_name'] ?? 'default';
                $proceduresByServiceName[$serviceName][] = $proc;
            }
            Log::info('Procedures enrichment result', [
                'plan_id' => $this->planId,
                'enriched_procedures' => $enrichedProcedures,
                'rejected_procedures' => $rejectedProcedures,
                'already_scheduled_procedures' => $alreadyScheduledProcedures,
                'procedures_by_service_name' => $proceduresByServiceName,
                'enriched_count' => count($enrichedProcedures),
                'rejected_count' => count($rejectedProcedures)
            ]);
            $allGroupedAppointments = [];

            // 2. Iterar sobre cada grupo de especialidad y llamar a la IA
            foreach ($proceduresByServiceName as $serviceName => $procedures) {
                $defaultPrompt = config('ai.appointment_grouping_prompts.default');
                $servicePrompt = config("ai.appointment_grouping_prompts.{$serviceName}");

                $prompt = $defaultPrompt;
                if ($servicePrompt) {
                    $prompt .= "\n\n" . $servicePrompt;
                }

                Log::info(
                    'Sending request to AI for appointment grouping',
                    [
                        'service_name' => $serviceName,
                        'procedures_count' => count($procedures),
                        'prompt' => $prompt
                    ]
                );

                $response = $grouper->group($procedures, $prompt);

                if (!empty($response['appointments'])) {
                    $allGroupedAppointments = array_merge($allGroupedAppointments, $response['appointments']);
                }
            }

            // 3. Consolidar resultados y construir el mensaje
            $finalSummary = "";
            if (!empty($allGroupedAppointments)) {
                $finalSummary = "De tu orden médica, hemos organizado las siguientes citas. Elige la que desees programar enviando 1, 2, 3, etc. según el número de la cita.\n\n";
                foreach ($allGroupedAppointments as $index => $appointment) {
                    $appointmentNumber = $index + 1;
                    $finalSummary .= "*{$appointmentNumber}.* Incluye los siguientes procedimientos:\n";
                    if (!empty($appointment['procedures'])) {
                        foreach ($appointment['procedures'] as $procedure) {
                            $clientType = $procedure['client_type'] === 'affiliate' ? 'Afiliado' : 'Particular';
                            $finalSummary .= " - {$procedure['descripcion']} ({$procedure['cups']}) - {$clientType}\n";
                        }
                    }
                    $finalSummary .= "\n";
                }
            } elseif (empty($finalSummary) && !empty($enrichedProcedures)) {
                $finalSummary = "Hemos procesado tu orden. Por favor, revisa las citas sugeridas.";
            } else {
                $finalSummary = "No se pudieron planificar citas con los procedimientos de la orden.";
            }

            if (!empty($rejectedProcedures)) {
                $finalSummary .= "\n\n--- AVISO ---\n";
                $finalSummary .= "Los siguientes procedimientos no se pudieron incluir en el plan:\n";
                foreach ($rejectedProcedures as $rejected) {
                    $finalSummary .= "- {$rejected['descripcion']} (CUPS {$rejected['cups']}): {$rejected['reason']}\n";
                }
            }

            if (!empty($alreadyScheduledProcedures)) {
                $finalSummary .= "\n\n--- AVISO ---\n";
                $finalSummary .= "Los siguientes procedimientos no se planificaron porque ya tienes citas futuras para ellos:\n";
                foreach ($alreadyScheduledProcedures as $proc) {
                    $finalSummary .= "- {$proc['descripcion']} (CUPS {$proc['cups']})\n";
                }
            }

            $payload = [
                'status' => 'ok',
                'plan_id' => $this->planId,
                'count' => count($allGroupedAppointments),
                'grouped_appointments' => ['appointments' => $allGroupedAppointments],
                'summary_text' => $finalSummary,
            ];

            $notifier->notifyFromConfig($this->resumeKey, $payload, 'PlanAppointmentsJob - ');
        } catch (\Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $errorPayload = ['status' => 'error', 'message' => 'No pudimos planificar las citas en este momento. Por favor, intenta de nuevo más tarde.'];
                $notifier->notifyFromConfig($this->resumeKey, $errorPayload, 'PlanAppointmentsJob - FINAL ATTEMPT - ');
            }
            throw $e;
        }
    }

    private function enrichProcedures(
        PatientRepositoryInterface $patientRepo,
        EntityRepositoryInterface $entityRepo,
        CupProcedureRepositoryInterface $cupRepo,
        SoatRepositoryInterface $soatRepo,
        AppointmentRepositoryInterface $appointmentRepo
    ): array {
        // Determine entity and price type
        $entityCode = null;
        if ($this->clientType === 'affiliate' && $this->patientId) {
            $patient = $patientRepo->findById($this->patientId);
            $entityCode = $patient['entity_code'] ?? null;
        } else {
            $entityCode = 'PAR01'; // Individual
        }

        if (!$entityCode) throw new \Exception('Entity code could not be determined.');

        $entity = $entityRepo->findByCode($entityCode);
        if (!$entity) throw new \Exception("Entity with code {$entityCode} not found.");
        $priceType = $entity['price_type'];

        $individualEntity = $entityRepo->findByCode('PAR01');
        $individualPriceType = $individualEntity['price_type'];

        $enriched = [];
        $rejected = [];
        $alreadyScheduled = [];
        foreach ($this->data['procedimientos'] as $proc) {
            $cupCode = $proc['cups'];

            if ($this->patientId && $appointmentRepo->hasFutureAppointmentsForCup($this->patientId, $cupCode)) {
                $alreadyScheduled[] = [
                    'cups' => $cupCode,
                    'descripcion' => $proc['descripcion'],
                    'reason' => 'Ya existe una cita futura para este procedimiento.'
                ];
                continue;
            }

            $cupData = $cupRepo->findByCode($cupCode);

            if (!$cupData) {
                $rejected[] = ['cups' => $cupCode, 'descripcion' => $proc['descripcion'], 'reason' => 'No se encuentra en nuestro catálogo.'];
                continue;
            }
            if (($cupData['is_active'] ?? 0) != 1) {
                $rejected[] = ['cups' => $cupCode, 'descripcion' => $proc['descripcion'], 'reason' => 'No está activo actualmente.'];
                continue;
            }

            if (is_string($priceType) && strlen($priceType) === 1) {
                $tipoPrecioNorm = '0' . $priceType;
            } elseif (is_int($priceType) && $priceType < 10) {
                $tipoPrecioNorm = '0' . (string)$priceType;
            } else {
                $tipoPrecioNorm = (string)$priceType;
            }


            $price = $soatRepo->findPrice($cupCode, 'tariff_' . $tipoPrecioNorm);

            if ($this->clientType === 'affiliate' && $price > 0) {
                $proc['client_type'] = 'affiliate';
                $proc['price'] = $price;
            } else {
                $proc['client_type'] = 'individual';
                if (is_string($individualPriceType) && strlen($individualPriceType) === 1) {
                    $tipoPrecioNorm = '0' . $individualPriceType;
                } elseif (is_int($individualPriceType) && $individualPriceType < 10) {
                    $tipoPrecioNorm = '0' . (string)$individualPriceType;
                } else {
                    $tipoPrecioNorm = (string)$individualPriceType;
                }

                $proc['price'] = $soatRepo->findPrice($cupCode, 'tariff_' . $tipoPrecioNorm);
            }
            $proc['service_id'] = $cupData['service_id'];
            $proc['specialty_id'] = $cupData['specialty_id'];
            $proc['service_name'] = $cupData['service_name'];
            $enriched[] = $proc;
        }

        return ['enriched' => $enriched, 'rejected' => $rejected, 'already_scheduled' => $alreadyScheduled];
    }
}
