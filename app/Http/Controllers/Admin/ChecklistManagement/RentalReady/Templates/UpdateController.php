<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
// Request
use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Templates\StoreRequest;

use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Templates\UpdateRequest;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
     public function __invoke(UpdateRequest $request, $unique_id)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Find template by unique_id
            $template = RentalReadyChecklistTemplate::where('unique_id', $unique_id)->firstOrFail();

            // Update template fields
            $template->update([
                'template_name' => $validated['template_name'],
                'description' => $validated['description'] ?? null,
                'equipment_category_id' => $validated['equipment_category'],
                'active_template' => $validated['is_active'] ?? 0,
            ]);

            // Reset template questions
            RentalReadyChecklistTemplateQuestion::where('template_id', $template->id)->delete();

            // Re-insert questions
            $questions = json_decode($validated['questions'], true);

            foreach ($questions as $index => $q) {
                RentalReadyChecklistTemplateQuestion::create([
                    'template_id' => $template->id,
                    'question_id' => $q['id'],
                    'index_number' => $index + 1,
                    'required' => $q['required'] ?? false, 
                ]);
            }

            DB::commit();

            flash('Template updated successfully.')->success();
            session()->flash('active_tab', 'templates');

            return redirect()
                ->route('admin.checklist-management.rental-ready.index')
                ->with('success', 'Template updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while updating the template.')->error();
            session()->flash('active_tab', 'templates');

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the template.']);
        }
    }
}
