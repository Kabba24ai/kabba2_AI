<?php

namespace App\Http\Requests\Admin\ProductManagement\Options;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Clean the input before validation.
     */
    protected function prepareForValidation(): void
    {
        $input = PurifyHelper::purify($this->all());

        // Filter out options with empty or null labels
        if (isset($input['options']) && is_array($input['options'])) {
            $input['options'] = collect($input['options'])
                ->filter(function ($option) {
                    return isset($option['label']) && trim($option['label']) !== '';
                })
                ->values()
                ->all(); // Re-index the array
        }

        $this->merge($input);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:240', Rule::unique('product_options', 'name')->ignore($this->route('unique_id'), 'unique_id')],
            'type' => ['required', 'in:Rental,Retail'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:Active,Inactive'],

            'options' => ['required', 'array', 'min:1'],
            'options.*.id' => ['nullable', 'integer', 'exists:product_option_items,id'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'options.*.label' => ['required', 'string', 'max:255'],
            'options.*.daily' => ['nullable', 'numeric', 'min:0'],
            'options.*.weekend' => ['nullable', 'numeric', 'min:0'],
            'options.*.weekly' => ['nullable', 'numeric', 'min:0'],
            'options.*.monthly' => ['nullable', 'numeric', 'min:0'],
            'options.*.retail_price' => ['nullable', 'numeric', 'min:0'],
            'options.*.charged' => ['required', 'in:Unlimited,1 Time Max'],
            'options.*.value' => ['required', 'in:Blank,Checked'],
            'options.*.comment' => ['nullable', 'string'],
            'options.*.accept_label' => ['nullable', 'string', 'max:255'],
            'options.*.decline_label' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'The name has already been taken.',
            'options.required' => 'At least one option is required.',
        ];
    }
}
