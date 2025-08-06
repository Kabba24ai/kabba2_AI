<?php

namespace App\Http\Resources\Api\Admin\V1\OrderAddresses;

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
            'order_id' => $this->order_id ?? 0,
            'type' => $this->type ?? '',
            'first_name' => $this->first_name ?? '',
            'last_name' => $this->last_name ?? '',
            'full_name' => $this->full_name ?? '',
            'full_address' => $this->full_address ?? '',
            'email' => $this->email ?? '',
            'phone' => $this->phone ?? '',
            'address' => $this->address ?? '',
            'city' => $this->city ?? '',
            'state' => $this->state ?? '',
            'state_id' => $this->state_id ?? 0,
            'zip_code' => $this->zip_code ?? '',
        ];

        return $return;
    }
}
