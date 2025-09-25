<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Http\Traits;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

trait DispatchesJobsSafely
{
    /**
     * Prepara (con delay) y despacha un job de forma segura, manejando la lógica de retraso,
     * logging y excepciones si el servicio de colas no está disponible.
     *
     * @param object $job El job a despachar.
     * @param string $logMessage El mensaje base para el log. Ej: "----CREAR PACIENTE"
     * @param array $logContext Contexto adicional para el log.
     */
    protected function dispatchSafely(object $job, string $logMessage, array $logContext = []): void
    {
        // 1. Aplicar la lógica de retraso
        $delayInSeconds = app()->environment('production') ? (int)env('JOB_PROD_DELAY_SECONDS', 5) : (int)env('JOB_DEV_DELAY_SECONDS', 10);
        if ($delayInSeconds > 0) {
            $job->delay(now()->addSeconds($delayInSeconds));
        }

        // 2. Registrar el despacho del job
        Log::info($logMessage . ' Job despachado con ' . $delayInSeconds . ' segundos de retraso', [
            'data' => $logContext
        ]);

        // 3. Despachar el job
        Bus::dispatch($job);
    }
}
