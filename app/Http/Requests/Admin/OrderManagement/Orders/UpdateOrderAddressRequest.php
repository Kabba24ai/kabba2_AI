<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Helpers\PurifyHelper;
use App\Http\Requests\ApiBaseFormRequest;

class UpdateOrderAddressRequest extends ApiBaseFormRequest
{
    public function rules()
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'state_id' => 'required|integer|exists:states,id',
            'state' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
            'type' => 'required|in:Billing,Shipping',
        ];
    }
}
