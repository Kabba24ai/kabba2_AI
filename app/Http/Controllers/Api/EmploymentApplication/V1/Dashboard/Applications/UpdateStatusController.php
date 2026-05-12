<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Applications;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stores\Application;
use Illuminate\Http\JsonResponse;

class UpdateStatusController extends BaseController
{
    public function __invoke(Application $application, string $status): JsonResponse
    {
        $allowedStatuses = [
            'pending',
            'reviewed',
            'accepted',
            'rejected',
            'archive',

            //  NEW STATUSES
            'call_first_interview',
            'call_second_interview',
            'extend_offer',
        ];

        if (!in_array($status, $allowedStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status value',
            ], 422);
        }

        $statusMap = [
            'pending' => 0,
            'reviewed' => 1,
            'accepted' => 2,
            'rejected' => 3,
            'archive'  => 4,

            //  NEW MAPPING
            'call_first_interview' => 5,
            'call_second_interview' => 6,
            'extend_offer' => 7,
        ];

        $application->update([
            'status' => $statusMap[$status],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application status updated',
        ]);
    }
}