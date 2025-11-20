<?php

namespace App\Http\Requests\Admin\WebsiteManagement\FaqPage;

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
            'categoryName' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_expand' => ['nullable', 'boolean'],
            'category_icon_media' => ['nullable', 'file', 'image'], 
            'category_index_number' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Category name is required.',
        ];
    }
}
