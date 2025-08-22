<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;


use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
// Request
use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Templates\StoreRequest;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        // dd($validated);
       
        DB::beginTransaction();

        try {
            // Save the template
            $template = RentalReadyChecklistTemplate::create([
                'template_name' => $validated['template_name'],
                'description' => $validated['description'] ?? null,
                'equipment_category_id' => $validated['equipment_category'],
                'active_template' => $validated['is_active'] ?? 0, 
            ]);

            // Decode the JSON options (questions)
            $questions = json_decode($validated['questions'], true);

            foreach ($questions as $index => $q) {
                RentalReadyChecklistTemplateQuestion::create([
                    'template_id' => $template->id,
                    'question_id' => $q['id'], 
                    'index_number' => $index + 1,
                ]);
            }

            DB::commit();

            flash('Question Template created successfully.')->success();

             session()->flash('active_tab', 'templates');
        
            return redirect()
                ->route('admin.checklist-management.rental-ready.index')
                ->with('success', 'Question created successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
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
