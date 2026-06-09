<?php

namespace App\Http\Requests\Api\Admin\V1\Dispatch;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateStatusRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'order_product_unique_id' => ['required', 'string', 'exists:order_products,unique_id'],
            'schedule_type'           => ['required', 'in:Delivery,Return'],
            'schedule_status'         => ['required', 'in:Pending,Completed'],
        ];
    }
}
