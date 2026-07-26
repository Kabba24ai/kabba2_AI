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
            'contact_phone'  => [Rule::requiredIf(fn () => $this->input('contact_source') === 'other'), 'nullable', 'string', 'max:50'],

            // Service location: the order's delivery address, or a different one
            // (entered deliberately — a technician is never sent on an assumption).
            'location_source' => ['required', Rule::in(['delivery', 'other'])],
            'loc_street'      => [Rule::requiredIf(fn () => $this->input('location_source') === 'other'), 'nullable', 'string', 'max:255'],
            'loc_city'        => [Rule::requiredIf(fn () => $this->input('location_source') === 'other'), 'nullable', 'string', 'max:255'],
            'loc_state'       => [Rule::requiredIf(fn () => $this->input('location_source') === 'other'), 'nullable', 'string', 'max:255'],
            'loc_zip'         => [Rule::requiredIf(fn () => $this->input('location_source') === 'other'), 'nullable', 'string', 'max:20'],

            // Reported problems — the shared shop symptom library + optional detail.
            'complaints'         => ['nullable', 'array'],
            'complaints.*'       => ['integer', 'exists:service_symptoms,id'],
            'additional_details' => ['nullable', 'string', 'max:5000'],

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
            // A stated problem is required — a library selection or free-text detail.
            if (empty($this->input('complaints')) && trim((string) $this->input('additional_details')) === '') {
                $validator->errors()->add('complaints', 'Select at least one reported problem, or add details.');
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
