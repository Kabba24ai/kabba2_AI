<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

// Helpers
use App\Helpers\PurifyHelper;
use App\Http\Requests\ApiBaseFormRequest;

class UpdatePoidRequest extends ApiBaseFormRequest
{
    /**
     * Prepare data before validation
     */
    protected function prepareForValidation()
    {
        // Purify all input data
        $input = PurifyHelper::purify($this->all(), []);
        $this->merge($input);

        // Optional: Trim PO ID
        if ($this->has('po_id')) {
            $this->merge([
                'po_id' => trim($this->po_id)
            ]);
        }
    }

    /**
     * Validation Rules
     */
    public function rules()
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'po_id'    => 'nullable',
        ];
    }

    /**
     * Custom Messages
     */
    public function messages()
    {
        return [
            'order_id.required' => 'Order ID is required.',
            'order_id.exists'   => 'Selected order does not exist.',
            'po_id.max'         => 'PO ID may not be greater than 255 characters.',
        ];
    }
}
