<?php

namespace App\Http\Requests\Admin\Crm\Customers\CustomerAccount;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;
use Illuminate\Validation\Rule;

class ChargeStoreRequest extends FormRequest
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
            'customer_id' => ['required'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required'],
            'responsible_person' => ['required'],
            'sales_tax' => ['nullable', Rule::in(['add', 'free', 'reverse'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
