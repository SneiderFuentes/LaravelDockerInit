<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BirdWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string',
            'message_id' => 'required|string',
            'status' => 'nullable|string',
            'timestamp' => 'nullable|string',
            'from' => 'required|string',
            'message' => 'nullable|array',
            'message.type' => 'nullable|string',
            'message.content' => 'nullable|string',
            'context' => 'nullable|array',
            'context.message_id' => 'nullable|string',
        ];
    }
}
