<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\Users\UpdateUserVacationRequest;
use App\Http\Resources\Api\TimeTracker\V1\Users\EmployeeResource;
use App\Models\Iam\Personnel\User;

class UpdateUserVacationController extends BaseController
{
    /**
     * Update only vacation settings of a user
     */
    public function __invoke(UpdateUserVacationRequest $request, User $user)
    {
        $data = $request->validated();

        // If vacation disabled → clear policies
        if (! $data['vacation_eligible']) {
            $data['vacation_allotment_hour_id'] = null;
            $data['vacation_start_day_id'] = null;
        }

        $user->update($data);

        // Reload relations for response
        $user->load([
            'roles',
            'vacationAllotmentHour',
            'vacationStartDay',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vacation settings updated successfully.',
            'data' => new EmployeeResource($user),
        ]);
    }
}
