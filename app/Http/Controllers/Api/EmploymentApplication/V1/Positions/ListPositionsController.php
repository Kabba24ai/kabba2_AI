<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Positions;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stores\EmploymentPosition;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
 
class ListPositionsController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        // Fetch active positions
        $positions = EmploymentPosition::where('is_active', true)
            ->orderBy('title') // optional ordering
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Employment positions fetched successfully.',
            'data'    => $positions,
        ]);
    }
}
