<?php

namespace App\Http\Requests\Admin\Configurations\PriceSettings;

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
            'diesel_price_per_gallon' => 'required|numeric|min:0',
            'gas_price_per_gallon' => 'required|numeric|min:0',
            'def_price_per_gallon' => 'required|numeric|min:0',
        ];
    }

}
