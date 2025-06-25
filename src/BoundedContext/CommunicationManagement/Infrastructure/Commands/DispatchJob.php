<?php

declare(strict_types=1);

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Commands;

use Core\Jobs\SendWhatsappMessages;
use Core\Jobs\SyncAppointments;
use Illuminate\Console\Command;

final class DispatchJob extends Command
{
    protected $signature = 'job:dispatch {job? : El nombre del job a ejecutar}';

    protected $description = 'Dispatch a job (SendWhatsappMessages por defecto)';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $jobName = $this->argument('job') ?? 'SyncAppointments';

        $this->info("Dispatching job: {$jobName}");

        try {
            if ($jobName === 'SendWhatsappMessages') {
                SendWhatsappMessages::dispatch();
                $this->info('SendWhatsappMessages job dispatched successfully.');
            } else if ($jobName === 'SyncAppointments') {
                SyncAppointments::dispatch();
                $this->info('SyncAppointments job dispatched successfully.');
            } else {
                $this->error("Job {$jobName} not supported yet.");
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error dispatching job: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
