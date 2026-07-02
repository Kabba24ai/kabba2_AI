<?php

namespace App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\Item;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->except(['image'])));
    }

    public function rules(): array
    {
        return [
            'section_unique_id' => ['required', 'string', 'exists:website_page_sections,unique_id'],
            'item_key'          => ['nullable', 'string', 'max:100'],
            'title'             => ['nullable', 'string', 'max:255'],
            'subtitle'          => ['nullable', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'image'             => ['nullable', 'image', 'max:2048'],
            'icon'              => ['nullable', 'string', 'max:100'],
            'button_text'       => ['nullable', 'string', 'max:100'],
            'button_url'        => ['nullable', 'string', 'max:500'],
            'display_order'     => ['nullable', 'integer', 'min:0'],
            'status'            => ['nullable', 'string', 'in:Active,Inactive'],
            'content'           => ['nullable', 'array'],
        ];
    }
}
