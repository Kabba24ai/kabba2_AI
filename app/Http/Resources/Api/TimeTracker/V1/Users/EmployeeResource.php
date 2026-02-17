<?php

namespace App\Http\Resources\Api\TimeTracker\V1\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->id,
            'unique_id' => $this->unique_id,
            'first_name' => $this->first_name ?? '',
            'last_name' => $this->last_name ?? '',
            'email' => $this->email ?? '',
            'role' => $this->roles->first()?->short_name ?? 'employee',
            'roles' => $this->roles->pluck('short_name'),
            'roles_name' => $this->roles->pluck('name'),
            'shift_start_time' => $this->shift_start_time ?? null,
            'shift_end_time' => $this->shift_end_time ?? null,
        
            'store' => $this->whenLoaded('store', function () {

                $today = now()->format('l');

                $weekly = $this->store->hours->map(function ($hour) {
                    return [
                        'day' => $hour->day_name,
                        'open' => $hour->start_time,
                        'close' => $hour->end_time,
                        'is_closed' => (bool) $hour->is_closed,
                    ];
                });

                $todayHours = $this->store->hours
                    ->where('day_name', $today)
                    ->first();

                return [
                    'id' => $this->store->id,
                    'store_name' => $this->store->store_name,

                    'today_schedule' => $todayHours ? [
                        'day' => $todayHours->day_name,
                        'open' => $todayHours->start_time,
                        'close' => $todayHours->end_time,
                        'is_closed' => (bool) $todayHours->is_closed,
                    ] : null,

                    'weekly_schedule' => $weekly,
                ];
            }),


            'vacation_allotment_hour' => $this->whenLoaded(
                'vacationAllotmentHour',
                fn () => [
                    'id'    => $this->vacationAllotmentHour->id,
                    'hours' => $this->vacationAllotmentHour->hours,
                    'name'  => $this->vacationAllotmentHour->name,
                ]
            ),

            'vacation_start_day' => $this->whenLoaded(
                'vacationStartDay',
                fn () => [
                    'id'         => $this->vacationStartDay->id,
                    'day_number' => $this->vacationStartDay->day_number,
                    'name'       => $this->vacationStartDay->name,
                ]
            ),
            'vacation_eligible' => $this->vacation_eligible ?? 0,
           
            'created_at' => $this->created_at,
        ];
    }
}
