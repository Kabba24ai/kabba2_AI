<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

// Helpers
use App\Helpers\PurifyHelper;
use App\Http\Requests\ApiBaseFormRequest;

class UpdateProductScheduleRequest extends ApiBaseFormRequest
{
    public function authorize()
    {
        // Adjust authorization as needed
        return true;
    }

    protected function prepareForValidation()
    {
        // Purify all input data
        $input = PurifyHelper::purify($this->all(), []);
        $this->merge($input);

        // Normalize delivery_date
        if (!empty($this->delivery_date)) {
            try {
            $this->merge([
                'delivery_date' => date('Y-m-d', strtotime($this->delivery_date))
            ]);
            } catch (\Exception $e) {
            // Ignore invalid date format
            }
        }

        // Normalize delivery_time
        if (!empty($this->delivery_time)) {
            try {
            $this->merge([
                'delivery_time' => date('H:i', strtotime($this->delivery_time))
            ]);
            } catch (\Exception $e) {
            // Ignore invalid time format
            }
        }

        // Normalize pickup_date
        if (!empty($this->pickup_date)) {
            try {
            $this->merge([
                'pickup_date' => date('Y-m-d', strtotime($this->pickup_date))
            ]);
            } catch (\Exception $e) {
            // Ignore invalid date format
            }
        }

        // Normalize pickup_time
        if (!empty($this->pickup_time)) {
            try {
            $this->merge([
                'pickup_time' => date('H:i', strtotime($this->pickup_time))
            ]);
            } catch (\Exception $e) {
            // Ignore invalid time format
            }
        }
    }

    public function rules()
    {
        return [
            'type' => 'required|string|in:delivery,return',
            // Delivery fields (match OrderProduct model fields)
            'delivery_date' => 'nullable|date',
            'delivery_time' => 'nullable|string',
            'delivery_transport_mode' => 'nullable|string|in:Store,Truck',
            'delivery_status' => 'nullable|string|in:Pending,Completed,Reschedule',
            'delivery_store_id' => 'nullable|integer|exists:stores,id',
            'delivery_by' => 'nullable|integer|exists:users,id',
            // Return fields (pickup in DB)
            'pickup_date' => 'nullable|date',
            'pickup_time' => 'nullable|string',
            'pickup_transport_mode' => 'nullable|string|in:Store,Truck',
            'pickup_status' => 'nullable|string|in:Pending,Completed,Reschedule',
            'pickup_store_id' => 'nullable|integer|exists:stores,id',
            'pickup_by' => 'nullable|integer|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'type.in' => 'Type must be delivery or return.',
            'delivery_store_id.exists' => 'Selected delivery store does not exist.',
            'pickup_store_id.exists' => 'Selected pickup/return store does not exist.',
        ];
    }
}
