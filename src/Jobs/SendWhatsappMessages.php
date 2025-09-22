<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Application\Services\SendWhatsappMessageService;
use Core\BoundedContext\SubaccountManagement\Domain\Repositories\SubaccountRepositoryInterface;
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
    public $timeout = 300; // 5 minutos

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
     * @param AppointmentRepositoryInterface $appointmentRepository
     * @param SendWhatsappMessageService $whatsappService
     * @param SubaccountRepositoryInterface $subaccountRepository
     * @return void
     */
    public function handle(
        AppointmentRepositoryInterface $appointmentRepository,
        SendWhatsappMessageService $whatsappService,
        SubaccountRepositoryInterface $subaccountRepository
    ): void {
        try {
            Log::info('Starting to send WhatsApp messages');

            // Obtener la lista de subcuentas (centros)
            $subaccounts = $subaccountRepository->findAll();
            Log::info('Found ' . count($subaccounts) . ' subaccounts to process');

            // Procesar máximo 10 centros por job para evitar timeout
            $subaccounts = array_slice($subaccounts, 0, 10);

        $totalAppointments = 0;
        $totalMessagesSent = 0;

        foreach ($subaccounts as $subaccount) {
            Log::info('Processing center: ' . $subaccount->key());

            try {
                $startDate = now()->addDay()->startOfDay(); // Mañana desde las 00:00
                $endDate = now()->addDay()->endOfDay(); // Mañana hasta las 23:59

                Log::info('Filtering appointments by date range', [
                    'center' => $subaccount->key(),
                    'start_date' => $startDate->format('Y-m-d H:i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s')
                ]);

                // Establecer timeout para la consulta específica
                $startTime = microtime(true);
                Log::info('Starting database query for center: ' . $subaccount->key());

                // Lanzar jobs por horas del día para este centro
                Log::info('Dispatching hourly jobs for center: ' . $subaccount->key());

                // Crear jobs para cada hora del día (6 AM a 8 PM)
                for ($hour = 0; $hour <= 23; $hour++) {
                    $hourStart = $startDate->copy()->setTime($hour, 0);
                    $hourEnd = $startDate->copy()->setTime($hour, 59);

                    // Despachar job para esta hora específica
                    \Core\Jobs\SendWhatsappMessagesHourly::dispatch(
                        $subaccount->key(),
                        $hourStart,
                        $hourEnd
                    )->delay(now()->addSeconds($hour * 10)); // Espaciar jobs 10 segundos por hora
                }

                Log::info('Dispatched 15 hourly jobs for center: ' . $subaccount->key());

            } catch (\Exception $e) {
                Log::error('Failed to dispatch hourly jobs for center: ' . $subaccount->key(), [
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Finished dispatching hourly jobs for all centers');

        } catch (\Throwable $e) {
            Log::error('Critical error in SendWhatsappMessages job', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

}
