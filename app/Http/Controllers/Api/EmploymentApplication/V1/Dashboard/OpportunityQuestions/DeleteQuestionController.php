<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions;

use App\Http\Controllers\Api\BaseController;
use App\Models\Opportunity\OpportunityQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DeleteQuestionController extends BaseController
{
    public function __invoke(string $uniqueId): JsonResponse
    {
        try {
            $question = OpportunityQuestion::where('unique_id', $uniqueId)->firstOrFail();

            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to delete opportunity question', [
                'unique_id' => $uniqueId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question',
            ], 500);
        }
    }
}
