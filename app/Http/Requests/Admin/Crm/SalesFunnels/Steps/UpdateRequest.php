<?php

namespace App\Http\Requests\Admin\Crm\SalesFunnels\Steps;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'funnel_unique_id'      => ['required', 'exists:sales_funnels,unique_id'],
            'step_type'             => ['required', 'in:SMS,Email'],
            'timing_reference_type' => ['required', 'in:funnel_entry_time,order_created_datetime,order_paid_datetime,rental_delivery_datetime,rental_return_datetime,lead_added_datetime,after_previous_event'],
            'offset_direction'      => ['required', 'in:before,after'],

            // Three-part combined offset
            'offset_days'           => ['required', 'integer', 'min:0', 'max:30'],
            'offset_hours'          => ['required', 'integer', 'min:0', 'max:23'],
            'offset_minutes_val'    => ['required', 'integer', 'in:0,15,30,45'],

            'message_category_id'   => ['required', 'integer'],
            'message_template_id'   => ['required', 'integer'],
        ];
    }
}
