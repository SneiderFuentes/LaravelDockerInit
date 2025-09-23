<?php

namespace Core\BoundedContext\CommunicationManagement\Application\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendInstagramMessageService
{
    /**
     * Envía mensaje de confirmación de cita a Instagram usando Bird
     */
    public function sendAppointmentConfirmationMessage(array $appointmentData): bool
    {
        try {
            Log::info('Preparing Instagram message for Bird API', [
                'appointment_id' => $appointmentData['appointment_id'],
                'patient_name' => $appointmentData['patient_name']
            ]);

            // URL del endpoint de Bird para Instagram
            $birdInstagramUrl = env('BIRD_INSTAGRAM_API_URL');
            $birdApiKey = env('BIRD_API_KEY');

            if (empty($birdInstagramUrl) || empty($birdApiKey)) {
                Log::error('Missing Bird Instagram configuration', [
                    'instagram_url' => $birdInstagramUrl ? 'SET' : 'MISSING',
                    'api_key' => $birdApiKey ? 'SET' : 'MISSING'
                ]);
                return false;
            }

            // Preparar el cuerpo de la petición para Instagram
            $requestBody = [
                'appointment_id' => $appointmentData['appointment_id'],
                'instagram_username' => $appointmentData['instagram_username'] ?? null,
                'patient_name' => $appointmentData['patient_name'],
                'clinic_name' => $appointmentData['clinic_name'],
                'clinic_address' => $appointmentData['clinic_address'],
                'appointment_date' => $appointmentData['appointment_date'],
                'appointment_time' => $appointmentData['appointment_time'],
                'procedures' => $appointmentData['procedures'],
                'message_type' => 'appointment_confirmation'
            ];

            Log::info('Sending request to Bird Instagram API', [
                'url' => $birdInstagramUrl,
                'body' => $requestBody
            ]);

            // Enviar petición a Bird
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $birdApiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->timeout(30)->post($birdInstagramUrl, $requestBody);

            $responseBody = $response->body();
            $statusCode = $response->status();

            Log::info('Bird Instagram API response received', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'appointment_id' => $appointmentData['appointment_id']
            ]);

            // Verificar si la respuesta es exitosa
            if ($response->successful()) {
                Log::info('Instagram message sent successfully via Bird', [
                    'appointment_id' => $appointmentData['appointment_id'],
                    'patient_name' => $appointmentData['patient_name'],
                    'status_code' => $statusCode
                ]);
                return true;
            } else {
                Log::error('Bird Instagram API returned error', [
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'appointment_id' => $appointmentData['appointment_id'],
                    'patient_name' => $appointmentData['patient_name']
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception occurred while sending Instagram message via Bird', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'appointment_id' => $appointmentData['appointment_id'] ?? 'N/A',
                'patient_name' => $appointmentData['patient_name'] ?? 'N/A'
            ]);
            return false;
        }
    }
}
