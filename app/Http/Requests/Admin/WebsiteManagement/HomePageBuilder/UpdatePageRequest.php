<?php

namespace App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->except(['og_image'])));
    }

    public function rules(): array
    {
        return [
            'meta_title'       => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
            'og_title'         => ['nullable', 'string', 'max:160'],
            'og_description'   => ['nullable', 'string', 'max:320'],
            'og_image'         => ['nullable', 'image', 'max:2048'],
            // Retired V2 routes must never reappear as canonical URLs
            'canonical_url'    => ['nullable', 'url', 'max:500', 'not_regex:#/(home-v2|contact-us-v2)(/|$|\?)#'],
        ];
    }

    public function messages(): array
    {
        return [
            'canonical_url.not_regex' => 'Canonical URL must use the current page route — /home-v2 and /contact-us-v2 are retired.',
        ];
    }
}
