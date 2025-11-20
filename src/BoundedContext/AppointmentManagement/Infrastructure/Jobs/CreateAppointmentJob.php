<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\CreateAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Commands\CreateAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\DoctorRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use DateTime;
use InvalidArgumentException;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentSlotNotAvailableException;

/**
 * Estructura JSON enviada al webhook:
 * {
 *   "status": "ok" | "error",
 *   "appointment_id": "...",
 *   "message": "...",
 *   ...extra data...
 * }
 */
class CreateAppointmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 60; // 1 minuto, la creación debería ser rápida.

    private array $requestData;
    private string $resumeKey;

    public function __construct(array $requestData, string $resumeKey)
    {
        $this->requestData = $requestData;
        $this->resumeKey = $resumeKey;
    }

    public function handle(
        CreateAppointmentHandler $handler,
        WebhookNotifierService $notifier,
        PatientRepositoryInterface $patientRepo,
        DoctorRepositoryInterface $doctorRepo
    ): void {
        try {
            $command = new CreateAppointmentCommand(
                $this->requestData['patient_id'],
                $this->requestData['doctor_id'],
                (int)$this->requestData['agenda_id'],
                $this->requestData['date'],
                $this->requestData['time'],
                $this->requestData['cups'],
                (int)($this->requestData['espacios'] ?? 1),
                $this->requestData['is_contrasted'],
                $this->requestData['is_sedated'] ?? false
            );
            Log::info('----CREAR CITA Job en ejecución', ['attempts' => $this->attempts()]);
            $result = $handler->handle($command);

            $patient = $patientRepo->findById($this->requestData['patient_id']);
            $doctor = $doctorRepo->findByDocumentNumber($this->requestData['doctor_id']);

            $detailsText = $this->buildAppointmentDetailsText($result[0], $patient, $doctor);

            $payload = [
                'status' => 'ok',
                'appointment_id' => $result[0]['id'] ?? null,
                'message' => 'Appointment(s) created successfully',
                'appointment_details_text' => $detailsText,
                'data' => $result
            ];

            $notifier->notifyFromConfig($this->resumeKey, $payload, 'CreateAppointmentJob - ');
        } catch (AppointmentSlotNotAvailableException $e) {
            // CONFLICTO DE HORARIO: La cita no se puede crear porque el espacio ya fue tomado.
            Log::info('Appointment slot conflict. Notifying user.', [
                'resume_key' => $this->resumeKey,
                'error' => $e->getMessage()
            ]);

            $payload = [
                'status' => 'exists',
                'message' => $e->getMessage()
            ];
            $notifier->notifyFromConfig($this->resumeKey, $payload, 'CreateAppointmentJob - SLOT_CONFLICT - ');
        } catch (InvalidArgumentException $e) {
            // ERROR DE NEGOCIO: La cita no se puede crear porque ya existe una.
            // Esto es un fallo esperado que debemos notificar al usuario.
            Log::warning('Business rule violation: Attempted to create a duplicate appointment.', [
                'resume_key' => $this->resumeKey,
                'error' => $e->getMessage()
            ]);

            $payload = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $notifier->notifyFromConfig($this->resumeKey, $payload, 'CreateAppointmentJob - BUSINESS_ERROR - ');
        } catch (\Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $errorPayload = ['status' => 'error', 'message' => 'No pudimos agendar la cita en este momento. Inténtalo de nuevo más tarde.'];
                $notifier->notifyFromConfig($this->resumeKey, $errorPayload, 'CreateAppointmentJob - FINAL ATTEMPT - ');
            }
            throw $e;
        }
    }

    private function buildAppointmentDetailsText(array $appointment, ?array $patient, ?array $doctor): string
    {
        $doctorName = $doctor['doctor_full_name'] ?? 'Médico no asignado';
        $patientName = $patient['full_name'] ?? 'N/A';
        $id = $appointment['id'] ?? 'N/A';

        $formattedDate = Carbon::parse($appointment['date'])->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
        $time = $appointment['time_slot'] ?? 'Hora no disponible';
        Log::info('time', ['time' => $time]);
        $detail = "¡Tu cita ha sido programada con éxito!\n\n";
        $detail .= "Detalles de la Cita (ID: {$id}):\n";
        $detail .= "*Fecha:* {$formattedDate}\n";
        $detail .= "*Hora:* {$time}\n";
        $detail .= "*Médico:* {$doctorName}\n";
        $detail .= "*Paciente:* {$patientName}\n";
        $detail .= "*Estado:* Pendiente de Confirmar\n";

        if (!empty($appointment['cup_procedure'])) {
            if (!empty($appointment['cup_procedure'][0]['address'])) {
                $detail .= "*Dirección:* " . $appointment['cup_procedure'][0]['address'] . "\n";
            }
            $detail .= "\n*Procedimientos:*\n";
            foreach ($appointment['cup_procedure'] as $cup) {
                $detail .= "- " . ($cup['name'] ?? 'Procedimiento sin nombre') . "\n";
            }

            $preparationsText = "\n*Preparación:*\n";
            $hasPreparations = false;
            foreach ($appointment['cup_procedure'] as $cup) {
                if (!empty($cup['preparation'])) {
                    $cupName = $cup['name'] ?? 'Procedimiento sin nombre';
                    $preparationsText .= "- Para '{$cupName}': " . $cup['preparation'];
                    if (!empty($cup['video_url'])) {
                        $preparationsText .= " (Ver video: " . $cup['video_url'] . ")";
                    }
                    $preparationsText .= "\n";
                    $hasPreparations = true;
                }
            }
            $detail .= $hasPreparations ? $preparationsText : "\n*Preparación:*\nNinguna preparación requerida.\n";
        } else {
            $detail .= "\n*Procedimientos:*\nNo hay procedimientos asociados.\n";
            $detail .= "\n*Preparación:*\nNinguna preparación requerida.\n";
        }

        return $detail;
    }
}
