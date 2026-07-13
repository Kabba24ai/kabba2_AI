<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Question;

use App\Http\Controllers\Controller;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Services\ChecklistManagement\QuestionCrudService;

class CopyController extends Controller
{
    public function __construct(private QuestionCrudService $questionCrudService)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke($id)
    {
        try {
            $newQuestion = $this->questionCrudService->copy(
                CustomerAdminQuestion::class,
                CustomerAdminQuestionAnswer::class,
                (int) $id,
                function ($newQuestion, $original) {
                    // IMPORTANT: reset unique_id so boot() generates a new one
                    $newQuestion->unique_id = null;
                },
                fn ($answer) => [
                    'index_number'         => $answer->index_number,
                    'answer_delivery_text' => $answer->answer_delivery_text,
                    'answer_return_text'   => $answer->answer_return_text,
                    'delivery_amt'         => $answer->delivery_amt,
                    'return_amt'           => $answer->return_amt,
                    'required'             => $answer->required,
                    'sync_texts'           => $answer->sync_texts,
                    'is_damaged'           => $answer->is_damaged,
                ]
            );

            //  OPEN EDIT QUESTION MODAL
            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'questions');
            session()->flash('open_edit_question', $newQuestion->id);

            flash('Question copied successfully.')->success();

            return redirect()->route('admin.checklist-management.customer-admin.index');

        } catch (\Throwable $e) {
            report($e);

            session()->flash('active_tab', 'questions');

            flash('Something went wrong while copying the question.')->error();

            return redirect()->back();
        }
    }
}
