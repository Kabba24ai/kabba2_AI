<?php

namespace App\Http\Resources\Api\EmploymentApplication\V1\Stores;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'unique_id'    => $this->unique_id,
            'name'   => $this->store_name,
            'phone'        => $this->phone,
            'email'        => $this->email,
            'address'      => $this->full_address,
            'city'         => $this->city,
            'state'        => optional($this->state)->name,
            'zip'     => $this->zip_code,
            'status'       => $this->status,
            'created_at'   => $this->created_at,
            'is_active'     => $this->status === 'Active',
            'display_order'   => $this->created_at,
            'hoursOfOperation' => $this->hoursOfOperation->map(function ($hour) {
                return [
                    'id'         => $hour->id,
                    'day_of_week'   => $hour->day_name,
                    'is_closed'  => $hour->is_closed,
                    'open_time' => $hour->start_time,
                    'close_time'   => $hour->end_time,
                    'day_order'   => $hour->end_time,
                    'created_at'   => $hour->end_time,
                ];
            }),
        ];
    }
}
