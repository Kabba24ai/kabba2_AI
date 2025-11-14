<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\FaqPage\FaqQuestion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WebsiteManagement\FaqPage\FaqQuestions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateController extends Controller
{
    public function __invoke(Request $request, $unique_id)
    {
        $request->validate([
            'question_name' => 'required|string|max:255',
            'answer' => 'required|string',
            'status' => 'required|in:Active,Inactive',
            'related_question_id' => 'nullable|exists:faq_questions,id',
        ]);

        DB::beginTransaction();

        try {
            $faq = FaqQuestions::where('unique_id', $unique_id)->firstOrFail();

            $faq->update([
                'question_name' => $request->question_name,
                'answer' => $request->answer,
                'status' => $request->status,
                'related_question_id' => $request->related_question_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'FAQ updated successfully',
                'data' => [
                    'id' => $faq->id,
                    'unique_id' => $faq->unique_id,
                    'question_name' => $faq->question_name,
                    'answer' => $faq->answer,
                    'status' => $faq->status,
                    'related_question_id' => $faq->related_question_id,
                ],
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('FAQ Update Error:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating FAQ.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
