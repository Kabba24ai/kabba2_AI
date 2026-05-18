<?php

namespace App\Http\Requests\Admin\WebsiteManagement\ContactUsSection;

use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_title' => ['required', 'string'],
            'contact_subtitle' => ['nullable', 'string'],
            'contact_seo_title' => ['nullable', 'string'],
            'contact_seo_description' => ['nullable', 'string'],
        ];
    }

}
