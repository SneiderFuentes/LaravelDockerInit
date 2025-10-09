<?php

namespace Core\Jobs;

use Core\BoundedContext\CommunicationManagement\Application\Services\SendWhatsappMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRescheduleWhatsappMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        private array $messageData,
        private string $centerKey
    ) {}

    public function failed(\Throwable $exception): void
    {
        Log::error('SendRescheduleWhatsappMessage job failed completely', [
            'message_data' => $this->messageData,
            'center_key' => $this->centerKey,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    public function handle(SendWhatsappMessageService $whatsappService): void
    {
        $success = $whatsappService->sendRescheduleNotificationFlow($this->messageData);

        if ($success) {
            Log::info('Reschedule notification sent successfully', [
                'patient_id' => $this->messageData['patient_id'],
                'phone' => $this->messageData['phone'],
                'previous_date' => $this->messageData['appointment_date_cancel'],
                'new_date' => $this->messageData['appointment_date_new']
            ]);
        } else {
            Log::warning('Reschedule notification failed', [
                'patient_id' => $this->messageData['patient_id'],
                'phone' => $this->messageData['phone']
            ]);
        }

    }
}

