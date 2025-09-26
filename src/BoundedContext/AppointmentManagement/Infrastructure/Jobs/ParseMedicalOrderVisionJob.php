<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Jobs;

use Core\BoundedContext\AppointmentManagement\Application\Services\VisionMedicalOrderService;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Services\WebhookNotifierService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ParseMedicalOrderVisionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 180; // 3 minutos, para dar tiempo a la conversión de PDF y a la IA.

    public function __construct(
        private string $fileUrl,
        private string $contentType,
        private string $orderId,
        private string $resumeKey,
        private ?string $patientDocument = null
    ) {}

    public function handle(VisionMedicalOrderService $vision, WebhookNotifierService $notifier): void
    {
        Log::info('----PARSE MEDICAL ORDER Job en ejecución', ['attempts' => $this->attempts()]);
        try {
            // 1) Descargar y almacenar el archivo
            $filePath = $this->downloadAndStoreFile();

            // 2) Extraer datos con IA
            $data = $vision->extract($filePath, $this->patientDocument);

            // Comprobar si la IA no pudo encontrar una tabla de procedimientos
            if (isset($data['error']) && $data['error'] === 'no_table_detected') {
                $payload = [
                    'status' => 'error',
                    'message' => 'No pudimos identificar una tabla de procedimientos en el archivo. Por favor, asegúrate de que el archivo sea una orden médica legible y vuelve a intentarlo.'
                ];
            } else {
                $summaryText = $this->generateSummaryText($data);

                $payload = [
                    'status' => 'ok',
                    'data' => $data,
                    'summary_text' => $summaryText,
                    'message' => 'Medical order parsed successfully'
                ];
                if (isset($data['paciente']['documento']) && $data['paciente']['documento'] !== $this->patientDocument && $data['paciente']['entidad'] !== 'Capital Salud') {
                    $payload = [
                        'status' => 'error',
                        'message' => 'El documento del paciente no coincide con el documento esperado. Por favor, asegúrate de que el archivo sea una orden médica legible y vuelve a intentarlo.'
                    ];
                }
                $data['order_id'] = $this->orderId;
            }
            $notifier->notifyFromConfig($this->resumeKey, $payload, 'ParseMedicalOrderVisionJob - ');
        } catch (\Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $errorPayload = ['status' => 'error', 'message' => 'Lo sentimos, no pudimos procesar la orden médica en este momento. Por favor, intenta de nuevo más tarde.'];
                $notifier->notifyFromConfig($this->resumeKey, $errorPayload, 'ParseMedicalOrderVisionJob - FINAL ATTEMPT - ');
            }
            throw $e;
        }
    }

    private function downloadAndStoreFile(): string
    {
        $apiToken = config('services.messagebird.api_key');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiToken,
            'Content-Type' => 'application/json',
        ])->get($this->fileUrl);

        if ($response->failed()) {
            throw new \Exception('Failed to download file from URL: ' . $response->status());
        }

        $fileContent = $response->body();

        // Determinar el tipo de contenido real desde el propio archivo para mayor fiabilidad.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualContentType = finfo_buffer($finfo, $fileContent);
        finfo_close($finfo);

        $extension = $this->getExtensionFromContentType($actualContentType);
        $fileName = $this->orderId . '.' . $extension;
        $path = 'medical-orders/' . $fileName;

        Storage::put($path, $fileContent);

        return Storage::path($path);
    }

    private function getExtensionFromContentType(string $contentType): string
    {
        $mimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'application/pdf' => 'pdf',
        ];

        return $mimeTypes[$contentType] ?? 'tmp';
    }

    private function cleanupFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            @unlink($filePath);
            Log::info('Temporary file cleaned up', ['file_path' => $filePath]);
        }
    }

    private function generateSummaryText(array $data): string
    {
        $paciente = $data['paciente'] ?? [];
        $orden = $data['orden'] ?? [];
        $procedimientos = $orden['procedimientos'] ?? [];

        $nombre = $paciente['nombre'] ?? 'N/A';
        $documento = $paciente['documento'] ?? 'N/A';
        $edad = $paciente['edad'] ?? 'N/A';
        $sexo = $paciente['sexo'] ?? 'N/A';
        $entidad = $paciente['entidad'] ?? 'N/A';

        $orderId = $data['order_id'] ?? 'N/A';
        $fecha = isset($orden['fecha']) ? Carbon::parse($orden['fecha'])->format('d / m / Y') : 'N/A';
        $diagnostico = $orden['diagnostico'] ?? 'N/A';
        $observaciones = $data['observaciones'] ?? 'Ninguna';

        $text = "Resumen de tu orden médica\n\n";
        $text .= "*Paciente*\n";
        if ($entidad !== 'Capital Salud') {
            $text .= "Nombre: {$nombre}\n";
            $text .= "Documento: {$documento}\n";
        }
        $text .= "Edad / Sexo: {$edad} años · {$sexo}\n";
        $text .= "Entidad: {$entidad}\n\n";

        $text .= "*Orden*\n";
        $text .= "ID de orden: {$orderId}\n";
        $text .= "Fecha de emisión: {$fecha}\n";
        $text .= "Diagnóstico: {$diagnostico}\n";
        $text .= "Observaciones adicionales: {$observaciones}\n\n";

        $text .= "*Procedimientos solicitados*\n";
        if (empty($procedimientos)) {
            $text .= "No se especificaron procedimientos.\n";
        } else {
            foreach ($procedimientos as $proc) {
                $desc = $proc['descripcion'] ?? 'N/A';
                $cups = $proc['cups'] ?? 'N/A';
                $cantidad = $proc['cantidad'] ?? 'N/A';
                $text .= "{$desc} (CUPS {$cups}) — Cantidad: {$cantidad}\n";
            }
        }

        $text .= "\nPor favor revise que toda la información sea correcta.\n\n";
        $text .= "¿La información es correcta?\n";

        return $text;
    }
}
