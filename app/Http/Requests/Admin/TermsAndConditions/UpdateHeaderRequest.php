<?php

namespace App\Http\Requests\Admin\TermsAndConditions;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->all()));
    }

    public function rules(): array
    {
        return [
            'terms_condition_text_1' => ['nullable', 'string', 'max:255'],
            'terms_condition_text_2' => ['nullable', 'string', 'max:255'],
            'terms_condition_text_3' => ['nullable', 'string', 'max:255'],
        ];
    }
}
