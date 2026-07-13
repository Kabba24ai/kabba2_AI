<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Question;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Services\ChecklistManagement\QuestionCrudService;
use App\Http\Requests\Admin\ChecklistManagement\CustomerAdmin\Question\StoreRequest;

class StoreController extends Controller
{
    public function __construct(private QuestionCrudService $questionCrudService)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        try {
            // options is already an array, no need to json_decode
            $answerRows = collect($validated['options'])->values()->map(fn ($option, $index) => [
                'index_number'         => $index + 1,
                'answer_delivery_text' => $option['answer_delivery_text'] ?? null,
                'answer_return_text'   => $option['answer_return_text'] ?? null,
                'delivery_amt'         => $option['delivery_amt'] ?? null,
                'return_amt'           => $option['return_amt'] ?? null,
                'required'             => $option['required'] ?? 0,
                'sync_texts'           => !empty($option['syncEnabled']) && $option['syncEnabled'] !== 'false' ? 1 : 0,
                'is_damaged'           => $option['is_damaged'] ?? 0,
            ])->all();

            $this->questionCrudService->store(
                CustomerAdminQuestion::class,
                [
                    'question_name'          => $validated['question_name'],
                    'category_id'            => $validated['category_id'],
                    'question_delivery_text' => $validated['question_delivery_text'],
                    'question_return_text'   => $validated['question_return_text'],
                    'required_question'      => $validated['required_question'] ?? 0,
                ],
                CustomerAdminQuestionAnswer::class,
                $answerRows
            );

            flash('Question created successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'questions');

            return redirect()->route('admin.checklist-management.customer-admin.index');

        } catch (\Throwable $e) {
            report($e);

            flash('Something went wrong while creating the question.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the question.']);
        }
    }
}
