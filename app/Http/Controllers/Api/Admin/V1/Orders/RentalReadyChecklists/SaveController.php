<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\RentalReadyChecklists;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\RentalReadyChecklists\SaveRequest;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\Iam\Personnel\User;
// Resources

// Model
use App\Models\Orders\OrderProduct;

class SaveController extends BaseController
{
    /**
     * Order Rental Ready Checklist Save
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(SaveRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $uniqueId = $validated['order_product_unique_id'];

        $orderProduct = OrderProduct::query()
            ->with(['equipmentRentalReadyTemplate','equipment.checklistMaster.rentalReadyTemplate.templateQuestions.question.answers','equipment.checklistMaster.rentalReadyTemplate.templateQuestions.question.category'])
            ->where('unique_id', $uniqueId)
            ->first();

         if ($orderProduct && $orderProduct->equipment &&
            ($orderProduct->equipment->current_status->isRented() || $orderProduct->equipment->current_status->isAvailable())) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.rental_ready_checklists.invalid_equipment_status'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        if (isset($orderProduct->equipmentRentalReadyTemplate)) {

            $questions = optional($orderProduct->equipmentRentalReadyTemplate->checklistQuestions)
                        ->pluck('rental_ready_qa_json')   // same as map->question but clearer
                        ->filter()            // remove nulls
                        ->values() ?? collect();

            $questions = collect($questions)->map(function ($item) {
                            return is_string($item) ? json_decode($item, true) : $item; // decode to array
                        });
        }else{
            if (!$orderProduct->equipment || !$orderProduct->equipment->checklistMaster?->rental_ready_template_id) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => trans('messages.api.admin.v1.rental_ready_checklists.no_rental_ready_checklist_found'),
                    ],
                    JsonResponse::HTTP_NOT_FOUND,
                );
            }

            $questions = optional($orderProduct->equipment->checklistMaster?->rentalReadyTemplate?->templateQuestions)
                        ->pluck('question')   // same as map->question but clearer
                        ->filter()            // remove nulls
                        ->values() ?? collect();
        }


        if ($questions->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.rental_ready_checklists.no_questions_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $employee = User::where('id', $validated['user_id'])->first();
        if (!$employee) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.users.no_users_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $validatedChecklist = $validated['checklist'] ?? [];
        $validatedChecklist = collect($validatedChecklist)->keyBy('question_unique_id')->toArray();

        $newQuestions = [];
        $statuses = [];
        foreach ($questions as $key => $question) {
            if(is_array($question)){
                $questionUniqueId = $question['id'] ?? null;
                $newQuestions[$key] = $question;
                $newQuestions[$key]['note'] = $validatedChecklist[$questionUniqueId]['note'] ?? '';
                if(isset($questionUniqueId) && isset($validatedChecklist[$questionUniqueId])){
                    $answer = collect($question['options'])->firstWhere('id', $validatedChecklist[$questionUniqueId]['answer_id']);
                    $newQuestions[$key]['selected_answer'] = $answer ?? null;
                    $statuses[] = $answer['status'] ?? null;
                }else{
                    $newQuestions[$key]['selected_answer'] = null;
                }
            }
        }

        $allAnswers = collect($validatedChecklist)->pluck('answer_id')->flatten()->filter();

        dd($questions);

        $counts = [
            'total_questions' => $questions->count(),
            'required_questions' => $questions->where('is_required', true)->count(),
            'optional_questions' => $questions->where('is_required', false)->count(),
            'required_items_completed' => 0,
            'items_requiring_maintenance' => 0,
            'damaged_items' => 0,
        ];

        dd('asd');
        $template = EquipmentRentalReadyTemplate::create([
                        'equipment_id' => $orderProduct->equipment_id,
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'inspection_date' => now()->toDateString(),
                        'inspection_time' => now()->toTimeString(),
                        'equipment_hours' => $validated['equipment_hours'] ?? 0,
                        'general_notes' => $validated['general_notes'] ?? '',
                        'status' => $status,
                        'total_questions' => $counts['total_questions'],
                        'required_questions' => $counts['required_questions'],
                        'optional_questions' => $counts['optional_questions'],
                        'required_items_completed' => $counts['required_items_completed'],
                        'items_requiring_maintenance' => $counts['items_requiring_maintenance'],
                        'damaged_items' => $counts['damaged_items'],
                        'created_by' => auth()->id(),
                    ]);



        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.checklist_saved_successfully'),
        ]);
    }
}
