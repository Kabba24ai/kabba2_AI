<?php

namespace App\Http\Resources\Api\EmploymentApplication\V1\Application;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Locations\State;

class ApplicationDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (string) $this->id,
            'unique_id' => $this->unique_id,

            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,

            'start_date' =>optional($this->start_date)->toDateString(),

            'address' => collect([
                data_get($this->personal_details, 'city'),
                data_get($this->personal_details, 'state'),
                data_get($this->personal_details, 'zip_code'),
            ])->filter()->implode(', '),

            'store_id' => $this->store_id,

            'status' => match ((int) $this->status) {
                0 => 'pending',
                1 => 'reviewed',
                2 => 'accepted',
                3 => 'rejected',
            },

            'created_at' => optional($this->created_at)->toDateString(),

            // Flatten JSON fields
            ...data_get($this->job_preferences, []),
            ...data_get($this->experience_details, []),
            ...data_get($this->skills, []),
            ...data_get($this->driving_details, []),
        ];
    }
}

