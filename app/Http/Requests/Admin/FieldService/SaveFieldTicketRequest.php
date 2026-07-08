<?php

namespace App\Http\Requests\Admin\FieldService;

use App\Enums\FieldService\FieldMachineStatus;
use App\Enums\FieldService\FieldOperationalExpectation;
use App\Enums\FieldService\FieldRecoveryRisk;
use App\Enums\FieldService\FieldSafetyConcern;
use App\Enums\FieldService\FieldSiteAccess;
use App\Enums\FieldService\FieldYesNoUnknown;
use App\Enums\Service\ServicePriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFieldTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 1. Source / incident information
            'order_id'             => ['nullable', 'integer', 'exists:orders,id'],
            'equipment_id'         => ['nullable', 'integer', 'exists:equipment,id'],
            'serial_number'        => ['nullable', 'string', 'max:255'],
            'job_site_address'     => ['required', 'string', 'max:2000'],
            'contact_name'         => ['nullable', 'string', 'max:255'],
            'contact_phone'        => ['nullable', 'string', 'max:50'],
            'reported_at'          => ['required', 'date'],
            'problem_summary'      => ['required', 'string', 'max:5000'],
            'diagnostic_summary'   => ['nullable', 'string', 'max:10000'],
            'ai_session_reference' => ['nullable', 'string', 'max:255'],

            // 2. Media review checklist
            'photos_received'           => ['nullable', 'boolean'],
            'video_received'            => ['nullable', 'boolean'],
            'media_reviewed'            => ['nullable', 'boolean'],
            'additional_media_required' => ['nullable', 'boolean'],
            'media_bypassed'            => ['nullable', 'boolean'],

            // 3. Dispatch assessment
            'priority'       => ['required', Rule::enum(ServicePriority::class)],
            'safety_concern' => ['required', Rule::enum(FieldSafetyConcern::class)],
            'machine_status' => ['required', Rule::enum(FieldMachineStatus::class)],
            'machine_stuck'  => ['required', Rule::enum(FieldYesNoUnknown::class)],
            'recovery_risk'  => ['required', Rule::enum(FieldRecoveryRisk::class)],
            'site_access'    => ['required', Rule::enum(FieldSiteAccess::class)],
            'site_notes'     => ['nullable', 'string', 'max:5000'],

            // 4. Dispatch assignment (optional at creation)
            'technician_id'          => ['nullable', 'integer', 'exists:users,id'],
            'truck_id'               => ['nullable', 'integer', 'exists:dispatch_ai_trucks,id'],
            'estimated_departure_at' => ['nullable', 'date'],
            'estimated_arrival_at'   => ['nullable', 'date', 'after_or_equal:estimated_departure_at'],
            'suggested_tools'        => ['nullable', 'string', 'max:5000'],
            'suggested_parts'        => ['nullable', 'string', 'max:5000'],
            'special_instructions'   => ['nullable', 'string', 'max:5000'],

            // 5. Initial operational expectation (estimate only)
            'operational_expectation' => ['required', Rule::enum(FieldOperationalExpectation::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'order_id'      => 'related rental order',
            'equipment_id'  => 'equipment',
            'technician_id' => 'assigned technician',
            'truck_id'      => 'service truck',
            'reported_at'   => 'date/time reported',
        ];
    }
}
