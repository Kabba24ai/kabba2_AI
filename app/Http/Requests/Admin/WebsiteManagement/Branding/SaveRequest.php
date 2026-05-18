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

            'site_logo' => 'nullable|image',

            'site_name' => [
                'nullable',
                'string'
            ],

            'site_phone' => [
                'nullable',
                'string'
            ],

            'site_email' => [
                'nullable',
                'string'
            ],


            'home_page_image' => 'nullable|image|dimensions:width=1900,height=430',

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

            'terms_condition_text_1' => [
                'nullable',
                'string'
            ],

            'terms_condition_text_2' => [
                'nullable',
                'string'
            ],

            'terms_condition_text_3' => [
                'nullable',
                'string'
            ],



        ];
    }

    public function messages(): array
{
    return [
        'home_page_image.dimensions' => 'The home page image must be exactly 1900 × 430 pixels.',
        'home_page_image.image' => 'Please upload a valid image file.',
    ];
}
}
