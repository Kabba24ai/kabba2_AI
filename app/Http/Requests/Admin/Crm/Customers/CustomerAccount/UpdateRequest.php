<?php

namespace App\Http\Requests\Admin\Crm\Customers\CustomerAccount;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\PurifyHelper;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->all(), ['notes']));
    }

    public function rules(): array
    {
        return [
            'type' => ['required'],
            'amount' => ['required', 'numeric'],
            'payment_type' => ['nullable', 'string'],
            'cheque_number' => ['nullable'],
            'responsible_person' => ['required'],
            'sales_tax' => ['nullable', Rule::in(['add', 'free', 'reverse'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
