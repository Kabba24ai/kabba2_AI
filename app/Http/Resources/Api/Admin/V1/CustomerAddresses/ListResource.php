<?php

namespace App\Http\Resources\Api\Admin\V1\CustomerAddresses;

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
            'unique_id' => $this->unique_id ?? '',
            'customer_id' => $this->customer_id ?? 0,
            'type' => $this->type ?? '',
            'is_primary' => (bool) ($this->is_primary ?? false),
            'first_name' => $this->first_name ?? '',
            'last_name' => $this->last_name ?? '',
            'full_name' => $this->full_name ?? '',
            'email' => $this->email ?? '',
            'phone' => $this->phone ?? '',
            'address' => $this->address ?? '',
            'city' => $this->city ?? '',
            'state_id' => $this->state_id ?? 0,
            'state_name' => $this->state_name ?? '',
            'zip_code' => $this->zip_code ?? '',
            'country' => $this->country ?? '',
            'full_address' => $this->full_address ?? '',
        ];

        return $return;
    }
}
