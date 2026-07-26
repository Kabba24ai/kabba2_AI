<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\ResponsibilityDecision;

use App\Enums\Service\ApprovalType;
use App\Enums\Service\FinancialResponsibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Editing a decision NEVER changes its stable `key` — the key is immutable once
 * created (downstream tickets snapshot it). Only presentation, mappings, flags,
 * active state, and order are editable here.
 */
class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allows_repair'                   => $this->boolean('allows_repair'),
            'requires_diagnostic_fee'         => $this->boolean('requires_diagnostic_fee'),
            'is_terminal_resolution'          => $this->boolean('is_terminal_resolution'),
            'requires_customer_authorization' => $this->boolean('requires_customer_authorization'),
            'requires_oem_authorization'      => $this->boolean('requires_oem_authorization'),
            'is_active'                       => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            // `key` is intentionally absent — immutable after creation.
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color'       => ['nullable', 'string', 'max:255'],
            'financial_reporting_category' => ['nullable', 'string', 'max:255'],
            'financial_path' => ['nullable', Rule::in(array_column(FinancialResponsibility::cases(), 'value'))],
            'approval_type'  => ['nullable', Rule::in(array_column(ApprovalType::cases(), 'value'))],
            'allows_repair'                   => ['boolean'],
            'requires_diagnostic_fee'         => ['boolean'],
            'is_terminal_resolution'          => ['boolean'],
            'requires_customer_authorization' => ['boolean'],
            'requires_oem_authorization'      => ['boolean'],
            'is_active'                       => ['boolean'],
            'sort_order'                      => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A decision name is required.',
        ];
    }
}
