<?php

namespace App\Http\Requests\Admin\Crm\SalesFunnels\Steps;

use App\Http\Requests\ApiBaseFormRequest;

class StoreRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'funnel_unique_id' => ['required', 'exists:sales_funnels,unique_id'],
            'step_type' => ['required', 'in:SMS,Email'],
            'sms_category_id' => ['required', 'nullable', 'exists:sms_categories,id'],
            'sms_funnel_id' => ['required', 'exists:sms_funnels,id'],
            'delay_unit' => ['required', 'in:Days,Hours,Minutes'],
            'delay_value' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
