<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Question;

use App\Http\Controllers\Controller;
use App\Helpers\ModelHelper;

use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Services\ChecklistManagement\QuestionCrudService;

class CopyController extends Controller
{
    public function __construct(private QuestionCrudService $questionCrudService)
    {
    }

    public function __invoke($id)
    {
        try {
            $newQuestion = $this->questionCrudService->copy(
                RentalReadyChecklistQuestion::class,
                RentalReadyChecklistQuestionAnswer::class,
                (int) $id,
                function ($newQuestion, $original) {
                    // If your question model has unique_id:
                    if (isset($original->unique_id)) {
                        $newQuestion->unique_id = ModelHelper::generateUniqueID(
                            new RentalReadyChecklistQuestion,
                            'RQQ'
                        );
                    }
                },
                fn ($answer) => [
                    'answer_name'  => $answer->answer_name,
                    'type'         => $answer->type,
                    'index_number' => $answer->index_number,
                ]
            );

            // Open Questions tab after redirect
            session()->flash('active_tab', 'questions');

            session()->flash('edit_questions_open', $newQuestion->id);


            flash('Question copied successfully.')->success();

            return redirect()->route('admin.checklist-management.rental-ready.index');

        } catch (\Throwable $e) {
            report($e);

            session()->flash('active_tab', 'questions');
            flash('Something went wrong while copying the question.')->error();

            return redirect()->back();
        }
    }
}
