<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Question;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Services\ChecklistManagement\QuestionCrudService;
use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Question\UpdateRequest;

class UpdateController extends Controller
{
    public function __construct(private QuestionCrudService $questionCrudService)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateRequest $request, $id)
    {
        $validated = $request->validated();

        try {
            $options = json_decode($validated['options'], true);

            $answerRows = collect($options)->values()->map(fn ($option, $index) => [
                'id'           => $option['id'] ?? null,
                'answer_name'  => $option['text'],
                'type'         => $option['status'],
                'index_number' => $index + 1,
            ])->all();

            $this->questionCrudService->updateWithDiffedAnswers(
                RentalReadyChecklistQuestion::class,
                RentalReadyChecklistQuestionAnswer::class,
                (int) $id,
                [
                    'question_name'     => $validated['question_name'],
                    'category_id'       => $validated['category_id'],
                    'required_question' => $validated['required_question'] ?? 0,
                ],
                $answerRows
            );

            flash('Question updated successfully.')->success();
            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'questions');

            return redirect()->route('admin.checklist-management.rental-ready.index');
        } catch (\Throwable $e) {
            report($e);

            flash('Something went wrong while updating the question.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the question.']);
        }
    }
}
