<?php

namespace Core\Console;

use Core\Jobs\SendAppointmentReminders;
use Core\Jobs\SyncAppointments;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Core\Jobs\SendWhatsappMessages;
use Core\Jobs\CallUnconfirmedUsers;
use Core\BoundedContext\SubaccountManagement\Infrastructure\Commands\SeedSubaccountsCommand;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Commands\DispatchJob;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SeedSubaccountsCommand::class,
        DispatchJob::class,
        \Core\Console\HealthCheckCommand::class,
        \App\Console\Commands\RunWhatsappJob::class,
        \App\Console\Commands\RunPendingAppointmentsJob::class,
        \App\Console\Commands\SendSpecificAgendaMessages::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        // Prune horizon metrics
        $schedule->command('horizon:purge')
            ->daily();

        // Monitor health
        $schedule->command('health-check:run')
            ->everyFiveMinutes();

        // Programar el envío de mensajes de WhatsApp diariamente a las 11:00
        $schedule->command('whatsapp:send-messages')
            ->dailyAt('7:00')
            ->withoutOverlapping();

        $schedule->command('appointments:send-pending-messages')
        ->dailyAt('15:00')
        ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
