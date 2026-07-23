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

    /** A Standard Equipment Ticket: fleet unit, no customer/order. */
    private function isStandard(): bool
    {
        return $this->input('ticket_source') === 'standard';
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

            // Intake source: 'customer' (order-related, the pre-existing path)
            // or 'standard' (a fleet unit on our lot, no customer/order). Absent
            // on the edit form and legacy payloads — those keep the customer
            // rules, so this field only ADDS the Standard path's constraints.
            'ticket_source'            => ['nullable', Rule::in(['customer', 'standard'])],

            // Standard path: equipment is chosen within a category, and the
            // category is required. Membership is verified in withValidator.
            'equipment_category_id'    => [Rule::requiredIf(fn () => $this->isStandard()), 'nullable', 'exists:product_categories,id'],

            // Rental-order (customer) intake path: order is mandatory and
            // equipment must come from that order (enforced in withValidator).
            // The Standard path never requires — or allows — an order.
            'intake'                   => ['nullable', 'boolean'],
            'order_id'                 => [Rule::requiredIf(fn () => !$this->isStandard() && $this->boolean('intake')), 'nullable', 'exists:orders,id'],
            // Service Store is required for a Standard ticket (there is no order
            // to infer a location from); optional otherwise, as before.
            'service_store_id'         => [Rule::requiredIf(fn () => $this->isStandard()), 'nullable', 'exists:stores,id'],
            'rental_date'              => ['nullable', 'date'],

            // Equipment ID Override: the unit actually being repaired when the
            // order carries the wrong one. Any equipment unit is allowed here —
            // the order-scoped rule below applies to equipment_id only.
            'equipment_override_id'     => ['nullable', 'exists:equipment,id'],
            'equipment_override_reason' => ['nullable', 'string', 'max:255'],

            // Structured complaints: each selection becomes its own
            // ServiceTicketComplaint record. Evidence rides the standard
            // ticket-media pipeline (same limits as SaveMediaRequest).
            'complaints'   => ['nullable', 'array'],
            'complaints.*' => ['integer', 'exists:service_symptoms,id'],
            'evidence'     => ['nullable', 'array'],
            'evidence.*'   => [
                'file', 'max:51200', // 50 MB
                'mimes:jpg,jpeg,png,gif,webp,heic,mp4,mov,avi,webm',
            ],
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

            // Standard Equipment path — enforce the isolation rules on the
            // server (never trust hidden browser fields): the unit must belong
            // to the chosen category, and no order/customer/override may ride
            // along. This path is mutually exclusive with the order checks.
            if ($this->isStandard()) {
                $categoryId = (int) $this->input('equipment_category_id');
                $equipmentId = (int) $this->input('equipment_id');

                if ($categoryId && $equipmentId && !$validator->errors()->hasAny(['equipment_id', 'equipment_category_id'])) {
                    $belongs = \Illuminate\Support\Facades\DB::table('equipment')
                        ->where('id', $equipmentId)
                        ->where('product_category_id', $categoryId)
                        ->exists();
                    if (!$belongs) {
                        $validator->errors()->add('equipment_id', 'Select equipment that belongs to the chosen category.');
                    }
                }

                // Reject a manipulated payload that smuggles order-related data
                // into a Standard ticket.
                if ($this->filled('order_id')) {
                    $validator->errors()->add('order_id', 'A standard equipment ticket cannot be linked to a rental order.');
                }
                if ($this->filled('equipment_override_id')) {
                    $validator->errors()->add('equipment_override_id', 'A standard equipment ticket cannot use an equipment override.');
                }

                return;
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
            'order_id.required'               => 'Select the rental order this service ticket relates to.',
            'equipment_category_id.required'  => 'Select the equipment category first.',
            'service_store_id.required'       => 'Select the service store for this standard equipment ticket.',
            'blocked_reason.required'         => 'A blocked reason is required when the ticket is waiting on parts or approvals.',
            'expected_action_date.required'   => 'An expected action date is required when the ticket is waiting on parts or approvals.',
        ];
    }
}
