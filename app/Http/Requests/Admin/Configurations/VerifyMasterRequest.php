<?php

namespace App\Http\Requests\Admin\Configurations;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class VerifyMasterRequest extends FormRequest
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
        $this->merge(PurifyHelper::purify($this->all()));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
            'field_name' => ['required', 'string'],
        ];
    }

}
