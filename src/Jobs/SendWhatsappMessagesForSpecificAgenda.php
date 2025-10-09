<?php

namespace Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendWhatsappMessagesForSpecificAgenda implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        private int $agendaId,
        private string $doctorDocument,
        private string $date,
        private ?string $previousDate = null
    ) {}

    public function failed(\Throwable $exception): void
    {
        Log::error('SendWhatsappMessagesForSpecificAgenda job failed completely', [
            'agenda_id' => $this->agendaId,
            'doctor_document' => $this->doctorDocument,
            'date' => $this->date,
            'previous_date' => $this->previousDate,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    public function handle(): void
    {
        $centerKey = 'datosipsndx';

        $startDate = Carbon::parse($this->date)->startOfDay();
        $endDate = Carbon::parse($this->date)->endOfDay();

        \Core\Jobs\GetUniquePatientsByDateRangeForSpecificAgenda::dispatch(
            $centerKey,
            $startDate,
            $endDate,
            $this->agendaId,
            $this->doctorDocument,
            $this->previousDate
        )->delay(now()->addSeconds(5));
    }
}

