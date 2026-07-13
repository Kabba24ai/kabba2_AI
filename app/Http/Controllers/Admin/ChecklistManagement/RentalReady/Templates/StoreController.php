<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates;

use App\Http\Controllers\Controller;

use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
use App\Services\ChecklistManagement\TemplateCrudService;
// Request
use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Templates\StoreRequest;

class StoreController extends Controller
{
    public function __construct(private TemplateCrudService $templateCrudService)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        try {
            $questions = json_decode($validated['questions'], true);

            // A plain foreach — not collect($questions), which silently treats a
            // null (invalid-JSON) $questions as an empty collection. The original
            // code's raw foreach over null triggers a PHP warning that this app's
            // exception handler converts into a catchable error, and that exact
            // behavior must be preserved (see PR-B4_3_TEMPLATE_REFACTOR.md).
            $questionRows = [];
            foreach ($questions as $index => $q) {
                $questionRows[] = [
                    'question_id'  => $q['id'],
                    'index_number' => $index + 1,
                ];
            }

            $this->templateCrudService->store(
                RentalReadyChecklistTemplate::class,
                [
                    'template_name'         => $validated['template_name'],
                    'description'           => $validated['description'] ?? null,
                    'equipment_category_id' => $validated['equipment_category'],
                    'active_template'       => $validated['is_active'] ?? 0,
                ],
                RentalReadyChecklistTemplateQuestion::class,
                $questionRows
            );

            flash('Question Template created successfully.')->success();

             session()->flash('active_tab', 'templates');

            return redirect()
                ->route('admin.checklist-management.rental-ready.index');

        } catch (\Throwable $e) {
            report($e);

            session()->flash('active_tab', 'templates');


            flash('Something went wrong while creating the question.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the question.']);
        }
    }
}
