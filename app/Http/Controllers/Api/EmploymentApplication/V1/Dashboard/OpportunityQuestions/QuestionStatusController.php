<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions;

use App\Http\Controllers\Api\BaseController;
use App\Models\Opportunity\OpportunityQuestion;
use Illuminate\Http\JsonResponse;

class QuestionStatusController extends BaseController
{
    public function __invoke(string $uniqueId): JsonResponse
    {
        $question = OpportunityQuestion::where('unique_id', $uniqueId)->firstOrFail();

        $question->status = $question->status === 1 ? 0 : 1;
        $question->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => [
                'status' => $question->status,
            ],
        ]);
    }
}
