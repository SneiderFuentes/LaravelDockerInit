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
            'from' => 'required|string',
            'message' => 'nullable|string',
            'message_id' => 'nullable|string',
        ];
    }
}
