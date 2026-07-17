<?php

namespace App\Http\Requests\Admin\WaitList;

use App\Enums\WaitList\WaitListReason;
use App\Enums\WaitList\WaitListStorePreference;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Unified wait list creation: one customer, one category, and one or more
 * selected acceptable EQUIPMENT INVENTORY UNITS from that category —
 * individual assets by Equipment ID, never catalog products. Units from
 * any other category are rejected server-side regardless of what the form
 * submits, and there is no upper limit on how many units may be selected.
 */
class SaveWaitListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
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
            'product_category_id' => ['required', 'exists:product_categories,id'],
            // The selected acceptable units — at least one, no upper limit
            'equipment_ids'       => ['required', 'array', 'min:1'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $categoryId = $this->input('product_category_id');
            $equipmentIds = (array) $this->input('equipment_ids', []);

            if (! $categoryId || $equipmentIds === []) {
                return;
            }

            $inCategory = Equipment::query()
                ->where('product_category_id', $categoryId)
                ->whereIn('id', $equipmentIds)
                ->count();

            if ($inCategory !== count(array_unique($equipmentIds))) {
                $validator->errors()->add(
                    'equipment_ids',
                    'One or more selected equipment units do not belong to the selected category.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'equipment_ids.required'       => 'Select at least one acceptable equipment unit.',
            'equipment_ids.min'            => 'Select at least one acceptable equipment unit.',
            'product_category_id.required' => 'Select an equipment category.',
        ];
    }
}
