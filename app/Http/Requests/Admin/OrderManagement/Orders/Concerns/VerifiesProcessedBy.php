<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders\Concerns;

use App\Enums\Orders\ProcessedReason;
use App\Models\Iam\Personnel\User;
use Illuminate\Validation\Rule;

/**
 * Shared "Processed By" verification for refund and void requests.
 * Multiple employees share terminals, so beyond the logged-in session we
 * require the employee who is physically processing the action to pick
 * themselves and confirm their employee code. This is an operational
 * accountability check, not high-security authentication.
 */
trait VerifiesProcessedBy
{
    protected function processedByRules(): array
    {
        return [
            'processed_by'  => ['required', 'integer', 'exists:users,id'],
            'employee_code' => ['required', 'string', 'max:20'],
            'reason'        => ['required', Rule::enum(ProcessedReason::class)],
            'reason_other'  => ['nullable', 'required_if:reason,' . ProcessedReason::Other->value, 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->hasAny(['processed_by', 'employee_code'])) {
                return;
            }

            $employee = User::where('id', $this->input('processed_by'))->active()->first();

            if (!$employee) {
                $v->errors()->add('processed_by', 'The selected employee is not an active employee.');

                return;
            }

            if (!hash_equals((string) $employee->employee_code, trim((string) $this->input('employee_code')))) {
                $v->errors()->add('employee_code', 'The Employee ID does not match the selected employee.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'processed_by.required' => 'Select the employee processing this action.',
            'employee_code.required' => 'Enter the Employee ID to confirm who is processing this action.',
            'reason.required'       => 'Select a reason.',
            'reason_other.required_if' => 'Describe the reason when "Other" is selected.',
        ];
    }
}
