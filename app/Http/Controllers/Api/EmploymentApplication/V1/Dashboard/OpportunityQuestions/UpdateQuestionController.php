<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions;

use App\Http\Controllers\Api\BaseController;
use App\Models\Opportunity\OpportunityQuestion;
use App\Http\Requests\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions\UpdateQuestionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class UpdateQuestionController extends BaseController
{
    public function __invoke(
        UpdateQuestionRequest $request,
        string $unique_id
    ): JsonResponse {
        try {
            $question = OpportunityQuestion::where('unique_id', $unique_id)
                ->firstOrFail();

           $question->update([
                ...$request->validated(),
                'answer_grid'  => (string) $request->answer_grid,
                'question_key' => Str::slug($request->question_text, '_'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'data'    => $question,
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to update opportunity question', [
                'unique_id' => $unique_id,
                'payload'   => $request->validated(),
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update question',
            ], 500);
        }
    }
}
