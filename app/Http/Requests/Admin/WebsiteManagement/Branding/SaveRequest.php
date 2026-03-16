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

        
            'home_page_image' => 'nullable|image',

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


        ];
    }
}