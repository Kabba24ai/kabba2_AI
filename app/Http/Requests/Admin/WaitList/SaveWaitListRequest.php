<?php

namespace App\Http\Requests\Admin\WaitList;

use App\Enums\WaitList\WaitListReason;
use App\Enums\WaitList\WaitListRequestType;
use App\Enums\WaitList\WaitListStorePreference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveWaitListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // The optional Choice #2/#3 selects submit blank entries in the
        // equipment_ids array; drop them so only real picks are validated.
        if (is_array($this->input('equipment_ids'))) {
            $ids = array_values(array_filter(
                $this->input('equipment_ids'),
                fn ($id) => $id !== null && $id !== ''
            ));

            $this->merge(['equipment_ids' => $ids !== [] ? $ids : null]);
        }
    }

    public function rules(): array
    {
        return [
            // Structured system data only — no free-form customer/equipment entry
            'customer_id'         => ['required', 'exists:customers,id'],
            'request_type'        => ['required', Rule::enum(WaitListRequestType::class)],
            'product_category_id' => [
                Rule::requiredIf(fn () => $this->input('request_type') === WaitListRequestType::Category->value),
                'nullable', 'exists:product_categories,id',
            ],
            // One record = one need; up to three candidate units, no quantities
            'equipment_ids'       => [
                Rule::requiredIf(fn () => $this->input('request_type') === WaitListRequestType::SpecificEquipment->value),
                'nullable', 'array', 'min:1', 'max:3',
            ],
            'equipment_ids.*'     => ['integer', 'distinct', 'exists:equipment,id'],
            'store_preference'    => ['required', Rule::enum(WaitListStorePreference::class)],
            'store_id'            => [
                Rule::requiredIf(fn () => in_array($this->input('store_preference'), [
                    WaitListStorePreference::SpecificStore->value,
                    WaitListStorePreference::PreferredTransferAllowed->value,
                ], true)),
                'nullable', 'exists:stores,id',
            ],
            // Pre-canned reason codes only — extra explanation goes in Internal Notes
            'reason'              => ['required', Rule::enum(WaitListReason::class)],
            'internal_notes'      => ['nullable', 'string', 'max:5000'],
            // Manual queue positions only: blank, #1, #2, or #3
            'priority_override'   => ['nullable', 'integer', Rule::in([1, 2, 3])],
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_ids.max'      => 'A wait list record may reference at most three equipment units.',
            'equipment_ids.required' => 'Select at least one equipment unit for a specific-equipment wait list.',
            'product_category_id.required' => 'Select a category for a category wait list.',
        ];
    }
}
