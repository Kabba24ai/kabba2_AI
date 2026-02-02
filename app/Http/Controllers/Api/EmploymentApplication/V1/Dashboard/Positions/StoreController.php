<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Positions;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\EmploymentApplication\V1\Dashboard\Positions\StoreRequest;
use App\Models\Stores\EmploymentPosition;
use Illuminate\Http\JsonResponse;

class StoreController extends BaseController
{
    public function __invoke(
        StoreRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $position = EmploymentPosition::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Position created successfully',
            'data' => $position,
        ]);
    }
}
