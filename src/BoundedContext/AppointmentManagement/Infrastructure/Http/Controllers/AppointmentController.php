<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers;

use Core\BoundedContext\AppointmentManagement\Application\Commands\CancelAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Commands\ConfirmAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Commands\CreateAppointmentCommand;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\CancelAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ConfirmAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\CreateAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ListAppointmentsHandler;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetAppointmentHandler;
use Core\BoundedContext\AppointmentManagement\Application\Queries\ListAppointmentsQuery;
use Core\BoundedContext\AppointmentManagement\Application\Queries\GetAppointmentQuery;
use Core\BoundedContext\AppointmentManagement\Application\Handlers\ListPendingAppointmentsHandler;
use Core\BoundedContext\AppointmentManagement\Application\Queries\ListPendingAppointmentsQuery;
use Core\BoundedContext\AppointmentManagement\Domain\Exceptions\AppointmentNotFoundException;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\ConfirmationChannelType;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Core\BoundedContext\SubaccountManagement\Application\Services\GetSubaccountConfigService;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\PatientRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\EntityRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\SoatRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\ScheduleConfigRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence\GenericDbAppointmentRepository;
use Core\BoundedContext\AppointmentManagement\Application\DTOs\AppointmentDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AppointmentController extends Controller
{
    public function __construct(
        private ListAppointmentsHandler $listAppointmentsHandler,
        private GetAppointmentHandler $getAppointmentHandler,
        private ConfirmAppointmentHandler $confirmAppointmentHandler,
        private CancelAppointmentHandler $cancelAppointmentHandler,
        private ListPendingAppointmentsHandler $listPendingAppointmentsHandler,
        private CreateAppointmentHandler $createAppointmentHandler
    ) {}

    public function index(Request $request, $centerKey): JsonResponse
    {
        try {
            $startDate = $request->get('start_date') ? new DateTime($request->get('start_date')) : null;
            $endDate = $request->get('end_date') ? new DateTime($request->get('end_date')) : null;

            $query = new ListPendingAppointmentsQuery(
                $centerKey,
                $startDate,
                $endDate
            );

            $appointments = $this->listPendingAppointmentsHandler->handle($query);

            return new JsonResponse([
                'data' => array_map(fn($dto) => $dto->toArray(), $appointments),
                'meta' => [
                    'total' => count($appointments),
                    'start_date' => $startDate?->format('Y-m-d') ?? 'tomorrow',
                    'end_date' => $endDate?->format('Y-m-d') ?? 'tomorrow 23:59:59',
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error listing pending appointments: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $centerKey, $id): JsonResponse
    {
        try {
            $query = new GetAppointmentQuery($id, $centerKey);
            $appointment = $this->getAppointmentHandler->handle($query);
            return new JsonResponse(['data' => $appointment->toArray()]);
        } catch (AppointmentNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Error fetching appointment: ' . $e->getMessage()], 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $command = new CreateAppointmentCommand(
                $request->input('patient_id'),
                $request->input('doctor_id'),
                (int)$request->input('agenda_id'),
                $request->input('date'),
                $request->input('time'),
                $request->input('cups'),
                (int)$request->input('espacios', 1)
            );
            $result = $this->createAppointmentHandler->handle($command);
            return new JsonResponse([
                'data' => $result,
                'message' => 'Appointment(s) created successfully',
                'espacios' => $command->espacios,
                'citas_creadas' => count($result)
            ], 201);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error creating appointment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function confirm(Request $request, string $centerKey, string $id): JsonResponse
    {
        try {
            $channelType = $request->input('channel_type');
            if ($channelType && !in_array($channelType, ['whatsapp', 'voz'])) {
                throw new InvalidArgumentException('Invalid channel type. Must be whatsapp or voz');
            }

            $command = new ConfirmAppointmentCommand(
                $id,
                $centerKey,
                $request->input('channel_id'),
                $channelType ? ConfirmationChannelType::from($channelType) : null
            );

            $appointment = $this->confirmAppointmentHandler->handle($command);

            // Formatear mensaje de la cita
            $formattedMessage = $this->formatSingleAppointmentMessage($appointment);

            return new JsonResponse([
                'data' => $appointment->toArray(),
                'message' => 'Appointment confirmed successfully',
                'formatted_message' => $formattedMessage,
            ]);
        } catch (AppointmentNotFoundException $e) {
            Log::error("Appointment not found in confirm()", [
                'appointment_id' => $id,
                'center_key' => $centerKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 404);
        } catch (InvalidArgumentException $e) {
            Log::error("Invalid argument in confirm()", [
                'appointment_id' => $id,
                'center_key' => $centerKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            Log::error("Unexpected error in confirm()", [
                'appointment_id' => $id,
                'center_key' => $centerKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                'error' => 'Error confirming appointment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, string $centerKey, string $id): JsonResponse
    {
        try {
            $channelType = $request->input('channel_type');
            if ($channelType && !in_array($channelType, ['whatsapp', 'voz'])) {
                throw new InvalidArgumentException('Invalid channel type. Must be whatsapp or voz');
            }

            $command = new CancelAppointmentCommand(
                $id,
                $centerKey,
                $request->input('reason', 'No reason provided'),
                $request->input('channel_id'),
                $channelType ? ConfirmationChannelType::from($channelType) : null
            );

            $appointment = $this->cancelAppointmentHandler->handle($command);

            // Mensaje simple para cancelación
            $formattedMessage = "✅ *Cita cancelada exitosamente*\n\n";
            $formattedMessage .= "Su cita ha sido cancelada correctamente. Lamentamos los inconvenientes y esperamos poder atenderle en otra ocasión.";

            return new JsonResponse([
                'data' => $appointment->toArray(),
                'message' => 'Appointment cancelled successfully',
                'formatted_message' => $formattedMessage,
            ]);
        } catch (AppointmentNotFoundException $e) {
            Log::error("Appointment not found in cancel()", [
                'appointment_id' => $id,
                'center_key' => $centerKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 404);
        } catch (InvalidArgumentException $e) {
            Log::error("Invalid argument in cancel()", [
                'appointment_id' => $id,
                'center_key' => $centerKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            Log::error("Unexpected error in cancel()", [
                'appointment_id' => $id,
                'center_key' => $centerKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                'error' => 'Error cancelling appointment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Formatea un mensaje detallado para una única cita
     */
    private function formatSingleAppointmentMessage(AppointmentDTO $appointment): string
    {
        $appointmentArray = $appointment->toArray();
        $doctorData = $appointmentArray['doctor_data'] ?? [];
        $cupData = $appointmentArray['cup_data'] ?? [];

        $doctorName = $doctorData['full_name'] ?? 'Médico no asignado';

        // Formateo de fecha
        $formattedDate = 'Fecha no disponible';
        if ($appointment->date) {
            try {
                $formattedDate = Carbon::parse($appointment->date)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
            } catch (\Exception $e) {
                // Mantener el valor por defecto si hay error
            }
        }

        $time = $this->formatTimeSlot($appointment->timeSlot);

        // Mapeo de estado
        $statusMap = [
            'pending' => 'Pendiente de Confirmar',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada',
        ];
        $formattedStatus = $statusMap[$appointment->status->value] ?? ucfirst($appointment->status->value);

        $detail = "*Detalles de la Cita (ID: " . $appointment->id . "):*\n\n";
        $detail .= "*Fecha:* " . $formattedDate . "\n";
        $detail .= "*Hora:* " . $time . "\n";
        $detail .= "*Médico:* " . $doctorName . "\n";
        $detail .= "*Paciente:* " . $appointment->patientName . "\n";
        $detail .= "*Teléfono:* " . $appointment->patientPhone . "\n";
        $detail .= "*Estado:* " . $formattedStatus . "\n";

        // Añadir información de confirmación si existe
        if ($appointment->confirmationDate) {
            $confirmationDate = Carbon::parse($appointment->confirmationDate)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY [a las] HH:mm');
            $channelType = $appointment->confirmationChannelType ? ucfirst($appointment->confirmationChannelType->value) : 'No especificado';
            $detail .= "*Confirmada el:* " . $confirmationDate . "\n";
            $detail .= "*Canal de confirmación:* " . $channelType . "\n";
        }

        // Añadir dirección si existe en los CUPs
        $addressFound = false;
        if (!empty($cupData)) {
            foreach ($cupData as $cup) {
                if (!empty($cup['address'])) {
                    $detail .= $this->formatAddressWithMaps($cup['address']) . "\n";
                    $addressFound = true;
                    break;
                }
            }
        }

        // Si no se encontró dirección, usar la por defecto
        if (!$addressFound) {
            $detail .= $this->formatAddressWithMaps('') . "\n";
        }

        $detail .= "\n";

        if (!empty($cupData)) {
            $detail .= "*Procedimientos:*\n";
            foreach ($cupData as $cup) {
                $cupName = $cup['name'] ?? 'Procedimiento sin nombre';
                $detail .= "• " . $cupName . "\n";
            }
            $detail .= "\n";

            $preparationsText = "*Preparación:*\n";
            $hasPreparations = false;
            foreach ($cupData as $cup) {
                if (!empty($cup['preparation'])) {
                    $cupName = $cup['name'] ?? 'Procedimiento sin nombre';
                    $preparation = $cup['preparation'];
                    $preparationsText .= "• Para *'" . $cupName . "'*: " . $preparation;

                    // Añadir video_url si existe, o usar video por defecto para pruebas
                    if (!empty($cup['video_url'])) {
                        $preparationsText .= "\n  📹 [Ver video](" . $cup['video_url'] . ")";
                    }

                    // Añadir audio_url si existe
                    if (!empty($cup['audio_url'])) {
                        $preparationsText .= "\n  🎵 [Audio](" . $cup['audio_url'] . ")";
                    }

                    $preparationsText .= "\n";
                    $hasPreparations = true;
                }
            }

            if ($hasPreparations) {
                $detail .= $preparationsText;
            } else {
                $detail .= "*Preparación:*\nNinguna preparación requerida.\n";
            }
        } else {
            $detail .= "*Procedimientos:*\nNo hay procedimientos asociados.\n";
            $detail .= "*Preparación:*\nNinguna preparación requerida.\n";
        }

        return $detail;
    }

    /**
     * Formatea el time slot a formato legible
     */
    private function formatTimeSlot(string $timeSlot): string
    {
        // Asumiendo formato YYYYMMDDHHMM
        if (strlen($timeSlot) === 12) {
            $hour = substr($timeSlot, 8, 2);
            $minute = substr($timeSlot, 10, 2);
            return $hour . ':' . $minute;
        }
        return $timeSlot;
    }

    /**
     * Obtiene la URL de Google Maps para una dirección específica
     */
    private function getGoogleMapsUrl(string $address): string
    {
        $addressMaps = [
            'Calle 35 # 36 26 Antiguo edificio Clinica Martha' => 'https://maps.app.goo.gl/eVNp9t7wY8DhgUhR6',
            'Calle 34 No 38-47 Barzal' => 'https://maps.app.goo.gl/MZqCxVoKAgwrnUVh7',
        ];

        // Buscar coincidencia exacta primero
        if (isset($addressMaps[$address])) {
            return $addressMaps[$address];
        }

        // Buscar coincidencia parcial para mayor flexibilidad
        foreach ($addressMaps as $knownAddress => $url) {
            if (strpos($address, 'Calle 35') !== false && strpos($knownAddress, 'Calle 35') !== false) {
                return $url;
            }
            if (strpos($address, 'Calle 34') !== false && strpos($knownAddress, 'Calle 34') !== false) {
                return $url;
            }
        }

        // Por defecto, usar la dirección de Calle 34 No 38-47 Barzal
        return 'https://maps.app.goo.gl/MZqCxVoKAgwrnUVh7';
    }

    /**
     * Formatea la dirección con su URL de Google Maps
     */
    private function formatAddressWithMaps(string $address): string
    {
        if (empty($address)) {
            $defaultAddress = 'Calle 34 No 38-47 Barzal';
            $mapsUrl = 'https://maps.app.goo.gl/MZqCxVoKAgwrnUVh7';
            return "*Dirección:* " . $defaultAddress . "\n📍 [Ver en Google Maps](" . $mapsUrl . ")";
        }

        $mapsUrl = $this->getGoogleMapsUrl($address);
        return "*Dirección:* " . $address . "\n📍 [Ver en Google Maps](" . $mapsUrl . ")";
    }

}
