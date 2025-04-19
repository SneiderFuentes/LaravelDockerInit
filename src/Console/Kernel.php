<?php

namespace Core\Console;

use Core\Jobs\SendAppointmentReminders;
use Core\Jobs\SyncAppointments;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Core\Jobs\SendWhatsappMessages;
use Core\Jobs\CallUnconfirmedUsers;
use Core\BoundedContext\SubaccountManagement\Infrastructure\Commands\SeedSubaccountsCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SeedSubaccountsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Sync appointments every 30 minutes during business hours
        $schedule->job(new SyncAppointments())
            ->weekdays()
            ->between('8:00', '20:00')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // // Send appointment reminders daily at 10:00
        // $schedule->job(new SendAppointmentReminders())
        //     ->dailyAt('10:00')
        //     ->withoutOverlapping()
        //     ->runInBackground();

        // Run queue worker for processing jobs
        $schedule->command('queue:work --stop-when-empty --queue=default')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Prune horizon metrics
        $schedule->command('horizon:purge')
            ->daily();

        // Monitor health
        $schedule->command('health-check:run')
            ->everyFiveMinutes();

        // Programar el envío de mensajes de WhatsApp diariamente a las 7:00
        $schedule->job(new SendWhatsappMessages())
            ->dailyAt('7:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Programar llamadas para usuarios que no han confirmado a las 16:00
        $schedule->job(new CallUnconfirmedUsers())
            ->dailyAt('16:00')
            ->withoutOverlapping()
            ->runInBackground();
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
