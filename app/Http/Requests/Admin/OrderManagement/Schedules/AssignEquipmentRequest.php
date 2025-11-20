<?php

namespace App\Http\Requests\Admin\OrderManagement\Schedules;

// Helpers
use App\Http\Requests\ApiBaseFormRequest;

class AssignEquipmentRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_unique_id' => ['required', 'exists:users,unique_id'],
            'order_product_unique_id' => ['required', 'exists:order_products,unique_id'],
            'equipment_unique_id' => ['required', 'exists:equipment,unique_id'],
        ];
    }
}
