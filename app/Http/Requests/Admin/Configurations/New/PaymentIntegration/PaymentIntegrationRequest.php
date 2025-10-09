<?php

namespace App\Http\Requests\Admin\Configurations\New\PaymentIntegration;

use Illuminate\Foundation\Http\FormRequest;

class PaymentIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_gateway' => ['nullable', 'string'],
            'payment_api_public_key' => ['nullable', 'string'],
            'payment_api_key' => ['nullable', 'string'],
            'payment_api_secret' => ['nullable', 'string'],
            'payment_test_mode' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_gateway.required' => 'Payment gateway is required.',
            'payment_api_public_key.required' => 'Public API key is required.',
            'payment_api_key.required' => 'API key is required.',
            'payment_api_secret.required' => 'Secret key is required.',
        ];
    }

}
