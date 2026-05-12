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

            'positions' => collect(
                data_get($this->job_preferences, 'positions', [])
            )->map(function ($id) {
                $position = \App\Models\Stores\EmploymentPosition::find($id);

                return $position
                    ? [
                        'id' => (string) $position->id,
                        'title' => $position->title,
                    ]
                    : null;
            })->filter()->values(),

            'status' => match ((int) $this->status) {
                0 => 'pending',
                1 => 'reviewed',
                2 => 'accepted',
                3 => 'rejected',
                4 => 'archive',

                5 => 'call_first_interview',
                6 => 'call_second_interview',
                7 => 'extend_offer',

                default => 'pending',
            },

            'created_at' => optional($this->created_at)->toDateString(),
        ];
    }
}
