<?php

namespace App\Http\Requests\Admin\TermsAndConditions;

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
            'content' => ['nullable', 'string'],
            'signature_block' => ['nullable', 'string'],
			'status' => ['required', 'in:Published,Draft,Pending'],
            'is_global' => ['nullable', 'in:Yes,No'],
            'seo_title' => ['nullable', 'string', 'max:240'],
            'seo_description' => ['nullable', 'string', 'max:240'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
        ];
    }
}
