<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\ResponsibilityDecision;

use App\Enums\Service\ApprovalType;
use App\Enums\Service\FinancialResponsibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Coerce the (possibly-absent) boolean flags so validated() always carries them. */
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
            'name'        => ['required', 'string', 'max:255'],
            // Explicit, permanent business identifier — lowercase snake_case,
            // unique, immutable after creation. Never derived from the name.
            'key'         => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('service_responsibility_decisions', 'key')],
            'description' => ['nullable', 'string'],
            'color'       => ['nullable', 'string', 'max:255'],
            // Reporting dimension — metadata only, not enforced.
            'financial_reporting_category' => ['nullable', 'string', 'max:255'],
            // ENFORCED mappings — must be a known FinancialResponsibility /
            // ApprovalType value, or null.
            'financial_path' => ['nullable', Rule::in(array_column(FinancialResponsibility::cases(), 'value'))],
            'approval_type'  => ['nullable', Rule::in(array_column(ApprovalType::cases(), 'value'))],
            // Forward-looking flags (stored, not enforced this phase).
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
            'name.required'  => 'A decision name is required.',
            'key.required'   => 'A system key is required.',
            'key.regex'      => 'The system key must be lowercase snake_case (letters, numbers, underscores; starting with a letter).',
            'key.unique'     => 'That system key is already in use.',
        ];
    }
}
