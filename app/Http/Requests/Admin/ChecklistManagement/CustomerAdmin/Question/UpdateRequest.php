<?php

namespace App\Http\Requests\Admin\ChecklistManagement\CustomerAdmin\Question;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('options') && is_string($this->options)) {
        $this->merge([
            'options' => json_decode($this->options, true),
        ]);
    }
    }

    public function rules(): array
    {
        return [
            'question_name'      => ['required', 'string'],
            'category_id'        => ['required', 'integer'],
            'required_question'  => ['nullable', 'boolean'],
            'question_delivery_text' => ['required', 'string'],
            'question_return_text'   => ['required', 'string'],

            'options'            => ['required', 'array'],
            
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
