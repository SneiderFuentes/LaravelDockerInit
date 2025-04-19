<?php

namespace Core\BoundedContext\FlowManagement\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TriggerFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flow_id' => 'required|string',
            'channel_type' => 'required|string|in:sms,whatsapp,voice',
            'phone_number' => 'required|string|min:10',
            'parameters' => 'sometimes|array',
            'parameters.*' => 'nullable'
        ];
    }
}
