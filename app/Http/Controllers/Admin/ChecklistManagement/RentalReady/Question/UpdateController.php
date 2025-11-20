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
            // Find existing question
            $question = RentalReadyChecklistQuestion::findOrFail($id);

            // Update question fields
            $question->update([
                'question_name'     => $validated['question_name'],
                'category_id'       => $validated['category_id'],
                'required_question' => $validated['required_question'] ?? 0,
            ]);

            // Decode JSON options
            $options = json_decode($validated['options'], true);

            // Collect existing answers keyed by ID
            $existingAnswers = $question->answers()->get()->keyBy('id');

            $keepIds = []; // To track which ones remain

            foreach ($options as $index => $option) {
                if (!empty($option['id']) && $existingAnswers->has($option['id'])) {
                    // Update existing
                    $answer = $existingAnswers[$option['id']];
                    $answer->update([
                        'answer_name'  => $option['text'],
                        'type'         => $option['status'],
                        'index_number' => $index + 1,
                    ]);
                    $keepIds[] = $answer->id;
                } else {
                    // Create new
                    $new = RentalReadyChecklistQuestionAnswer::create([
                        'answer_name'  => $option['text'],
                        'type'         => $option['status'],
                        'index_number' => $index + 1,
                        'question_id'  => $question->id,
                    ]);
                    $keepIds[] = $new->id;
                }
            }

            // Delete removed answers (not present in new list)
            $question->answers()->whereNotIn('id', $keepIds)->delete();

            DB::commit();

            flash('Question updated successfully.')->success();
            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'questions');

            return redirect()->route('admin.checklist-management.rental-ready.index');
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
