<?php

namespace App\Http\Requests\Admin\WaitList;

use App\Enums\WaitList\WaitListReason;
use App\Enums\WaitList\WaitListStorePreference;
use App\Models\ProductManagement\ProductCategoryChild;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Unified wait list creation: one customer, one category, and one or more
 * selected acceptable products FROM that category. Products from any other
 * category are rejected server-side regardless of what the form submits.
 */
class SaveWaitListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('product_ids'))) {
            $ids = array_values(array_filter(
                $this->input('product_ids'),
                fn ($id) => $id !== null && $id !== ''
            ));

            $this->merge(['product_ids' => $ids !== [] ? $ids : null]);
        }
    }

    public function rules(): array
    {
        return [
            // Structured system data only — no free-form customer/equipment entry
            'customer_id'         => ['required', 'exists:customers,id'],
            'product_category_id' => ['required', 'exists:product_categories,id'],
            // The selected acceptable products — at least one, no upper limit
            'product_ids'         => ['required', 'array', 'min:1'],
            'product_ids.*'       => ['integer', 'distinct', 'exists:products,id'],
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
            $productIds = (array) $this->input('product_ids', []);

            if (! $categoryId || $productIds === []) {
                return;
            }

            $inCategory = ProductCategoryChild::query()
                ->where('product_category_id', $categoryId)
                ->whereIn('product_id', $productIds)
                ->distinct()
                ->pluck('product_id')
                ->all();

            if (count($inCategory) !== count(array_unique($productIds))) {
                $validator->errors()->add(
                    'product_ids',
                    'One or more selected products do not belong to the selected category.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'product_ids.required'          => 'Select at least one acceptable equipment product.',
            'product_ids.min'               => 'Select at least one acceptable equipment product.',
            'product_category_id.required'  => 'Select an equipment category.',
        ];
    }
}
