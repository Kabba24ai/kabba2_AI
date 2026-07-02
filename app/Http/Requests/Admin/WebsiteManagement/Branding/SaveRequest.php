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

            'site_logo'          => 'nullable|image|dimensions:width=64,height=64',
            'site_favicon'       => 'nullable|image|dimensions:width=64,height=64',
            'hp_builder_logo'    => 'nullable|image|dimensions:width=64,height=64',
            'hp_builder_favicon' => 'nullable|image|dimensions:width=64,height=64',

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
        'site_logo.dimensions'          => 'The site logo must be exactly 64 x 64 pixels.',
        'site_logo.image'               => 'Please upload a valid site logo image file.',
        'site_favicon.dimensions'       => 'The favicon must be exactly 64 x 64 pixels.',
        'site_favicon.image'            => 'Please upload a valid favicon image file.',
        'hp_builder_logo.dimensions'    => 'The home page logo must be exactly 64 x 64 pixels.',
        'hp_builder_logo.image'         => 'Please upload a valid home page logo image file.',
        'hp_builder_favicon.dimensions' => 'The home page favicon must be exactly 64 x 64 pixels.',
        'hp_builder_favicon.image'      => 'Please upload a valid home page favicon image file.',
        'home_page_image.dimensions' => 'The home page image must be exactly 1900 × 430 pixels.',
        'home_page_image.image'    => 'Please upload a valid image file.',
    ];
}
}
