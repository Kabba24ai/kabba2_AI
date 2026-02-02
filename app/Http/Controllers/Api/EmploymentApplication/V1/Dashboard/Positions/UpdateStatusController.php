<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Positions;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stores\EmploymentPosition;
use Illuminate\Http\JsonResponse;

class UpdateStatusController extends BaseController
{
    public function __invoke(EmploymentPosition $position): JsonResponse
    {
        $position->update([
            'is_active' => !$position->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employment Position status updated successfully',
            'data' => [
                'id' => $position->id,
                'is_active' => $position->is_active,
            ],
        ]);
    }
}
