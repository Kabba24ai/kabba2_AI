<?php

namespace App\Http\Requests\Admin\Crm\Customers\CustomerCredit;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class RedeemStoreRequest extends FormRequest
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
            'reason' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'responsible_person' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
