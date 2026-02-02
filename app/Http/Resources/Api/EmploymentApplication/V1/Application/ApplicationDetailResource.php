<?php

namespace App\Http\Resources\Api\EmploymentApplication\V1\Application;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Locations\State;
use App\Helpers\MediaHelper;

class ApplicationDetailResource extends JsonResource
{
    public function toArray($request)
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


       $licenseStateId = data_get($this->driving_details, 'license_state');

        $licenseState = $licenseStateId
            ? State::find($licenseStateId)
            : null;

        $licenseStateName = $licenseState?->name;
        $licenseStateAbbr = $licenseState?->abbreviation;

        return [
            'id' => (string) $this->id,
            'unique_id' => $this->unique_id,

            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,

            'start_date' =>optional($this->start_date)->toDateString(),

         'license_state_labal' => $licenseStateAbbr ?? $licenseStateName,
  
            
            // use existing media relationship + getUrl()
            'resume_url' => $this->media?->getUrl(),

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

