<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Question;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Helpers\ModelHelper;

use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;

class CopyController extends Controller
{
    public function __invoke($id)
    {
        DB::beginTransaction();

        try {
            // Find original question
            $question = RentalReadyChecklistQuestion::with('answers')->findOrFail($id);

            // Duplicate question
            $newQuestion = $question->replicate();
            $newQuestion->question_name = $question->question_name . ' (Copy)';

            // If your question model has unique_id:
            if (isset($question->unique_id)) {
                $newQuestion->unique_id = ModelHelper::generateUniqueID(
                    new RentalReadyChecklistQuestion,
                    'RQQ'
                );
            }

            $newQuestion->save();

            // Duplicate all Answers
            foreach ($question->answers as $ans) {
                RentalReadyChecklistQuestionAnswer::create([
                    'answer_name'  => $ans->answer_name,
                    'type'         => $ans->type,
                    'index_number' => $ans->index_number,
                    'question_id'  => $newQuestion->id,
                ]);
            }

            DB::commit();

            // Open Questions tab after redirect
            session()->flash('active_tab', 'questions');

            session()->flash('edit_questions_open', $newQuestion->id);


            flash('Question copied successfully.')->success();

            return redirect()->route('admin.checklist-management.rental-ready.index');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            session()->flash('active_tab', 'questions');
            flash('Something went wrong while copying the question.')->error();

            return redirect()->back();
        }
    }
}
