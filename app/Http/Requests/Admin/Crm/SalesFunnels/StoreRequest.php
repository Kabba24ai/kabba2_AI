<?php

namespace App\Http\Requests\Admin\Crm\SalesFunnels;

use App\Http\Requests\ApiBaseFormRequest;

class StoreRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'string'],

            'category_id' => ['nullable', 'exists:sales_funnel_categories,id'],

            'trigger_event' => [
                'required',
                'in:rental_start_date,new_lead_added',
            ],

            'timing' => [
                'required',
                'in:before,after',
            ],

            'date_value' => ['nullable', 'integer', 'min:1', 'max:31'],
            'hour_value' => ['nullable', 'integer', 'min:0', 'max:23'],
            'minute_value' => ['nullable', 'integer', 'in:15,30,45'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Optional: clean + normalize input
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
