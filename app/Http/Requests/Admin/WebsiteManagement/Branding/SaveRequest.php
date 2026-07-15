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

            'site_phone' => [
                'nullable',
                'string'
            ],

            'site_email' => [
                'nullable',
                'string'
            ],

            // Retired (not accepted here anymore):
            //  - home_seo_title / home_seo_description — homepage SEO is managed
            //    in Home Page Builder → SEO (website_pages meta columns)
            //  - top_text / top_phone / bottom_title / bottom_text — legacy
            //    homepage fields with no front-end consumers; structured data
            //    reads site_phone
            //  - all_rights_reserved / powered_by — footer content is managed
            //    in Website Management → Footer (copyright_text/powered_by_text)
            //  - terms_condition_text_1/2/3 — Settings → Terms & Conditions

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
        ];
    }
}
