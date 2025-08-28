<?php

namespace App\Http\Requests\Admin\ChecklistManagement\CustomerAdmin\Question;

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
        'question_name'      => ['required', 'string'],
        'category_id'        => ['required', 'integer'],
        'required_question'  => ['nullable', 'boolean'],

        // Validate the array
        'options'            => ['required', 'array'],

        // Validate each item in the array
        'options.*.delivery_text' => ['required', 'string'],
        'options.*.return_text'   => ['required', 'string'],
        'options.*.delivery_amt'  => ['nullable', 'numeric'],
        'options.*.return_amt'    => ['nullable', 'numeric'],
    ];
    }

    public function messages(): array
    {
        return [
        ];
    }
}
