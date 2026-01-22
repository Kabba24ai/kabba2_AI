<?php

namespace App\Http\Requests\Admin\ProductManagement\Options;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:240'],
            'type' => ['required', 'in:Rental,Retail'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:Active,Inactive'],

            // Validate the options array
            'options' => ['required', 'array', 'min:1'],
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

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'type.required' => 'Please select a valid type.',
            'status.required' => 'Status is required.',
            'options.required' => 'At least one option must be provided.',
            'options.*.label.required' => 'Each option must have a label.',
        ];
    }
}
