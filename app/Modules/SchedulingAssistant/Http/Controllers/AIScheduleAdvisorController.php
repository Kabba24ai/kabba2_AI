<?php

namespace App\Modules\SchedulingAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SchedulingAssistant\Services\AIScheduleAdvisorPipelineService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Throwable;

class AIScheduleAdvisorController extends Controller
{
    public function __construct(
        protected AIScheduleAdvisorPipelineService $pipelineService
    ) {
    }

    public function show(int $orderProductId): JsonResponse
    {
        try {
            $result = $this->pipelineService->analyzeWithAI($orderProductId);

            return response()->json([
                'success' => true,
                'data' => $result,
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
                'message' => 'AI scheduling advisor failed.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
