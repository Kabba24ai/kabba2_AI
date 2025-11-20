<?php

namespace App\Http\Resources\Api\Admin\V1\Stores;

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
            'store_name' => $this->store_name ?? '',
            'phone' => $this->phone ?? '',
            'email' => $this->email ?? '',
            'address' => $this->address ?? '',
            'state_id' => $this->state_id ?? 0,
            'city' => $this->city ?? '',
            'zip_code' => $this->zip_code ?? '',
            'is_primary' => $this->is_primary ?? '',
            'status' => $this->status ?? '',
            'full_address' => $this->full_address ?? '',
        ];

        return $return;
    }
}
