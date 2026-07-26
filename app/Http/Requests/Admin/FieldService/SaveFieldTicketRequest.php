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

            // Reported problems — the shared shop symptom library, free-text
            // "Other" problems for anything not in the library, + optional detail.
            'complaints'         => ['nullable', 'array'],
            'complaints.*'       => ['integer', 'exists:service_symptoms,id'],
            'custom_problems'    => ['nullable', 'array'],
            'custom_problems.*'  => ['string', 'max:255'],
            // Canonical Complaint Details narrative (shared contract with Standard
            // Service). Replaces the former `additional_details` field name.
            'customer_complaint' => ['nullable', 'string', 'max:5000'],

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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // A stated problem is required — a library selection, a free-text
            // "Other" problem, or additional details.
            $customProblems = array_filter(array_map('trim', (array) $this->input('custom_problems', [])), fn ($s) => $s !== '');
            if (empty($this->input('complaints')) && empty($customProblems) && trim((string) $this->input('customer_complaint')) === '') {
                $validator->errors()->add('complaints', 'Select or enter at least one reported problem, or add details.');
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
        ];
    }

    public function attributes(): array
    {
        return [
            'order_id'      => 'related rental order',
            'equipment_id'  => 'equipment',
            'technician_id' => 'assigned technician',
            'truck_id'      => 'service truck',
            'complaints'    => 'reported problems',
        ];
    }
}
