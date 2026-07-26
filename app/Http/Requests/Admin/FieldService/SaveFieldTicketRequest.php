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
            // 1. Dispatch information — the Order is the canonical source;
            // Customer / Equipment / Address / Contact derive from it.
            'order_id'      => ['required', 'integer', 'exists:orders,id'],
            'equipment_id'  => ['required', 'integer', 'exists:equipment,id'],
            'serial_number' => ['nullable', 'string', 'max:255'],

            // Contact: use the order's contact, or intentionally name someone else.
            'contact_source' => ['required', Rule::in(['order', 'other'])],
            'contact_name'   => [Rule::requiredIf(fn () => $this->input('contact_source') === 'other'), 'nullable', 'string', 'max:255'],
            // Same formatted-phone contract the rest of Kaaba enforces
            // (client-side .masked-phone → (555) 555-5555). Server-authoritative.
            'contact_phone'  => [Rule::requiredIf(fn () => $this->input('contact_source') === 'other'), 'nullable', 'string', 'regex:/^\(\d{3}\) \d{3}-\d{4}$/'],

            // Service location: the order's delivery address, or a different one
            // (entered deliberately — a technician is never sent on an assumption).
            'location_source' => ['required', Rule::in(['delivery', 'other'])],
            'loc_street'      => [Rule::requiredIf(fn () => $this->input('location_source') === 'other'), 'nullable', 'string', 'max:255'],
            'loc_city'        => [Rule::requiredIf(fn () => $this->input('location_source') === 'other'), 'nullable', 'string', 'max:255'],
            // The shared U.S. state list (App\Models\Locations\State) — submitted
            // as its canonical two-letter abbreviation, validated against the list.
            'loc_state'       => [Rule::requiredIf(fn () => $this->input('location_source') === 'other'), 'nullable', 'string', Rule::exists('states', 'abbreviation')],
            'loc_zip'         => [Rule::requiredIf(fn () => $this->input('location_source') === 'other'), 'nullable', 'string', 'max:20'],

            // Reported problems — structured selections from the shared Problem
            // Library (canonical complaint intake, identical to Standard Service).
            'complaints'         => ['nullable', 'array'],
            'complaints.*'       => ['integer', 'exists:service_symptoms,id'],
            // Canonical Complaint Details narrative — shared customer_complaint.
            'customer_complaint' => ['nullable', 'string', 'max:5000'],

            // Complaint Evidence — same uploader + rules as Standard Service.
            'evidence'   => ['nullable', 'array'],
            'evidence.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,gif,webp,heic,mp4,mov,avi,webm'],

            // 3. Dispatch assessment
            'priority'       => ['required', Rule::enum(ServicePriority::class)],
            'safety_concern' => ['required', Rule::enum(FieldSafetyConcern::class)],
            'machine_status' => ['required', Rule::enum(FieldMachineStatus::class)],
            'machine_stuck'  => ['required', Rule::enum(FieldYesNoUnknown::class)],
            'recovery_risk'  => ['required', Rule::enum(FieldRecoveryRisk::class)],
            'site_access'    => ['required', Rule::enum(FieldSiteAccess::class)],
            'site_notes'     => ['nullable', 'string', 'max:5000'],

            // 3. Assigned Personnel — the canonical multi-crew + one-lead contract,
            // identical to the Standard Service intake (shared component). The
            // mission's single lead-technician column is derived from these in the
            // controller. Optional at creation.
            'personnel'      => ['nullable', 'array'],
            'personnel.*'    => ['integer', 'exists:users,id'],
            'team_leader_id' => ['nullable', 'integer', 'exists:users,id'],

            // 4. Dispatch logistics (optional at creation)
            'truck_id'               => ['nullable', 'integer', 'exists:dispatch_ai_trucks,id'],
            'estimated_departure_at' => ['nullable', 'date'],
            // Expected Arrival is system-calculated + read-only — it is NEVER
            // accepted from the client; the server computes and persists it.

            // Departure Location — an EXPLICIT decision with no default. When a
            // store is chosen it must be an active store; when "Other" is chosen
            // the structured origin address is required (reuses the shared state
            // list, same as the service-location address block).
            'departure_location_type' => ['nullable', Rule::in(['store', 'other'])],
            'departure_store_id'      => [
                Rule::requiredIf(fn () => $this->input('departure_location_type') === 'store'),
                'nullable', 'integer',
                Rule::exists('stores', 'id')->where('status', 'Active'),
            ],
            'departure_street' => [Rule::requiredIf(fn () => $this->input('departure_location_type') === 'other'), 'nullable', 'string', 'max:255'],
            'departure_line2'  => ['nullable', 'string', 'max:255'],
            'departure_city'   => [Rule::requiredIf(fn () => $this->input('departure_location_type') === 'other'), 'nullable', 'string', 'max:255'],
            'departure_state'  => [Rule::requiredIf(fn () => $this->input('departure_location_type') === 'other'), 'nullable', 'string', Rule::exists('states', 'abbreviation')],
            'departure_zip'    => [Rule::requiredIf(fn () => $this->input('departure_location_type') === 'other'), 'nullable', 'string', 'max:20'],

            'suggested_tools'        => ['nullable', 'string', 'max:5000'],
            'suggested_parts'        => ['nullable', 'string', 'max:5000'],
            'special_instructions'   => ['nullable', 'string', 'max:5000'],

            // 5. Initial operational expectation (estimate only)
            'operational_expectation' => ['required', Rule::enum(FieldOperationalExpectation::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Team leader must be one of the assigned personnel (same canonical
            // rule the Standard Service intake enforces).
            $leaderId  = $this->input('team_leader_id');
            $personnel = array_map('intval', (array) $this->input('personnel', []));
            if ($leaderId !== null && !in_array((int) $leaderId, $personnel, true)) {
                $validator->errors()->add('team_leader_id', 'The team leader must be one of the assigned personnel.');
            }

            // A stated problem is required — a structured complaint selection or
            // Complaint Details.
            if (empty($this->input('complaints')) && trim((string) $this->input('customer_complaint')) === '') {
                $validator->errors()->add('complaints', 'Select at least one reported problem, or add complaint details.');
            }

            // Server-authoritative applicability: every selected library
            // complaint must be applicable to the chosen equipment's resolved
            // symptom profile, or belong to the additive Field Conditions
            // category — never trust the client-side filter alone.
            $complaints = array_map('intval', (array) $this->input('complaints', []));
            if ($complaints && $this->input('equipment_id')) {
                $applicable = \App\Services\ServiceManagement\ServiceProblemLibrary::applicableSymptomIdsForEquipment(
                    (int) $this->input('equipment_id')
                )->all();
                foreach ($complaints as $id) {
                    if (!in_array($id, $applicable, true)) {
                        $validator->errors()->add('complaints', 'A selected problem is not applicable to the chosen equipment.');
                        break;
                    }
                }
            }

            // Deriving from the order requires the order to actually carry it —
            // otherwise the dispatcher must enter it deliberately.
            $order = $this->input('order_id')
                ? \App\Models\Orders\Order::with('shippingAddress')->find($this->input('order_id'))
                : null;

            if ($this->input('location_source') === 'delivery' && !($order?->shippingAddress?->full_address)) {
                $validator->errors()->add('location_source', 'This order has no delivery address on file — enter a different address.');
            }

            if ($this->input('contact_source') === 'order') {
                $contactName = $order?->shippingAddress?->full_name ?: $order?->customer_name;
                if (!$contactName) {
                    $validator->errors()->add('contact_source', 'This order has no contact on file — enter someone else.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            // The two explicit dispatch decisions must be made deliberately —
            // there is no default, so a missing choice is a hard failure.
            'contact_source.required'  => 'Choose who the technician should ask for — Order Contact or Someone Else.',
            'location_source.required' => 'Choose the service location — Delivery Address or Different Address.',
            'contact_name.required'    => 'Enter the contact name for the person the technician should ask for.',
            'contact_phone.required'   => 'Enter the contact phone number for the person the technician should ask for.',
            'contact_phone.regex'      => 'Contact phone must be in (555) 555-5555 format.',
            'loc_street.required'      => 'Enter the street for the different service address.',
            'loc_city.required'        => 'Enter the city for the different service address.',
            'loc_state.required'       => 'Select the state for the different service address.',
            'loc_state.exists'         => 'Select a valid U.S. state.',
            'loc_zip.required'         => 'Enter the ZIP for the different service address.',
            // Departure location — explicit choice, no default.
            'departure_store_id.required' => 'Select the departure store.',
            'departure_store_id.exists'   => 'Select an active store as the departure location.',
            'departure_street.required'   => 'Enter the street for the Other departure address.',
            'departure_city.required'     => 'Enter the city for the Other departure address.',
            'departure_state.required'    => 'Select the state for the Other departure address.',
            'departure_state.exists'      => 'Select a valid U.S. state for the departure address.',
            'departure_zip.required'      => 'Enter the ZIP for the Other departure address.',
        ];
    }

    public function attributes(): array
    {
        return [
            'order_id'      => 'related rental order',
            'equipment_id'  => 'equipment',
            'truck_id'      => 'service truck',
            'complaints'    => 'reported problems',
        ];
    }
}
