<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Application\Services\InitiateCallService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CallUnconfirmedUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
     * @param InitiateCallService $callService
     * @return void
     */
    public function handle(
        AppointmentRepositoryInterface $appointmentRepository,
        InitiateCallService $callService
    ): void {
        Log::info('Starting to call unconfirmed users');

        // Obtener citas no confirmadas
        $appointments = $appointmentRepository->findUnconfirmedAppointments();

        Log::info('Found ' . count($appointments) . ' unconfirmed appointments to call');

        foreach ($appointments as $appointment) {
            try {
                $callService->initiateCall(
                    $appointment->patientPhone(),
                    $appointment->id()->value()
                );

                Log::info('Initiated call for appointment', [
                    'appointment_id' => $appointment->id()->value(),
                    'phone' => $appointment->patientPhone()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to initiate call for appointment', [
                    'appointment_id' => $appointment->id()->value(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Finished calling unconfirmed users');
    }
}
