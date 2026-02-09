<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions;

use App\Http\Controllers\Api\BaseController;
use App\Models\Opportunity\OpportunityQuestion;
use App\Http\Resources\Api\EmploymentApplication\V1\Application\OpportunityQuestionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuestionListController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $type = $request->query('type'); // mechanical | driving | computer

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Question type is required.',
            ], 422);
        }

        $questions = OpportunityQuestion::with(['options'])
            ->where('type', $type)
              ->orderBy('display_order') 
            ->get();

              
        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . ' questions fetched successfully.',
            'data'    => OpportunityQuestionResource::collection($questions),
        ]);
    }
}
