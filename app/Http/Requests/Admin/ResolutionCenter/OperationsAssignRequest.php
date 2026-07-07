<?php

namespace App\Http\Requests\Admin\ResolutionCenter;

use Illuminate\Foundation\Http\FormRequest;

class OperationsAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
