<?php

namespace App\Http\Requests\Admin\WebsiteManagement\WebsitePages;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->except(['og_image'])));
    }

    public function rules(): array
    {
        $pageId = $this->route('unique_id')
            ? \App\Models\WebsiteManagement\WebsitePage::where('unique_id', $this->route('unique_id'))->value('id')
            : null;

        return [
            'title'            => 'required|string|max:255',
            'slug'             => 'required|string|max:255|unique:website_pages,slug,' . $pageId,
            'page_key'         => 'nullable|string|max:100|unique:website_pages,page_key,' . $pageId,
            'status'           => 'nullable|string|in:Active,Inactive',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:500',
            'og_title'         => 'nullable|string|max:255',
            'og_description'   => 'nullable|string|max:500',
            'og_image'         => 'nullable|image|max:4096',
            'canonical_url'    => 'nullable|string|max:500',
            'header_message'   => 'nullable|string|max:100',
            'header_highlight' => 'nullable|string|max:100',
            'header_badge'     => 'nullable|string|max:60',
            'header_callout'   => 'nullable|string|max:160',
        ];
    }
}
