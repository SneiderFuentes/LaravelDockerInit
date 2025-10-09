<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Core\Jobs\SendWhatsappMessagesForSpecificAgenda;

class SendSpecificAgendaMessages extends Command
{
    protected $signature = 'whatsapp:send-specific-agenda {agenda_id} {doctor_document} {date}';
    protected $description = 'Send WhatsApp messages for a specific agenda, doctor and date';

    public function handle()
    {
        $agendaId = (int) $this->argument('agenda_id');
        $doctorDocument = $this->argument('doctor_document');
        $date = $this->argument('date');

        $this->info("Starting SendWhatsappMessagesForSpecificAgenda job...");
        $this->info("Agenda ID: {$agendaId}");
        $this->info("Doctor Document: {$doctorDocument}");
        $this->info("Date: {$date}");

        SendWhatsappMessagesForSpecificAgenda::dispatch($agendaId, $doctorDocument, $date);

        $this->info('Job dispatched to queue successfully!');
        $this->info('Check Horizon dashboard to see the job processing.');
    }
}

