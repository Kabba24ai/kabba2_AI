<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Positions;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stores\EmploymentPosition;
use Illuminate\Http\JsonResponse;

class DeleteController extends BaseController
{
    public function __invoke(EmploymentPosition $position): JsonResponse
    {
        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Position deleted successfully',
        ]);
    }
}
