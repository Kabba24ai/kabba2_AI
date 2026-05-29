<?php

namespace App\Http\Requests\Admin\Crm\SalesFunnels;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'string'],

            'category_id' => ['nullable', 'exists:sales_funnel_categories,id'],

            // Funnel Type — controls which event timing references are allowed per event.
            // Timing itself is configured inside each Event, not at the funnel level.
            'trigger_type' => [
                'required',
                'in:retail_order,rental_schedule,lead_added',
            ],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
