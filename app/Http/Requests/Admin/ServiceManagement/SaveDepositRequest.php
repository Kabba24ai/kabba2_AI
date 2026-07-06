<?php

namespace App\Http\Requests\Admin\ServiceManagement;

use Illuminate\Foundation\Http\FormRequest;

class SaveDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parts_deposit_required'          => ['nullable', 'boolean'],
            'parts_deposit_amount'            => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'parts_deposit_paid'              => ['nullable', 'boolean'],
            'parts_deposit_payment_reference' => ['nullable', 'string', 'max:255'],
            'parts_deposit_creditable'        => ['nullable', 'boolean'],
            'parts_deposit_applied_to_final_invoice' => ['nullable', 'boolean'],
        ];
    }
}
