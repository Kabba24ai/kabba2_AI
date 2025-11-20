<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Question;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
// Request
use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Question\StoreRequest;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            //  Create the Question
            $question = RentalReadyChecklistQuestion::create([
                'question_name'     => $validated['question_name'],
                'category_id'       => $validated['category_id'],
                'required_question' => $validated['required_question'] ?? 0,
            ]);

            //  Decode the JSON options
            $options = json_decode($validated['options'], true);

            //  Save each option as an Answer
            foreach ($options as $index => $option) {
                RentalReadyChecklistQuestionAnswer::create([
                    'answer_name'  => $option['text'],
                    'type'         => $option['status'], 
                    'index_number' => $index + 1, 
                    'question_id'  => $question->id,
                ]);
            }

            DB::commit();

            flash('Question created successfully.')->success();

             session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'questions');

            return redirect()
                ->route('admin.checklist-management.rental-ready.index');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while creating the question.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the question.']);
        }
    }
}
