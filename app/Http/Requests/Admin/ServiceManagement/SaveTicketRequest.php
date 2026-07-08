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
            // The rental-order intake form omits type/location/opened date —
            // StoreController applies the safe defaults. When present (edit
            // form, legacy payloads) the values must still be valid.
            'service_type'             => ['sometimes', 'required', Rule::enum(ServiceType::class)],
            'service_location'         => ['sometimes', 'required', Rule::enum(ServiceLocation::class)],
            'priority'                 => ['required', Rule::enum(ServicePriority::class)],
            // Diagnostic-first: statuses and responsibility may be omitted at
            // intake — StoreController applies the safe defaults.
            'repair_status'            => ['nullable', Rule::enum(RepairStatus::class)],
            'financial_responsibility' => ['nullable', Rule::enum(FinancialResponsibility::class)],
            'financial_status'         => ['nullable', Rule::enum(FinancialStatus::class)],
            'equipment_id'             => ['required', 'exists:equipment,id'],
            'opened_at'                => ['sometimes', 'required', 'date'],

            // Rental-order intake path: order is mandatory and equipment must
            // come from that order (enforced in withValidator below)
            'intake'                   => ['nullable', 'boolean'],
            'order_id'                 => [Rule::requiredIf(fn () => $this->boolean('intake')), 'nullable', 'exists:orders,id'],
            'service_store_id'         => ['nullable', 'exists:stores,id'],
            'rental_date'              => ['nullable', 'date'],
            'personnel'                => ['nullable', 'array'],
            'personnel.*'              => ['integer', 'exists:users,id'],
            'team_leader_id'           => ['nullable', 'integer', 'exists:users,id'],

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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Team leader must be one of the assigned personnel
            $leaderId  = $this->input('team_leader_id');
            $personnel = array_map('intval', (array) $this->input('personnel', []));
            if ($leaderId !== null && !in_array((int) $leaderId, $personnel, true)) {
                $validator->errors()->add('team_leader_id', 'The team leader must be one of the assigned personnel.');
            }

            // Rental-order intake: no unrelated equipment in this path
            if (!$this->boolean('intake') || $validator->errors()->hasAny(['order_id', 'equipment_id'])) {
                return;
            }

            $orderEquipmentIds = \Illuminate\Support\Facades\DB::table('order_products')
                ->where('order_id', $this->input('order_id'))
                ->whereNotNull('equipment_id')
                ->pluck('equipment_id')
                ->map(fn ($id) => (int) $id);

            if (!$orderEquipmentIds->contains((int) $this->input('equipment_id'))) {
                $validator->errors()->add('equipment_id', 'Select equipment from the chosen rental order.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'order_id.required'             => 'Select the rental order this service ticket relates to.',
            'blocked_reason.required'       => 'A blocked reason is required when the ticket is waiting on parts or approvals.',
            'expected_action_date.required' => 'An expected action date is required when the ticket is waiting on parts or approvals.',
        ];
    }
}
