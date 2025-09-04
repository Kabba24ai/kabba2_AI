<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\Customer;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestionLog;
use App\Models\MaintenanceManagement\Equipment ;



use App\Models\Iam\Personnel\User;
use Illuminate\Http\Request;


class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $validated = $request->all();

        $inspection_data = User::where('id', $request->input('inspectorSelect'))->first();

        // dd($validated);

        DB::beginTransaction();

        try {
            // Parse JSON from hidden input
            $qaPayload = json_decode($request->input('rental_ready_all_qa_json'), true);

            //  Create the Template
            $template = EquipmentRentalReadyTemplate::create([
                'equipment_id'             => $request->input('equipment_id'),
                'employee_id'              => $request->input('inspectorSelect'),
                'employee_name'            => $inspection_data->full_name ,
                'inspection_date'          => $request->input('inspection_date') ?? now()->toDateString(),
                'inspection_time'          => now()->format('H:i:s'),
                'equipment_hours'          => $request->input('equipmentHours'),
                'general_notes'            => $request->input('general_notes'),
                'status'                   => 'Draft',
                'total_questions'          => $qaPayload['counts']['total_questions'] ?? 0,
                'required_questions'       => $qaPayload['counts']['required_questions'] ?? 0,
                'optional_questions'       => $qaPayload['counts']['optional_questions'] ?? 0,
                'required_items_completed' => $qaPayload['counts']['required_items_completed'] ?? 0,
                'items_requiring_maintenance' => $qaPayload['counts']['items_requiring_maintenance'] ?? 0,
                'damaged_items'            => $qaPayload['counts']['damaged_items'] ?? 0,
                'created_by'               => auth()->id(),
            ]);

            // 2. Create Question records
            foreach ($qaPayload['questions'] as $q) {
                $question = EquipmentRentalReadyChecklistQuestion::create([
                    'equipment_rental_ready_template_id' => $template->id,
                    'rental_ready_checklist_questions_id' => $q['id'] ?? null, 
                    'selected_answer_id'                 => $q['selected_answer']['id'] ?? null,
                    'rental_ready_qa_json'               => json_encode($q),
                    'general_notes'                      => $q['note'] ?? null,
                ]);

                // 3. Create log for each question
                EquipmentRentalReadyChecklistQuestionLog::create([
                    'equipment_checklist_question_id'   => $question->id,
                    'equipment_rental_ready_template_id' => $template->id,
                    'rental_ready_all_qa_json'          => json_encode($qaPayload),
                    'action_by'                         => auth()->id(),
                    'action_user_name'                  => auth()->user()->full_name,
                ]);
            }



            // 4 update equipment last inspected info adn status


            $equipment = Equipment::find($request->input('equipment_id'));
            if ($equipment) {
                // decide status based on checklist status / button pressed
                $status = $request->input('equipment_status') ?? 'maintenance';

                $equipment->update([
                    'current_status'  => match ($status) {
                        'available' => 'available',   // rental ready
                        'damaged'   => 'damaged',     // marked damaged
                        'maintenance' => 'maintenance', // if you ever use it
                    },
                    'updated_by' => auth()->id(),
                ]);
            }


            DB::commit();

            flash('Checklist saved successfully.')->success();

            return redirect()->route('admin.checklist-management.equipment-management.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while saving checklist: ' . $e->getMessage())->error();

            return redirect()->back()->withInput();
        }
    }
}
