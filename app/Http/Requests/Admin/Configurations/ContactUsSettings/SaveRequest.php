<?php

namespace App\Http\Requests\Admin\Configurations\ContactUsSettings;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
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
            'sales_phone'          => 'required|string|max:20',
            'sales_email'           => 'nullable|email|max:255',
            'support_phone'        => 'nullable|string|max:20',
            'support_email'           => 'nullable|email|max:255',
            'address1'        => 'required|string|max:255',
            'address2'        => 'nullable|string|max:255',
        ];
    }

}
