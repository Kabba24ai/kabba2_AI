<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Question;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;

class CopyController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($id)
    {
        DB::beginTransaction();

        try {
            // Original question with answers
            $question = CustomerAdminQuestion::with('answers')->findOrFail($id);

          // Copy question
$newQuestion = $question->replicate();

//  IMPORTANT: reset unique_id so boot() generates a new one
$newQuestion->unique_id = null;

$newQuestion->question_name = $question->question_name . ' (Copy)';
$newQuestion->save();

            // Copy answers
            foreach ($question->answers as $answer) {
                CustomerAdminQuestionAnswer::create([
                    'question_id'          => $newQuestion->id,
                    'index_number'         => $answer->index_number,
                    'answer_delivery_text' => $answer->answer_delivery_text,
                    'answer_return_text'   => $answer->answer_return_text,
                    'delivery_amt'         => $answer->delivery_amt,
                    'return_amt'           => $answer->return_amt,
                    'required'             => $answer->required,
                    'sync_texts'           => $answer->sync_texts,
                    'is_damaged'           => $answer->is_damaged,
                ]);
            }

            DB::commit();

            //  OPEN EDIT QUESTION MODAL
            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'questions');
            session()->flash('open_edit_question', $newQuestion->id);

            flash('Question copied successfully.')->success();

            return redirect()->route('admin.checklist-management.customer-admin.index');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            session()->flash('active_tab', 'questions');

            flash('Something went wrong while copying the question.')->error();

            return redirect()->back();
        }
    }
}
