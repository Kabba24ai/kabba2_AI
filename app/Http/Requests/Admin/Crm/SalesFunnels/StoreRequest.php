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

            'trigger_type' => [
                'required',
                'in:new_order,rental_schedule,lead_added',
            ],

            'trigger_reference' => [
                'required',
                'in:order_created_datetime,order_paid_datetime,delivery_datetime,return_datetime,lead_added_datetime',
            ],

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
