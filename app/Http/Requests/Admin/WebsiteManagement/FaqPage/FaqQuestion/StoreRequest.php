<?php

namespace App\Http\Requests\Admin\WebsiteManagement\FaqPage\FaqQuestion;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'integer', 'max:255'],
            'question' => ['nullable', 'string'],
            'answer' => ['nullable', 'string'],
            'active' => ['nullable', 'integer'],
            'relatedCategory' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'category is required.',
            'question.required' => 'Question name is required.',
            'answer.required' => 'Answer is required.',
        ];
    }
}
