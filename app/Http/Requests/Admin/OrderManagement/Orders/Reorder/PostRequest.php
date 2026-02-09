<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders\Reorder;

use App\Helpers\PurifyHelper;
use App\Http\Requests\ApiBaseFormRequest;

class PostRequest extends ApiBaseFormRequest
{
    protected function prepareForValidation()
    {
        // Purify all input data
        $input = PurifyHelper::purify($this->all(), []);
        $this->merge($input);

        // // Normalize delivery_dates array values to Y-m-d without filling blanks
        // if (!empty($this->delivery_dates) && is_array($this->delivery_dates)) {
        //     $normalizedDates = array_map(function ($date) {
        //         $date = trim((string) $date);
        //         if ($date === '') {
        //             return null; // keep blank as null
        //         }

        //         $ts = strtotime($date);
        //         return $ts ? date('Y-m-d', $ts) : null;
        //     }, $this->delivery_dates);

        //     $this->merge(['delivery_dates' => $normalizedDates]);
        // }
    }

    public function rules()
    {
        return [
            'order_type' => 'required|in:new,existing_order',
        ];
    }

    public function messages()
    {
        return [
        ];
    }
}
