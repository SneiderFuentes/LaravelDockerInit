<?php

namespace Core\Jobs;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use Core\BoundedContext\CommunicationManagement\Application\Services\SendAppointmentReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders implements ShouldQueue
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
     * @param SendAppointmentReminderService $reminderService
     * @return void
     */
    public function handle(
        AppointmentRepositoryInterface $appointmentRepository,
        SendAppointmentReminderService $reminderService
    ): void {
        Log::info('Starting to send appointment reminders');

        // Get appointments scheduled for tomorrow that are confirmed
        $tomorrow = new \DateTimeImmutable('tomorrow');
        $tomorrow = $tomorrow->setTime(0, 0, 0);
        $dayAfterTomorrow = $tomorrow->modify('+1 day');

        $appointments = $this->getAppointmentsForReminder($appointmentRepository, $tomorrow, $dayAfterTomorrow);

        Log::info('Found ' . count($appointments) . ' appointments requiring reminders');

        foreach ($appointments as $appointment) {
            try {
                $scheduledAt = $appointment->scheduledAt();

                $reminderService->sendReminder(
                    $appointment->patientPhone(),
                    $appointment->patientName(),
                    $scheduledAt->format('d/m/Y'),
                    $scheduledAt->format('H:i'),
                    'su médico' // This could be enhanced to include the actual doctor name
                );

                Log::info('Sent reminder for appointment', [
                    'appointment_id' => $appointment->id()->value(),
                    'patient' => $appointment->patientName(),
                    'phone' => $appointment->patientPhone()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send appointment reminder', [
                    'appointment_id' => $appointment->id()->value(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Finished sending appointment reminders');
    }

    /**
     * Get appointments that need reminders
     */
    private function getAppointmentsForReminder(
        AppointmentRepositoryInterface $repository,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        // Usar el método findByStatus para obtener las citas confirmadas
        $confirmados = $repository->findByStatus(
            'all', // Puedes ajustar el centerKey según necesites
            AppointmentStatus::Confirmed,
            null
        );

        return array_filter($confirmados, function ($appointment) use ($startDate, $endDate) {
            $appointmentDate = $appointment->scheduledAt();

            return $appointmentDate >= $startDate &&
                $appointmentDate < $endDate;
        });
    }
}
