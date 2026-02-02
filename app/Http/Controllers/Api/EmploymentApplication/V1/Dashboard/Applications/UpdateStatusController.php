<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Applications;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stores\Application;
use Illuminate\Http\JsonResponse;

class UpdateStatusController extends BaseController
{
    public function __invoke(Application $application, string $status): JsonResponse
    {
        if (!in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'])) {
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
