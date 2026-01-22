<?php

namespace App\Http\Requests\Admin\TermsAndConditions;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
        $this->merge(PurifyHelper::purify($this->all(),['content','signature_block']));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $uniqueId = $this->route('unique_id'); // assuming your route uses {unique_id}

        $requestArr = [
            'title' => [
            'required',
            'max:240',
            ],
            'signature_block' => ['nullable'],
            'content'=>['nullable'],
            'status' => ['required', 'in:Published,Draft,Pending'],
            'is_global' => ['nullable', 'in:Yes,No'],
            'seo_title' => ['nullable', 'string', 'max:240'],
            'seo_description' => ['nullable', 'string', 'max:240'],
        ];

        return $requestArr;
    }

}
