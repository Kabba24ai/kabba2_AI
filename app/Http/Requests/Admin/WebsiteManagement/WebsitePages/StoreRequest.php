<?php

namespace App\Http\Requests\Admin\WebsiteManagement\WebsitePages;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->except(['og_image'])));
    }

    public function rules(): array
    {
        return [
            'title'            => 'required|string|max:255',
            'slug'             => 'required|string|max:255|unique:website_pages,slug',
            'page_key'         => 'nullable|string|max:100|unique:website_pages,page_key',
            'status'           => 'nullable|string|in:Active,Inactive',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:500',
            'og_title'         => 'nullable|string|max:255',
            'og_description'   => 'nullable|string|max:500',
            'og_image'         => 'nullable|image|max:4096',
            'canonical_url'    => 'nullable|string|max:500',
        ];
    }
}
