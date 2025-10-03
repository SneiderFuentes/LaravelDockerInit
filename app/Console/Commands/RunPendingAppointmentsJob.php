<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Core\Jobs\SendPendingAppointmentsMessages;

class RunPendingAppointmentsJob extends Command
{
    protected $signature = 'appointments:send-pending-messages';
    protected $description = 'Execute SendPendingAppointmentsMessages job manually for pending appointments (calls)';

    public function handle()
    {
        $this->info('Starting SendPendingAppointmentsMessages job for automatic calls...');

        SendPendingAppointmentsMessages::dispatch();

        $this->info('Job dispatched to queue successfully!');
        $this->info('Check Horizon dashboard to see the job processing.');
    }
}
