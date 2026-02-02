<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Positions;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\EmploymentApplication\V1\Dashboard\Positions\UpdateRequest;
use App\Models\Stores\EmploymentPosition;
use Illuminate\Http\JsonResponse;

class UpdateController extends BaseController
{
    public function __invoke(
        UpdateRequest $request,
        EmploymentPosition $position
    ): JsonResponse {
        $validated = $request->validated();

        $position->update([
            'title' => $validated['title'],
         
            'description' => $validated['description'] ?? null,
         
            'is_active' => $validated['is_active'] ?? $position->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Position updated successfully',
            'data' => $position,
        ]);
    }
}
