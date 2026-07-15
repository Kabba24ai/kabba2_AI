<?php

namespace App\Http\Requests\Admin\WebsiteManagement\Branding;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Clean inputs before validation
     */
    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->all()));
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [

            // ── Site-wide Logo & Favicon ──────────────────────────────────────
            'site_logo'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'site_favicon'       => ['nullable', 'file', 'mimes:png,svg,ico', 'max:2048'],

            // ── Business Identity ─────────────────────────────────────────────
            'site_name' => ['sometimes', 'required', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:255'],

            'site_phone' => [
                'nullable',
                'string'
            ],

            'site_email' => [
                'nullable',
                'string'
            ],


            'home_seo_title' => [
                'nullable',
                'string'
            ],

            'home_seo_description' => [
                'nullable',
                'string'
            ],

            'home_page_subtitle' => [
                'nullable',
                'string'
            ],

            'top_text' => [
                'nullable',
                'string'
            ],

            'top_phone' => [
                'nullable',
                'string',
            ],

            'bottom_title' => [
                'nullable',
                'string',
            ],

            'bottom_text' => [
                'nullable',
                'string'
            ],

            'powered_by' => 'nullable',
            'all_rights_reserved' => 'nullable',

            'terms_and_conditions_url' => [
                'nullable',
                'string'
            ],

            // The Rental Agreement Header lines (terms_condition_text_1/2/3)
            // are managed exclusively in Settings → Terms & Conditions.

        ];
    }

    public function messages(): array
    {
        return [
            // ── Site-wide ──────────────────────────────────────────────────
            'site_logo.mimes'                    => 'Site logo must be a JPG, PNG, WebP, or SVG file.',
            'site_logo.max'                      => 'Site logo may not exceed 4 MB.',
            'site_favicon.mimes'                 => 'Site favicon must be a PNG, SVG, or ICO file.',
            'site_favicon.max'                   => 'Site favicon may not exceed 2 MB.',

            // ── Business Identity ──────────────────────────────────────────
            'site_name.required'                 => 'Business name is required.',
            'site_name.max'                      => 'Business name may not exceed 255 characters.',
            'site_tagline.max'                   => 'Tagline may not exceed 255 characters.',
        ];
    }
}
