<?php

namespace App\Http\Requests\Admin\Configurations\CompanySettings;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Sanitize editable text — no script injection through document blocks. */
    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->all()));
    }

    public function rules(): array
    {
        return [
            'company_name'             => 'required|string|max:120',
            'main_url'                 => 'required|string|max:255',
            'company_main_phone'       => 'required|string|max:30',
            'company_sales_phone'      => 'nullable|string|max:30',
            'store_hours_fallback'     => 'nullable|string|max:1000',
            'price_list_value_message' => 'nullable|string|max:1000',
            'price_list_disclaimer'    => 'nullable|string|max:5000',
        ];
    }
}
