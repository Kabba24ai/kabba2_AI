<?php

namespace App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->except(['image'])));
    }

    public function rules(): array
    {
        return [
            'title'              => ['nullable', 'string', 'max:255'],
            'subtitle'           => ['nullable', 'string', 'max:500'],
            'image'              => ['nullable', 'image', 'max:4096'],
            'media_id'           => ['nullable', 'string'],
            'button_text'        => ['nullable', 'string', 'max:100'],
            'button_url'         => ['nullable', 'string', 'max:500'],
            'status'             => ['nullable', 'string', 'in:Active,Inactive'],
            'content'            => ['nullable', 'array'],
            'content.*'          => ['nullable'],
        ];
    }
}
