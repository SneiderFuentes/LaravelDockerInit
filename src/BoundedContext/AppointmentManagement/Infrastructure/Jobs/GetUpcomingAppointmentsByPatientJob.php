<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Handlers\GetUpcomingAppointmentsByPatientHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;
use Carbon\Carbon;

class GetUpcomingAppointmentsByPatientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $patientId;
    public string $fromDate;
    public string $resumeKey;

    public function __construct(string $patientId, string $fromDate, string $resumeKey)
    {
        $this->patientId = $patientId;
        $this->fromDate = $fromDate;
        $this->resumeKey = $resumeKey;
    }

    public function handle(GetUpcomingAppointmentsByPatientHandler $handler, WebhookNotifierService $notifier)
    {
        $payload = [];
        try {
            $appointments = $handler->handle($this->patientId, $this->fromDate);

            $appointmentsListText = "Por favor, selecciona una de tus próximas citas:\n";
            $detailedAppointmentsTextMap = [];
            $selectionToIdMap = ['NA'];

            if (empty($appointments)) {
                $appointmentsListText = "No tienes citas próximas.";
            } else {
                foreach ($appointments as $index => $appointment) {
                    $doctorName = $appointment['doctor_data']['full_name'] ?? 'Médico no asignado';

                    // Formateo de fecha
                    $dateValue = $appointment['date'] ?? null;
                    $formattedDate = 'Fecha no disponible';
                    if ($dateValue) {
                        try {
                            $formattedDate = Carbon::parse($dateValue)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
                        } catch (\Exception $e) {
                            // Mantener el valor por defecto si hay error
                        }
                    }

                    $time = $appointment['time_slot'] ?? 'Hora no disponible';
                    $appointmentsListText .= ($index + 1) . ". Cita con " . $doctorName . " el " . $formattedDate . " a las " . $time . ".\n";

                    $id = $appointment['id'] ?? 'N/A';
                    $patientName = $appointment['patient_name'] ?? 'N/A';

                    // Mapeo de estado
                    $statusValue = $appointment['status'] ?? 'Desconocido';
                    $statusMap = [
                        'pending' => 'Pendiente de Confirmar',
                        'confirmed' => 'Confirmada',
                        'cancelled' => 'Cancelada',
                    ];
                    $formattedStatus = $statusMap[$statusValue] ?? ucfirst($statusValue);

                    $detail = "Detalles de la Cita (ID: " . $id . "):\n";
                    $detail .= "*Fecha:* " . $formattedDate . "\n";
                    $detail .= "*Hora:* " . $time . "\n";
                    $detail .= "*Médico:* " . $doctorName . "\n";
                    $detail .= "*Paciente:* " . $patientName . "\n";
                    $detail .= "*Estado:* " . $formattedStatus . "\n";

                    // Añadir dirección si existe en los CUPs
                    $addressFound = false;
                    if (!empty($appointment['cup_data'])) {
                        foreach ($appointment['cup_data'] as $cup) {
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

                    if (!empty($appointment['cup_data'])) {
                        $detail .= "*Procedimientos:*\n";
                        foreach ($appointment['cup_data'] as $cup) {
                            $cupName = $cup['name'] ?? 'Procedimiento sin nombre';
                            $detail .= "- " . $cupName . "\n";
                        }
                        $detail .= "\n";

                        $preparationsText = "*Preparación:*\n";
                        $hasPreparations = false;
                        foreach ($appointment['cup_data'] as $cup) {
                            if (!empty($cup['preparation'])) {
                                $cupName = $cup['name'] ?? 'Procedimiento sin nombre';
                                $preparation = $cup['preparation'];
                                $preparationsText .= "- Para '" . $cupName . "': " . $preparation;

                                // Añadir video_url si existe, o usar video por defecto para pruebas
                                if (!empty($cup['video_url'])) {
                                    $preparationsText .= "\n  📹 [Ver video](" . $cup['video_url'] . ")";
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

                    if ($id !== 'N/A') {
                        $detailedAppointmentsTextMap[$id] = $detail;
                        $selectionToIdMap[] = $id;
                    }
                }
            }

            $payload = [
                'status' => 'ok',
                'appointments' => $appointments,
                'count' => count($appointments),
                'appointments_list_text' => trim($appointmentsListText),
                'appointments_details_text_map' => $detailedAppointmentsTextMap,
                'selection_to_id_map' => $selectionToIdMap,
                'message' => 'Upcoming appointments retrieved successfully'
            ];
        } catch (\Throwable $e) {
            $payload = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            Log::error('GetUpcomingAppointmentsByPatientJob Error: ' . $e->getMessage(), ['exception' => $e]);
        }
        $notifier->notifyFromConfig($this->resumeKey, $payload, 'GetUpcomingAppointmentsByPatientJob - ');
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
