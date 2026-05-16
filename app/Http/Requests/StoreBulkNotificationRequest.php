<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBulkNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:128'],
            'channel' => ['required', Rule::in(['sms', 'email'])],
            'priority' => ['required', Rule::in(['high', 'default', 'low'])],
            'message' => ['required', 'string', 'max:2000'],
            'recipient_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'recipient_ids.*' => ['required', 'string', 'max:128'],
        ];
    }
}
