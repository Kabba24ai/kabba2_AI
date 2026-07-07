<?php

namespace App\Http\Requests\Admin\Documents;

use Illuminate\Foundation\Http\FormRequest;

class SavePriceListPresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:120'],
            'description'    => ['nullable', 'string', 'max:500'],
            'is_active'      => ['nullable', 'boolean'],
            'sort_order'     => ['nullable', 'integer', 'min:0', 'max:65535'],
            'category_ids'   => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'distinct', 'exists:product_categories,id'],
            'thumbnail'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'remove_thumbnail' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_ids.required' => 'Select at least one category for this preset.',
            'category_ids.min'      => 'Select at least one category for this preset.',
            'thumbnail.max'         => 'The thumbnail image must be 1 MB or smaller.',
            'thumbnail.mimes'       => 'The thumbnail must be a JPG, PNG, or WebP image.',
            'thumbnail.image'       => 'The thumbnail must be a JPG, PNG, or WebP image.',
        ];
    }
}
