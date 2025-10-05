<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MakeAutomaticCallService
{
    /**
     * Realiza una llamada automática para recordatorio de cita PENDIENTE
     */
    public function makePendingAppointmentCall(array $appointmentData): bool
    {
        $url = env('BIRD_FLOW_CONFIRM_APPOINTMENT_CALL');
        $apiKey = env('FLOW_APPOINMENT_WEBHOOK_API_KEY');


        if (!$url || !$apiKey) {
            Log::error('Missing call API configuration', [
                'url' => $url ? 'configured' : 'missing',
                'api_key' => $apiKey ? 'configured' : 'missing'
            ]);
            return false;
        }


        // Formatear fecha y hora para el bot
        $formattedDate = $this->formatDateForBot($appointmentData['appointment_date']);
        $formattedTime = $this->formatTimeForBot($appointmentData['appointment_time']);

        $callData = [
            'from' => $appointmentData['from'],
            'phone' => $appointmentData['phone'],
            'patient_name' => $appointmentData['patient_name'],
            'appointment_date' => $formattedDate,
            'appointment_time' => $formattedTime,
            'clinic_name' => $appointmentData['clinic_name'],
            'clinic_address' => $this->formatAddressForBot($appointmentData['clinic_address'] ?? 'Dirección no disponible'),
            'appointment_id' => $appointmentData['appointment_id'],
        ];
        Log::info('CALL_DATA', [
            'call_data' => $callData
        ]);

        try {
            // $response = Http::withHeaders([
            //     'Authorization' => 'Bearer ' . $apiKey,
            //     'Content-Type' => 'application/json'
            // ])->timeout(15)->post($url, $callData);

            $response = true;
            Log::info('CALL_API_RESPONSE', [
                'appointment_id' => $appointmentData['appointment_id'],
                'patient_name' => $appointmentData['patient_name'],
                'phone_called' => $appointmentData['phone'],
                // 'response_status' => $response->status(),
                // 'response_body' => $response->body()
            ]);


            // if ($response->successful()) {
            //     Log::info('Pending appointment call initiated successfully', [
            //         'patient_id' => $appointmentData['patient_id'],
            //         'phone' => $appointmentData['phone']
            //     ]);
            //     return true;
            // } else {
            //     Log::error('Call API returned error', [
            //         'status' => $response->status(),
            //         'body' => $response->body()
            //     ]);
            //     return false;
            // }
            return true;
        } catch (\Exception $e) {
            Log::error('Exception during call API request', [
                'error' => $e->getMessage(),
                'patient_id' => $appointmentData['patient_id'],
                'phone' => $appointmentData['phone']
            ]);
            return false;
        }
    }

    /**
     * Realiza una llamada automática para confirmación de cita
     */
    public function makeAppointmentConfirmationCall(array $appointmentData): bool
    {
        $url = env('CALL_API_ENDPOINT');
        $apiKey = env('CALL_API_KEY');

        if (!$url || !$apiKey) {
            Log::error('Missing call API configuration for confirmation', [
                'url' => $url ? 'configured' : 'missing',
                'api_key' => $apiKey ? 'configured' : 'missing'
            ]);
            return false;
        }

        // Formatear fecha y hora para el bot
        $formattedDate = $this->formatDateForBot($appointmentData['appointment_date']);
        $formattedTime = $this->formatTimeForBot($appointmentData['appointment_time']);

        // Preparar datos para la llamada de confirmación
        $callData = [
            'phone_number' => $appointmentData['phone'],
            'patient_name' => $appointmentData['patient_name'],
            'appointment_date' => $formattedDate,
            'appointment_time' => $formattedTime,
            'clinic_name' => $appointmentData['clinic_name'],
            'clinic_address' => $this->formatAddressForBot($appointmentData['clinic_address'] ?? 'Dirección no disponible'),
            'procedures' => $appointmentData['procedures'],
            'total_appointments' => $appointmentData['total_appointments'],
            'call_type' => 'appointment_confirmation',
            'appointment_id' => $appointmentData['appointment_id'],
            'patient_id' => $appointmentData['patient_id']
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->timeout(30)->post($url, $callData);

            Log::info('CONFIRMATION_CALL_API_RESPONSE', [
                'appointment_id' => $appointmentData['appointment_id'],
                'patient_name' => $appointmentData['patient_name'],
                'phone_called' => $appointmentData['phone'],
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                Log::info('Appointment confirmation call initiated successfully', [
                    'patient_id' => $appointmentData['patient_id'],
                    'phone' => $appointmentData['phone']
                ]);
                return true;
            } else {
                Log::error('Confirmation call API returned error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception during confirmation call API request', [
                'error' => $e->getMessage(),
                'patient_id' => $appointmentData['patient_id'],
                'phone' => $appointmentData['phone']
            ]);
            return false;
        }
    }

    /**
     * Formatea la fecha para que sea entendible por un bot
     */
    private function formatDateForBot(string $date): string
    {
        try {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
            if ($dateObj === false) {
                return $date; // Retorna la fecha original si no se puede formatear
            }

            // Formatear a español: "3 de octubre de 2025"
            $months = [
                1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
                5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
                9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
            ];

            $day = $dateObj->format('j');
            $month = $months[(int)$dateObj->format('n')];
            $year = $dateObj->format('Y');

            return "{$day} de {$month} de {$year}";
        } catch (\Exception $e) {
            return $date; // Retorna la fecha original si hay error
        }
    }

    /**
     * Formatea la hora para que sea entendible por un bot
     */
    private function formatTimeForBot(string $timeSlot): string
    {
        try {
            // Si viene en formato YYYYMMDDHHMM, extraer solo HHMM
            if (strlen($timeSlot) === 12) {
                $timeSlot = substr($timeSlot, -4);
            }

            // Si viene en formato HHMM, convertir a HH:MM
            if (strlen($timeSlot) === 4) {
                $hour = substr($timeSlot, 0, 2);
                $minute = substr($timeSlot, 2, 2);
                $timeSlot = "{$hour}:{$minute}";
            }

            // Crear objeto DateTime para formatear
            $timeObj = \DateTime::createFromFormat('H:i', $timeSlot);
            if ($timeObj === false) {
                return $timeSlot; // Retorna la hora original si no se puede formatear
            }

            // Formatear a español: "8:00 de la mañana" o "2:30 de la tarde"
            $hour = (int)$timeObj->format('H');
            $minute = $timeObj->format('i');

            if ($hour < 12) {
                $period = 'de la mañana';
            } elseif ($hour < 18) {
                $period = 'de la tarde';
            } else {
                $period = 'de la noche';
            }

            $formattedTime = $timeObj->format('g:i');
            if ($minute === '00') {
                $formattedTime = $timeObj->format('g');
            }

            return "{$formattedTime} {$period}";
        } catch (\Exception $e) {
            return $timeSlot; // Retorna la hora original si hay error
        }
    }

    /**
     * Formatea la dirección para que sea entendible por un bot
     */
    private function formatAddressForBot(string $address): string
    {
        // Reemplazar # por "número"
        $formattedAddress = str_replace('#', 'número ', $address);

        // Reemplazar "No" por "número" (manteniendo mayúsculas/minúsculas)
        $formattedAddress = preg_replace('/\bNo\b/', 'número', $formattedAddress);

        // Limpiar espacios múltiples
        $formattedAddress = preg_replace('/\s+/', ' ', $formattedAddress);

        return trim($formattedAddress);
    }
}
