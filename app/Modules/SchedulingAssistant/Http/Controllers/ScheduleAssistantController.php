<?php

namespace App\Modules\SchedulingAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SchedulingAssistant\Services\ScheduleAssistantService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Throwable;

class ScheduleAssistantController extends Controller
{
    public function __construct(
        protected ScheduleAssistantService $scheduleAssistantService
    ) {
    }

    public function show(int $orderProductId): JsonResponse
    {
        try {
            $result = $this->scheduleAssistantService->analyzeOrderProduct($orderProductId);

            return response()->json([
                'success' => true,
                'data' => $result->toArray(),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Scheduling assistant analysis failed.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
