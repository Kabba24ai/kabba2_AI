<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Question;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;

class DeleteController extends Controller
{
    public function __invoke($unique_id)
    {
        DB::beginTransaction();

          try {
            // Find question by unique_id
            $question = RentalReadyChecklistQuestion::where('unique_id', $unique_id)->firstOrFail();

            // Delete related answers first (if no cascade)
            $question->answers()->delete();

            // Delete the question
            $question->delete();

            DB::commit();

            flash('Question deleted successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'questions');

            return redirect()
                ->route('admin.checklist-management.rental-ready.index')
                ->with('success', 'Question deleted successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while deleting the question.')->error();

            return redirect()
                ->back()
                ->withErrors(['error' => 'An error occurred while deleting the question.']);
        }
    }
}
