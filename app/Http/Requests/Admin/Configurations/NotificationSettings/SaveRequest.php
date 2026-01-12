<?php

namespace App\Http\Requests\Admin\Configurations\NotificationSettings;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;

class SaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // You can add permission checks later if needed
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Sanitize all input (prevents XSS / script injection)
        $input = PurifyHelper::purify($this->all());

        $this->merge($input);
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:order,emergency',

            'recipients' => 'required|array|min:1',

            'recipients.*.user_id' => 'nullable|integer|min:0',

            'recipients.*.name' => 'required|string|max:255',

            'recipients.*.phone' => [
                'required',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Notification type is required.',
            'type.in' => 'Invalid notification type.',

            'recipients.required' => 'At least one recipient is required.',
            'recipients.array' => 'Recipients must be a valid list.',

            'recipients.*.name.required' => 'Recipient name is required.',
            'recipients.*.phone.required' => 'Phone number is required.',
            'recipients.*.phone.regex' =>
                'Phone number must be in format (xxx) xxx-xxxx.',
        ];
    }
}
