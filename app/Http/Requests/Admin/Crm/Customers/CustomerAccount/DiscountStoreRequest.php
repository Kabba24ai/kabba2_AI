<?php

namespace App\Http\Requests\Admin\Crm\Customers\CustomerAccount;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;
use Illuminate\Validation\Rule;

class DiscountStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->all(), ['content']));
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', Rule::in([
                'Volume Discount',
                'Repeat Customer Discount',
                'Damage Waiver Protection',
                'Misc. Management Discount',
                'Other',
            ])],
            'responsible_person' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
