<?php

namespace App\Http\Requests\Admin\ResolutionCenter;

use Illuminate\Foundation\Http\FormRequest;

class AnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'can_reschedule' => ['required', 'boolean'],
            'credit_would_satisfy' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
