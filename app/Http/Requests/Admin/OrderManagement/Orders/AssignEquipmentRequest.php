<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Http\Requests\ApiBaseFormRequest;

class AssignEquipmentRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_product_unique_id' => 'required|exists:order_products,unique_id',
            'equipment_unique_id' => 'required|exists:equipment,unique_id',
            'schedule_type' => 'required|in:Delivery,Return',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_product_unique_id.required' => 'Order product is required.',
            'order_product_unique_id.exists' => 'Selected order product does not exist.',
            'equipment_unique_id.required' => 'Equipment is required.',
            'equipment_unique_id.exists' => 'Selected equipment does not exist.',
            'schedule_type.required' => 'Schedule type is required.',
            'schedule_type.in' => 'Schedule type must be either Delivery or Return.',
        ];
    }
}
