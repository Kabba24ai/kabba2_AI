<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Equipment;

// Helpers
use App\Http\Requests\ApiBaseFormRequest;

class AssignChecklistMasterRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'checklist_master_unique_id' => ['required', 'exists:checklist_masters,unique_id'],
            'equipment_unique_id' => ['required', 'exists:equipment,unique_id'],
        ];
    }
}
