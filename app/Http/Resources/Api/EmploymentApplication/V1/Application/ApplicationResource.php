<?php

namespace App\Http\Resources\Api\EmploymentApplication\V1\Application;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Locations\State;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $city = data_get($this->personal_details, 'city');
        $stateId = data_get($this->personal_details, 'state');
        $zip = data_get($this->personal_details, 'zip_code');

        // Resolve state safely
        $state = $stateId
            ? State::find($stateId)
            : null;

        $stateName = $state?->name;
        $stateAbbr = $state?->abbreviation;

        return [
            'id'        => (string) $this->id,
            'unique_id' => $this->unique_id,

            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'email'      => $this->email,
            'phone'      => $this->phone,

            // Raw values (optional)
            'city'       => $city,
            'state_id'   => $stateId,
            'state'      => $stateName,
            'state_code' => $stateAbbr,
            'zip_code'   => $zip,

            //  Combined human-friendly address
            'address' => trim(
                collect([$city, $stateAbbr ?? $stateName, $zip])
                    ->filter()
                    ->implode(', ')
            ),

            'store_id' => $this->store_id,

            'status' => match ((int) $this->status) {
                0 => 'pending',
                1 => 'reviewed',
                2 => 'accepted',
                3 => 'rejected',
                default => 'pending',
            },

            'created_at' => optional($this->created_at)->toDateString(),
        ];
    }
}
