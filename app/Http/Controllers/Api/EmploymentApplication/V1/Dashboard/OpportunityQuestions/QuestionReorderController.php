<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions\QuestionReorderRequest;
use App\Models\Opportunity\OpportunityQuestion;
use Illuminate\Http\JsonResponse;

class QuestionReorderController extends BaseController
{
    public function __invoke(
        QuestionReorderRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $source = OpportunityQuestion::where(
            'unique_id',
            $validated['source_id']
        )->firstOrFail();

        $target = OpportunityQuestion::where(
            'unique_id',
            $validated['target_id']
        )->firstOrFail();

        // swap display_order
        [$source->display_order, $target->display_order] = [
            $target->display_order,
            $source->display_order,
        ];

        $source->save();
        $target->save();

        return response()->json([
            'success' => true,
            'message' => 'Questions reordered successfully',
        ]);
    }
}
