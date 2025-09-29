<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenAI\Laravel\Facades\OpenAI;

final class AppointmentGrouperAIService
{
    /**
     * Recibe un array de procedimientos y devuelve una agrupación de citas sugerida por la IA.
     *
     * @param array $procedures
     * @param ?string $prompt
     * @return array
     * @throws \JsonException
     */
    public function group(array $procedures, ?string $prompt = null): array
    {
        $prompt = $prompt ?: config('ai.appointment_grouping_prompts.default');
        $proceduresJson = json_encode($procedures, JSON_PRETTY_PRINT);

        $messages = [
            [
                'role'    => 'system',
                'content' => $prompt,
            ],
            [
                'role'    => 'user',
                'content' => "Aquí está la lista de procedimientos médicos validados para un paciente:\n\n{$proceduresJson}",
            ],
        ];

        $response = OpenAI::chat()->create([
            'model'    => 'gpt-4o-mini',
            'messages' => $messages,
            'response_format' => ['type' => 'json_object'],
            'max_tokens'   => 1500,
            'temperature'  => 0.1,
        ]);

        $content = $response->choices[0]->message->content ?? '';

        if (empty(trim($content))) {
            throw new \Exception('AI grouping response is empty.');
        }

        // A menudo, los modelos de IA envuelven la respuesta JSON en bloques de código de Markdown.
        // Debemos limpiar la cadena para extraer el JSON puro antes de decodificarlo.
        if (str_starts_with(trim($content), '```')) {
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');
            if ($jsonStart !== false && $jsonEnd !== false) {
                $content = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
            }
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::error('AppointmentGrouperAIService JSON parse error', [
                'error' => $e->getMessage(),
                'raw_content' => $content,
            ]);
            throw $e;
        }

        $validator = Validator::make($data, [
            'appointments' => 'present|array',
        ]);

        if ($validator->fails()) {
            throw new \Exception('AI grouping response is missing the "appointments" key.');
        }

        // Si el array de citas no está vacío, validamos la estructura de sus elementos.
        if (!empty($data['appointments'])) {
            $appointmentValidator = Validator::make($data, [
                'appointments.*.appointment_slot_estimate' => 'required|integer',
                'appointments.*.is_contrasted_resonance' => 'required|boolean',
                'appointments.*.procedures' => 'required|array',
                'appointments.*.procedures.*.cups' => 'required|string',
                'appointments.*.procedures.*.cantidad' => 'required|integer',
            ]);

            if ($appointmentValidator->fails()) {
                Log::error('AI grouping response validation failed on appointment structure', [
                    'errors' => $appointmentValidator->errors()->all(),
                    'raw_content' => $content,
                ]);
                throw new \Exception('AI grouping response has an invalid structure: ' . $appointmentValidator->errors()->first());
            }
        }

        return $data;
    }
}
