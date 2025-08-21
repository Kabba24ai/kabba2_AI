<?php

namespace App\Http\Requests\Admin\ChecklistManagement\RentalReady\Question;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_name'     => ['required'],
            'category_id'       => ['required'],
            'required_question' => ['nullable', 'boolean'],
            'options'           => ['required', 'json'],
        ];
    }

    public function messages(): array
    {
        return [
        ];
    }
}
