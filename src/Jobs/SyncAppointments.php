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
        Log::info('Starting appointment synchronization', [
            'subaccount_key' => $this->subaccountKey ?: 'all'
        ]);

        // Obtener mensajes recibidos
        $messages = $messageRepository->findAll();
        $responseMap = [];
        foreach ($messages as $message) {
            if ($message->getType()->isWhatsapp() && $message->getStatus()->isRead()) {
                $response = $this->interpretUserResponse($message->getContent());
                $responseMap[$message->getAppointmentId()] = $response;
            }
        }

        // Get subaccounts to sync
        $subaccounts = $this->getSubaccountsToSync($subaccountRepository);

        foreach ($subaccounts as $subaccount) {
            try {
                $config = $subaccount->config();
                $apiKey = $config->getSetting('api_key');
                $apiEndpoint = $config->getSetting('api_endpoint');

                if (!$apiKey || !$apiEndpoint) {
                    Log::warning('Subaccount missing API configuration', [
                        'subaccount_key' => $subaccount->key()
                    ]);
                    continue;
                }

                // Fetch appointments from external system
                $appointments = $this->fetchAppointmentsFromExternalSystem($apiEndpoint, $apiKey);

                // Sync with our database
                $this->syncAppointments($appointments, $appointmentRepository, $responseMap);

                Log::info('Completed appointment sync for subaccount', [
                    'subaccount_key' => $subaccount->key(),
                    'appointment_count' => count($appointments)
                ]);
            } catch (\Exception $e) {
                Log::error('Error syncing appointments for subaccount', [
                    'subaccount_key' => $subaccount->key(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Appointment synchronization completed');
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

    /**
     * Fetch appointments from external system
     */
    private function fetchAppointmentsFromExternalSystem(string $apiEndpoint, string $apiKey): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json'
        ])->get($apiEndpoint . '/appointments');

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch appointments: ' . $response->body());
        }

        return $response->json('data', []);
    }

    /**
     * Sync appointments with our database
     */
    private function syncAppointments(array $externalAppointments, AppointmentRepositoryInterface $repository, array $responseMap): void
    {
        foreach ($externalAppointments as $externalAppointment) {
            try {
                // Obtener el centerKey del subaccount actual o usar un valor por defecto
                $centerKey = $externalAppointment['center_key'] ?? $this->subaccountKey ?? 'default';

                // Buscar cita por ID y centerKey
                $appointment = $repository->findById($externalAppointment['id'], $centerKey);

                // Actualizar estado de la cita basado en la respuesta del usuario
                $userResponse = $responseMap[$externalAppointment['id']] ?? null;
                if ($userResponse) {
                    switch ($userResponse) {
                        case 'confirm':
                            $appointment = $appointment->confirm();
                            break;
                        case 'cancel':
                            $appointment = $appointment->cancel();
                            break;
                    }
                }

                $repository->save($appointment);
            } catch (\Exception $e) {
                Log::error('Error processing appointment during sync', [
                    'appointment_id' => $externalAppointment['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function interpretUserResponse(string $content): ?string
    {
        $content = strtolower(trim($content));

        if (strpos($content, 'confirmar') !== false || strpos($content, 'sí') !== false) {
            return 'confirm';
        }

        if (strpos($content, 'cancelar') !== false || strpos($content, 'no') !== false) {
            return 'cancel';
        }

        return null;
    }

    /**
     * Map external status to our domain status
     */
    private function mapStatus(string $externalStatus): AppointmentStatus
    {
        switch (strtolower($externalStatus)) {
            case 'confirmed':
                return AppointmentStatus::Confirmed;
            case 'cancelled':
                return AppointmentStatus::Cancelled;
            case 'completed':
                return AppointmentStatus::Completed;
            default:
                return AppointmentStatus::Pending;
        }
    }
}
