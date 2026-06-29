<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\Users\UpdateUserVacationRequest;
use App\Http\Resources\Api\TimeTracker\V1\Users\EmployeeResource;
use App\Models\Iam\Personnel\User;
use App\Services\TimeTrackerAuditService;

class UpdateUserVacationController extends BaseController
{
    /**
     * Update only vacation settings of a user
     */
    public function __invoke(UpdateUserVacationRequest $request, User $user)
    {
        $data = $request->validated();

        // dd($data);

        // Snapshot vacation field values BEFORE the if-block modifies $data and BEFORE update
        $oldValues = [
            'vacation_eligible'               => $user->vacation_eligible,
            'vacation_allotment_hour_id'      => $user->vacation_allotment_hour_id,
            'vacation_start_day_id'           => $user->vacation_start_day_id,
            'bonus_vacation_hours'            => $user->bonus_vacation_hours,
            'bonus_vacation_hours_start_date' => $user->bonus_vacation_hours_start_date,
            'bonus_vacation_hours_end_date'   => $user->bonus_vacation_hours_end_date,
        ];

        // If vacation disabled → clear policies
        if (! $data['vacation_eligible']) {
            $data['vacation_allotment_hour_id'] = null;
            $data['vacation_start_day_id'] = null;
        }

        $user->update($data);

        // Audit: one row per changed vacation field
        $updateType      = $data['vacation_eligible'] ? 'vacation_enabled' : 'vacation_disabled';
        $requestedFields = array_keys($data);

        foreach ($data as $field => $newVal) {
            if (! array_key_exists($field, $oldValues)) {
                continue;
            }

            $oldRaw = $oldValues[$field];
            if ($oldRaw === null) {
                $oldNorm = null;
            } elseif (is_bool($oldRaw)) {
                $oldNorm = $oldRaw ? '1' : '0';
            } elseif ($oldRaw instanceof \Carbon\Carbon) {
                $oldNorm = $oldRaw->toDateString();
            } else {
                $oldNorm = (string) $oldRaw;
            }

            if ($newVal === null) {
                $newNorm = null;
            } elseif (is_bool($newVal)) {
                $newNorm = $newVal ? '1' : '0';
            } else {
                $newNorm = (string) $newVal;
            }

            if ($oldNorm === $newNorm) {
                continue;
            }

            TimeTrackerAuditService::log([
                'store_id'    => $user->store_id,
                'employee_id' => $user->id,
                'entity_type' => 'employee_vacation',
                'entity_id'   => $user->id,
                'action'      => 'vacation_update',
                'field'       => $field,
                'old_value'   => $oldNorm,
                'new_value'   => $newNorm,
                'metadata'    => [
                    'update_type'            => $updateType,
                    'request_fields_changed' => $requestedFields,
                ],
            ]);
        }

        // Reload relations for response
        $user->load([
            'roles',
            'vacationAllotmentHour',
            'vacationStartDay',
            'store.hours'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vacation settings updated successfully.',
            'data' => new EmployeeResource($user),
        ]);
    }
}
