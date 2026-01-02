<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Vacation;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\TimeTracker\V1\Vacation\UpdateUserVacationBalanceRequest;

class UpdateUserVacationController extends BaseController
{
    public function __invoke(
        UpdateUserVacationBalanceRequest $request,
        $employeeId
    ): JsonResponse {
        $validated = $request->validated();

        $user = User::findOrFail($employeeId);

        $user->update([
            'vacation_allotment_hour_id' => $validated['vacation_allotment_hour_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vacation balance updated successfully',
        ]);
    }
}
