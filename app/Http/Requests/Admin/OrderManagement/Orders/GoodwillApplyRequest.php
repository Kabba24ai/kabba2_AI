<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Enums\Orders\GoodwillReasonCode;
use App\Http\Requests\ApiBaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Applying a Goodwill Adjustment.
 *
 * Validation here is SHAPE ONLY. Every financial and authority rule —
 * permission, one-active-adjustment, stale state, reconstructable basis,
 * downstream artifacts — is enforced by GoodwillAdjustmentService inside its
 * transaction under the order row lock. A form request cannot hold a lock, so
 * anything it "checked" would be a suggestion by the time the write ran.
 *
 * `expected_accepted_cents` is the caller's belief about cumulative settled
 * payments, sent so the service can detect that the order moved between the
 * preview and the confirmation. It is never trusted as an amount — the service
 * re-reads the real figure under the lock and refuses on disagreement.
 */
class GoodwillApplyRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'reason_code' => ['required', 'string', Rule::in(array_keys(GoodwillReasonCode::options()))],

            // Enforced again server-side by the service; duplicated here only
            // so the operator gets a field-level message instead of a modal
            // error after a round trip.
            'reason_note' => ['nullable', 'string', 'max:1000', 'required_if:reason_code,'.GoodwillReasonCode::Other->value],

            // Integer cents, matching the domain boundary. Sent by the modal
            // from the same preview the operator approved.
            'expected_accepted_cents' => ['required', 'integer', 'min:1'],

            'approved_by' => ['required', 'exists:users,id'],

            // One per modal-open, reused across retries of that same submit.
            'idempotency_token' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason_note.required_if' => 'A note is required when the reason is "Other".',
            'approved_by.required'    => 'A manager must authorise this adjustment.',
            'expected_accepted_cents.required' => 'Reload the order — the payment total could not be confirmed.',
        ];
    }
}
