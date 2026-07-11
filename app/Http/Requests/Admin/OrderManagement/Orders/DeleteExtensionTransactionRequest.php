<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Enums\Orders\ExtensionDeletionReason;
use App\Http\Requests\Admin\OrderManagement\Orders\Concerns\VerifiesProcessedBy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Administrative disposition for deleting a PAID extension transaction that
 * has no Kabba-recorded refund or void. Reuses the refund/void employee
 * verification (processed_by + employee_code) but with extension-deletion
 * reasons instead of ProcessedReason.
 */
class DeleteExtensionTransactionRequest extends FormRequest
{
    use VerifiesProcessedBy;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = $this->processedByRules();

        $rules['reason'] = ['required', Rule::enum(ExtensionDeletionReason::class)];
        $rules['reason_other'] = [
            'nullable',
            'required_if:reason,' . ExtensionDeletionReason::Other->value,
            'string',
            'max:255',
        ];
        $rules['notes'] = ['nullable', 'string', 'max:1000'];

        return $rules;
    }
}
