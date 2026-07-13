<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Question;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Services\ChecklistManagement\QuestionCrudService;

class DeleteController extends Controller
{
    public function __construct(private QuestionCrudService $questionCrudService)
    {
    }

    public function __invoke($unique_id)
    {
        try {
            $this->questionCrudService->delete(CustomerAdminQuestion::class, $unique_id);

            flash('Question deleted successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'questions');

            return redirect()
                ->route('admin.checklist-management.customer-admin.index');

        } catch (\Throwable $e) {
            report($e);

            flash('Something went wrong while deleting the question.')->error();

            return redirect()
                ->back()
                ->withErrors(['error' => 'An error occurred while deleting the question.']);
        }
    }
}
