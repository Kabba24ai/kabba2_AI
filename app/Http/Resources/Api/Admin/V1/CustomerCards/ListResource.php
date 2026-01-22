<?php

namespace App\Http\Resources\Api\Admin\V1\CustomerCards;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request)
    {
        $return = [
            'id' => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? 0,
            'customer_id' => $this->customer_id ?? 0,
            'payment_profile_id' => $this->payment_profile_id ?? 0,
            'first_name' => $this->first_name ?? '',
            'last_name' => $this->last_name ?? '',
            'card_number' => $this->card_number ?? '',
            'card_type' => $this->card_type ?? '',
        ];

        return $return;
    }
}
