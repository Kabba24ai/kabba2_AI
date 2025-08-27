<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Question;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Question\UpdateRequest;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateRequest $request, $id)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            //  Find existing question
            $question = RentalReadyChecklistQuestion::findOrFail($id);

            //  Update question fields
            $question->update([
                'question_name'     => $validated['question_name'],
                'category_id'       => $validated['category_id'],
                'required_question' => $validated['required_question'] ?? 0,
            ]);

            //  Decode JSON options
            $options = json_decode($validated['options'], true);

            // clear old answers and re-insert
            $question->answers()->delete();

            foreach ($options as $index => $option) {
                RentalReadyChecklistQuestionAnswer::create([
                    'answer_name'  => $option['text'],
                    'type'         => $option['status'], 
                    'index_number' => $option['index_number'] ?? ($index + 1), 
                    'question_id'  => $question->id,
                ]);
            }

            DB::commit();

            flash('Question updated successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'questions');

            return redirect()
                ->route('admin.checklist-management.rental-ready.index');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while updating the question.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the question.']);
        }
    }
}
