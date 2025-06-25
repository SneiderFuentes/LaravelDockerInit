<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Domain\Entities\Appointment;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentId;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use Core\BoundedContext\SubaccountManagement\Domain\Repositories\SubaccountRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\MessageRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Core\BoundedContext\CommunicationManagement\Domain\ValueObjects\MessageStatus;
use Core\BoundedContext\CommunicationManagement\Domain\Entities\Message;

class SyncAppointments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?string $subaccountKey;

    /**
     * Create a new job instance.
     *
     * @param string|null $subaccountKey
     * @return void
     */
    public function __construct(?string $subaccountKey = null)
    {
        $this->subaccountKey = $subaccountKey;
    }

    /**
     * Execute the job.
     *
     * @param AppointmentRepositoryInterface $appointmentRepository
     * @param SubaccountRepositoryInterface $subaccountRepository
     * @param MessageRepositoryInterface $messageRepository
     * @return void
     */
    public function handle(
        AppointmentRepositoryInterface $appointmentRepository,
        SubaccountRepositoryInterface $subaccountRepository,
        MessageRepositoryInterface $messageRepository
    ): void {
        Log::info('Iniciando sincronización de citas basada en respuestas de mensajes', [
            'subaccount_key' => $this->subaccountKey ?: 'all'
        ]);

        // Obtener mensajes con respuestas confirmadas o canceladas
        $messages = $messageRepository->findActionableResponses();
        $counter = [
            'processed' => 0,
            'updated' => 0,
            'not_found' => 0,
            'errors' => 0
        ];

        Log::info('Encontrados ' . count($messages) . ' mensajes con respuestas procesables');

        // Procesar los mensajes accionables
        foreach ($messages as $message) {
            // Si no está asociado a una cita, continuar
            if (!$message->getAppointmentId()) {
                continue;
            }

            $counter['processed']++;
            $response = $message->getMessageResponse();
            $appointmentId = $message->getAppointmentId();
            $subaccountKey = $message->getSubaccountKey();

            // Si no tenemos subaccount_key, no podemos actualizar la cita
            if (!$subaccountKey) {
                Log::warning('Mensaje sin subaccount_key. No se puede actualizar la cita', [
                    'message_id' => $message->getId(),
                    'appointment_id' => $appointmentId
                ]);
                continue;
            }



            $action = $this->determineResponseAction($response);
            if (!$action) {
                continue; // No hay acción reconocible en la respuesta
            }

            Log::info('Procesando respuesta de usuario', [
                'message_id' => $message->getId(),
                'appointment_id' => $appointmentId,
                'subaccount_key' => $subaccountKey,
                'response' => $response,
                'action' => $action
            ]);

            try {
                // Buscar la cita directamente en el centro correcto
                $appointment = $appointmentRepository->findById($appointmentId, $subaccountKey);

                if (!$appointment) {
                    Log::warning('Cita no encontrada', [
                        'appointment_id' => $appointmentId,
                        'subaccount_key' => $subaccountKey
                    ]);
                    $counter['not_found']++;
                    continue;
                }

                // Actualizar la cita según la acción
                $updatedAppointment = $this->updateAppointmentStatus($appointment, $action, $message);

                // Guardar la cita actualizada
                $appointmentRepository->save($updatedAppointment);
                $counter['updated']++;

                Log::info('Cita actualizada correctamente', [
                    'appointment_id' => $appointmentId,
                    'subaccount_key' => $subaccountKey,
                    'new_status' => $this->getStatusName($updatedAppointment)
                ]);
            } catch (\Exception $e) {
                $counter['errors']++;
                Log::error('Error al actualizar cita', [
                    'appointment_id' => $appointmentId,
                    'subaccount_key' => $subaccountKey,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Sincronización de citas completada', $counter);
    }

    /**
     * Obtiene el nombre del estado de la cita de manera segura
     *
     * @param Appointment $appointment
     * @return string
     */
    private function getStatusName(Appointment $appointment): string
    {
        $status = $appointment->status();

        // Para enums de PHP 8.1+
        if ($status instanceof \BackedEnum) {
            return $status->value;
        }

        // Para objetos con método label
        if (method_exists($status, 'label')) {
            return $status->label();
        }

        // Para objetos con método __toString
        if (is_object($status) && method_exists($status, '__toString')) {
            return (string)$status;
        }

        // Si ninguno de los métodos anteriores funciona, devolver un valor genérico
        return is_object($status) ? get_class($status) : 'Estado desconocido';
    }

    /**
     * Determina la acción a realizar basada en el contenido de la respuesta
     *
     * @param string $response
     * @return string|null
     */
    private function determineResponseAction(string $response): ?string
    {
        $responseLower = strtolower(trim($response));

        // Respuestas de confirmación
        if (
            $responseLower === 'confirmed' ||
            in_array($responseLower, ['si', 'sí', 'confirmar', 'confirmo', 'ok', 'yes'])
        ) {
            return 'confirm';
        }

        // Respuestas de cancelación
        if (
            $responseLower === 'canceled' || $responseLower === 'cancelled' ||
            in_array($responseLower, ['no', 'cancelar', 'cancelo', 'cancelado'])
        ) {
            return 'cancel';
        }

        return null;
    }

    /**
     * Actualiza el estado de una cita según la acción
     *
     * @param Appointment $appointment
     * @param string $action
     * @return Appointment
     */
    private function updateAppointmentStatus(Appointment $appointment, string $action, Message $message): Appointment
    {
        switch ($action) {
            case 'confirm':
                $appointment->confirm($message->getId());
                break;
            case 'cancel':
                $appointment->cancel('Cancelado por respuesta del paciente', $message->getId());
                break;
        }
        return $appointment;
    }

    /**
     * Get subaccounts to sync
     */
    private function getSubaccountsToSync(SubaccountRepositoryInterface $repository): array
    {
        if ($this->subaccountKey) {
            $subaccount = $repository->findByKey($this->subaccountKey);
            return $subaccount ? [$subaccount] : [];
        }

        return $repository->findAll();
    }
}
