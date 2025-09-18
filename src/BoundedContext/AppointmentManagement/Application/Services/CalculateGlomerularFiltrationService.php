<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Application\Services;

use OpenAI\Client;
use Illuminate\Support\Facades\Log;

class CalculateGlomerularFiltrationService
{
    public function __construct(private Client $openai) {}

    public function calculate(array $data): array
    {
        if (empty($data['creatinine']) || (float)$data['creatinine'] <= 0) {
            return $this->buildInvalidInputResponse();
        }

        $formula = $this->getFormulaFromAI($data);
        $value = 0;

        switch ($formula) {
            case 'COCKCROFT-GAULT':
                $value = $this->calculateCockcroftGault($data);
                break;
            case 'CKD-EPI':
                $value = $this->calculateCkdEpi($data);
                break;
            case 'SCHWARTZ':
                $value = $this->calculateSchwartz($data);
                break;
            default:
                throw new \Exception("Formula '{$formula}' not recognized.");
        }

        return $this->buildResponse($value, $formula);
    }

    private function getFormulaFromAI(array $data): string
    {
        $prompt = config('ai.glomerular_filtration_prompt');
        $requestData = [
            'age' => $data['age'],
            'gender' => $data['gender'],
            'underlying_disease' => ($data['underlying_disease_weight_type'] === 'true'),
        ];

        Log::info('Sending request to AI for formula selection', ['data' => $requestData]);

        $response = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => json_encode($requestData)],
            ],
            'temperature' => 0.0,
            'response_format' => ['type' => 'json_object'],
            'timeout' => 30,
        ]);

        $content = $response->choices[0]->message->content;
        if (empty($content)) {
            throw new \Exception('AI returned an empty response.');
        }

        $result = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to decode AI JSON response: ' . json_last_error_msg());
        }

        $formula = $result['formula'] ?? 'UNKNOWN';

        Log::info('Received AI response for formula selection', ['formula' => $formula]);

        return $formula;
    }

    private function calculateCockcroftGault(array $data): float
    {
        $crcl = ((140 - $data['age']) * $data['weight_kg']) / (72 * $data['creatinine']);
        if ($data['gender'] === 'F') {
            $crcl *= 0.85;
        }
        return $crcl;
    }

    private function calculateCkdEpi(array $data): float
    {
        $cr = $data['creatinine'];
        $age = $data['age'];

        if ($data['gender'] === 'F') {
            $kappa = 0.7;
            $alpha = -0.329;
            if ($cr > $kappa) {
                $alpha = -1.209;
            }
            return 144 * pow($cr / $kappa, $alpha) * pow(0.993, $age);
        } else { // Male
            $kappa = 0.9;
            $alpha = -0.411;
            if ($cr > $kappa) {
                $alpha = -1.209;
            }
            return 141 * pow($cr / $kappa, $alpha) * pow(0.993, $age);
        }
    }

    private function calculateSchwartz(array $data): float
    {
        if ($data['age'] < 1) {
            $k = ($data['underlying_disease_weight_type'] === 'low') ? 0.33 : 0.45;
        } elseif ($data['age'] >= 13) {
            $k = ($data['gender'] === 'M') ? 0.70 : 0.55;
        } else { // 2 a 12 años
            $k = 0.55;
        }
        return ($k * $data['height_cm']) / $data['creatinine'];
    }

    private function buildResponse(float $value, string $formula): array
    {
        $roundedValue = round($value, 2);
        $message = '';
        $canProceed = false;

        if ($roundedValue >= 60) {
            $canProceed = true;
            $message = "✅ ¡Todo en orden!\n\nTu función renal es de **{$roundedValue} mL/min**, un valor seguro para usar contraste.\n\nProcederemos a programar la cita.";
        } elseif ($roundedValue >= 30) {
            $canProceed = true;
            $message = "⚠️ Precaución necesaria\n\nTu filtrado es de **{$roundedValue} mL/min**.\nPodemos realizar el estudio con contraste, pero sigue estas indicaciones:\n\n• Bebe 1 litro de agua repartido entre hoy y la mañana de la cita.\n• Evita café y alcohol 24 h antes.\n• Informa a tu médico si tomas medicamentos para la presión o el azúcar.";
        } else {
            $message = "❌ No es seguro realizar el estudio con contraste\n\nTu filtrado renal es de **{$roundedValue} mL/min** (menor de 30 mL/min).\nPor tu seguridad debemos buscar una alternativa sin contraste.";
        }

        return [
            'can_proceed' => $canProceed,
            'message' => $message,
            'calculated_value' => $roundedValue,
            'formula_used' => $formula,
        ];
    }

    private function buildInvalidInputResponse(): array
    {
        return [
            'can_proceed' => false,
            'message' => '❌ No se pudo realizar el cálculo. El valor de creatinina no puede ser cero. Por favor, verifica los datos e intenta de nuevo.',
            'calculated_value' => 0,
            'formula_used' => 'N/A',
        ];
    }
}
