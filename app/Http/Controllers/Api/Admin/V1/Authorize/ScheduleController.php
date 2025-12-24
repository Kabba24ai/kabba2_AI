<?php

namespace App\Http\Controllers\Api\Admin\V1\Authorize;

use App\Http\Controllers\Api\BaseController;
use App\Models\Authrise\Submission;
use App\Services\AuthorizeNetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Throwable;

use App\Http\Requests\Api\Admin\V1\Authorize\ScheduleRequest;

class ScheduleController extends BaseController
{

    /**
     * Schedule Auhorize
     * @group Kabba Sales Site
     */
    public function __invoke(ScheduleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $submission = Submission::where('unique_id', $validated['unique_id'])->firstOrFail();

            $submission->fill([
                'schedule_datetime' => $validated['schedule_datetime'] ?? null,
            ])->save();

            return response()->json([
                'success' => true,
                'message' => 'schedule slot successfully set'
            ], 201);
        } catch (Throwable $exception) {

            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
    }
}
