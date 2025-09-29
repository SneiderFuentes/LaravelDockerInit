<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenAI\Laravel\Facades\OpenAI;
use Core\BoundedContext\AppointmentManagement\Domain\Repositories\CupProcedureRepositoryInterface;

final class VisionMedicalOrderService
{
    public function __construct(private CupProcedureRepositoryInterface $cupRepository) {}

    /**
     * Recibe la ruta al archivo (imagen o PDF) y devuelve los datos extraídos.
     */
    public function extract(string $filePath, ?string $patientDocument = null): array
    {
        // 1. Obtener el prompt base desde la configuración
        $visionPrompt = config('ai.vision_prompt');

        // 2. Obtener la lista de CUPS y formatearla para el prompt
        $allCups = $this->cupRepository->findAll();
        $cupsContext = "A continuación, una lista de CUPS y descripciones de referencia. Úsala para corregir posibles errores de OCR en el código del procedimiento basándote en la descripción del texto:\n\n";
        foreach ($allCups as $cup) {
            $cup = (array)$cup;
            if (isset($cup['code']) && isset($cup['name'])) {
                $cupsContext .= "- CUPS: {$cup['code']}, Descripción: {$cup['name']}\n";
            }
        }

        // 3. Agregar información del documento del paciente si está disponible
        $promptWithDocument = str_replace('{{patient_document}}', $patientDocument, $visionPrompt);

        $promptWithDate = str_replace('{{today_bogota}}', now()->format('Y-m-d'), $promptWithDocument);


        // 4. Reemplazar el marcador de posición en el prompt con la lista de CUPS
        $promptWithContext = str_replace('{{cups_context}}', $cupsContext, $promptWithDocument);

        // Obtener MIME y base64
        $mime = mime_content_type($filePath) ?: 'application/octet-stream';

        // Mantener referencia al archivo original para eliminarlo al final
        $filesToDelete = [$filePath];

        // Si es PDF, convertir a imagen usando Ghostscript
        if ($mime === 'application/pdf') {
            try {
                // Forzar siempre la conversión de la primera página del PDF
                $imagePath = $filePath . '.jpg';
                // Usar -dFirstPage=1 y -dLastPage=1 para procesar solo la primera página
                $command = "gs -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -r300 -dFirstPage=1 -dLastPage=1 -sOutputFile={$imagePath} {$filePath}";
                exec($command, $output, $returnCode);

                if ($returnCode !== 0) {
                    throw new \Exception("Error converting PDF to image. Return code: {$returnCode}");
                }

                // Añadir la imagen a la lista de archivos a eliminar
                $filesToDelete[] = $imagePath;

                $filePath = $imagePath;
                $mime = 'image/jpeg';
            } catch (\Exception $e) {
                Log::error('Error converting PDF to image: ' . $e->getMessage());
                throw $e;
            }
        }

        $base64 = base64_encode(file_get_contents($filePath));
        $dataUri = 'data:' . $mime . ';base64,' . $base64;

        try {

            $response = OpenAI::chat()->create([
                'model'    => 'gpt-4o-mini',
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => $promptWithContext,
                    ],
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Analiza la siguiente orden médica en la imagen y aplica el proceso de decisión que te indiqué.',
                            ],
                            [
                                'type'      => 'image_url',
                                'image_url' => [
                                    'url' => $dataUri,
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens'   => 1200,
                'temperature'  => 0,
            ]);

            $content = $response->choices[0]->message->content ?? '';
            $data = [];
            if (empty(trim($content))) {
                throw new \Exception('AI response is empty.');
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
            } catch (\JsonException $e) {
                Log::error('VisionMedicalOrderService JSON parse error', [
                    'error' => $e->getMessage(),
                    'raw_content' => $content,
                ]);
                throw new \Exception('Failed to decode AI JSON response.');
            }

            // Validar la estructura de la respuesta de la IA.
            $validator = Validator::make($data, [
                'paciente' => 'required|array',
                'orden' => 'required|array',
                'orden.procedimientos' => 'required|array',
                'orden.procedimientos.*.cups' => 'required|string',
                'orden.procedimientos.*.cantidad' => 'required|integer',
            ]);

            if ($validator->fails()) {
                // Si la validación falla, primero revisamos si es por el caso especial "no_table_detected".
                // Esto es un resultado de negocio, no un error de sistema.
                if (isset($data['error']) && $data['error'] === 'no_table_detected') {
                    return $data; // Devolver el array con la clave de error.
                }

                Log::error('AI response validation failed', [
                    'errors' => $validator->errors()->all(),
                    'raw_content' => $content,
                ]);
                throw new \Exception('AI response has an invalid structure: ' . $validator->errors()->first());
            }

            return $data;
        } finally {
            // Eliminar archivos temporales
            foreach (array_unique($filesToDelete) as $tmp) {
                if (is_string($tmp) && file_exists($tmp)) {
                    @unlink($tmp);
                }
            }
        }
    }
}
