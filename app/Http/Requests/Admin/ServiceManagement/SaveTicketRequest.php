<?php

namespace App\Http\Requests\Admin\ServiceManagement;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $blocked = RepairStatus::blocked();

        return [
            'service_type'             => ['required', Rule::enum(ServiceType::class)],
            'service_location'         => ['required', Rule::enum(ServiceLocation::class)],
            'priority'                 => ['required', Rule::enum(ServicePriority::class)],
            // Diagnostic-first: statuses and responsibility may be omitted at
            // intake — StoreController applies the safe defaults.
            'repair_status'            => ['nullable', Rule::enum(RepairStatus::class)],
            'financial_responsibility' => ['nullable', Rule::enum(FinancialResponsibility::class)],
            'financial_status'         => ['nullable', Rule::enum(FinancialStatus::class)],
            'equipment_id'             => ['required', 'exists:equipment,id'],
            'opened_at'                => ['required', 'date'],

            'order_id'                 => ['nullable', 'exists:orders,id'],
            'rental_date'              => ['nullable', 'date'],
            'personnel'                => ['nullable', 'array'],
            'personnel.*'              => ['integer', 'exists:users,id'],

            'customer_complaint'       => ['nullable', 'string', 'max:5000'],
            'technician_diagnosis'     => ['nullable', 'string', 'max:5000'],
            'root_cause'               => ['nullable', 'string', 'max:5000'],
            'repair_summary'           => ['nullable', 'string', 'max:5000'],
            'internal_notes'           => ['nullable', 'string', 'max:5000'],

            // Blocking context is mandatory when entering a blocked status
            'blocked_reason'           => [Rule::requiredIf(fn () => in_array($this->input('repair_status'), $blocked, true)), 'nullable', 'string', 'max:1000'],
            'expected_action_date'     => [Rule::requiredIf(fn () => in_array($this->input('repair_status'), $blocked, true)), 'nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'blocked_reason.required'       => 'A blocked reason is required when the ticket is waiting on parts or approvals.',
            'expected_action_date.required' => 'An expected action date is required when the ticket is waiting on parts or approvals.',
        ];
    }
}
