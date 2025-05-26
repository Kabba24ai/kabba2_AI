<?php

namespace App\Http\Requests\Admin\ProductManagement\Categories;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->all(),['content']));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:240'],
            'parent_id' => ['nullable', 'exists:product_categories,id'],
            'short_content' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'media' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'image', 'mimes:jpeg,jpg,png', 'max:2048'
            ],
            'status' => ['required', 'in:Active,Inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'media.required' => 'Please upload a media file.',
        ];
    }
}
