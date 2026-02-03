<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions;

use App\Http\Controllers\Api\BaseController;
use App\Models\Opportunity\OpportunityQuestion;
use App\Http\Requests\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions\StoreQuestionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class StoreQuestionController extends BaseController
{
    public function __invoke(StoreQuestionRequest $request): JsonResponse
    {
        try {
           $question = OpportunityQuestion::create([
                'type'          => $request->type,
                'question_key'  => Str::slug($request->question_text, '_'),
                'question_text' => $request->question_text,
                'sub_text'      => $request->sub_text,
                'required'      => $request->required ?? false,
                'answer_type'   => $request->answer_type,
                'answer_grid'   => (string) ($request->answer_grid ?? '100'),
                'display_order' => $request->display_order ?? 0,
                'status'        => 1,
            ]);


            return response()->json([
                'success' => true,
                'message' => 'Question created successfully',
                'data'    => $question,
            ], 201);

        } catch (Throwable $e) {
            Log::error('Failed to create opportunity question', [
                'payload' => $request->validated(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create question',
            ], 500);
        }
    }
}
