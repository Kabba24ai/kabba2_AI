<?php

namespace App\Http\Requests\Admin\Tasks\Billing;

use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates the Primary Billing Admin designation.
 *
 * Nullable so the designation can be cleared. When present it must reference
 * an existing user AND that user must be an ACTIVE employee — the same
 * two-layer guard the Order Management VerifiesProcessedBy trait uses
 * (exists:users,id + a User::active() re-check), so an inactive or invalid
 * employee can never be designated.
 */
class PrimaryBillingAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route sits inside the authenticated admin group; a signed-in admin
        // may manage this designation.
        return true;
    }

    public function rules(): array
    {
        return [
            'primary_billing_admin_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $id = $this->input('primary_billing_admin_id');

            if ($id && ! User::where('id', $id)->active()->exists()) {
                $validator->errors()->add(
                    'primary_billing_admin_id',
                    'The selected employee is not an active employee.'
                );
            }
        });
    }
}
