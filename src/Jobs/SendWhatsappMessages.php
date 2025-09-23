<?php

namespace Core\Jobs;

// Imports removidos ya que el job ahora es más simple
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendWhatsappMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 60; // 1 minuto (job simple que solo despacha otro job)

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendWhatsappMessages job failed completely', [
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void {
        // Entidad constante - no consultar subaccounts
        $centerKey = 'datosipsndx'; // Valor constante

        // Rango de fechas: mañana desde las 00:00 hasta las 23:59
        $startDate = now()->addDay()->startOfDay(); // Mañana desde las 00:00
        $endDate = now()->addDay()->endOfDay(); // Mañana hasta las 23:59

        // Ejecutar directamente el job que consulta IDs de pacientes únicos
        \Core\Jobs\GetUniquePatientsByDateRange::dispatch(
            $centerKey,
            $startDate,
            $endDate
        )->delay(now()->addSeconds(5)); // Delay de 5 segundos
    }

}
