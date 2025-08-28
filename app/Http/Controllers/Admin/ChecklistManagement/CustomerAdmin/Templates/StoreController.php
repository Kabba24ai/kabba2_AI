<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;


use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion;
// Request
use App\Http\Requests\Admin\ChecklistManagement\CustomerAdmin\Templates\StoreRequest;

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
            $template = CustomerAdminTemplate::create([
                'template_name' => $validated['template_name'],
                'description' => $validated['description'] ?? null,
                'equipment_category_id' => $validated['equipment_category'],
                'active_template' => $validated['is_active'] ?? 0, 
            ]);

            // Decode the JSON options (questions)
            $questions = json_decode($validated['questions'], true);

            foreach ($questions as $index => $q) {
                CustomerAdminTemplateQuestion::create([
                    'template_id' => $template->id,
                    'question_id' => $q['id'], 
                    'index_number' => $index + 1,
                ]);
            }

            DB::commit();

            flash('Question Template created successfully.')->success();

             session()->flash('active_tab', 'templates');
        
            return redirect()
                ->route('admin.checklist-management.rental-ready.index');

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
