<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Core\Jobs\SendWhatsappMessages;

class RunWhatsappJob extends Command
{
    protected $signature = 'whatsapp:send-messages';
    protected $description = 'Execute SendWhatsappMessages job manually';

    public function handle()
    {
        $this->info('Starting SendWhatsappMessages job...');

        // Enviar a la cola (aparecerá en Horizon)
        SendWhatsappMessages::dispatch();

        $this->info('Job dispatched to queue successfully!');
        $this->info('Check Horizon dashboard to see the job processing.');
    }
}
