<?php

namespace App\Http\Requests\Admin\GiftCards;

use App\Services\GiftCards\GiftCardPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Granting a gift card: no customer payment, merchant-funded value.
 *
 * ── WHY THIS IS A SEPARATE FORM ───────────────────────────────────────────
 *
 * Selling and granting look similar and are not. Taking $500 and issuing
 * $500 is a clerk's job. Issuing $500 that nobody paid for is the creation
 * of spendable value out of nothing, and it is the single most consequential
 * action in this feature.
 *
 * Merging the two into one form with a "was this paid for?" toggle would put
 * that decision one mis-click away from an ordinary sale, and would make the
 * audit trail depend on a checkbox. They stay apart.
 *
 * ── THE REASON IS NOT OPTIONAL ────────────────────────────────────────────
 *
 * A grant with no stated reason is indistinguishable from a mistake when
 * someone reviews it months later. Both the category and the free-text note
 * are required here, and the service refuses an empty reason independently.
 */
class GrantGiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        // GRANT, never SELL. Holding every other gift card permission does
        // not confer this one — see GiftCardPermissions.
        return GiftCardPermissions::canGrant($this->user());
    }

    /**
     * Why a business gives value away. A fixed list rather than free text so
     * the promotional expense can be reported by cause; the free-text note
     * carries the specifics.
     */
    public static function reasonCategories(): array
    {
        return [
            'service_recovery' => 'Service Recovery — making good on a problem',
            'promotion' => 'Promotion / Marketing Campaign',
            'employee_recognition' => 'Employee or Partner Recognition',
            'donation' => 'Donation / Community Sponsorship',
            'contest' => 'Contest or Giveaway Prize',
            'other' => 'Other (explain in the note)',
        ];
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],

            'grant_reason_category' => ['required', Rule::in(array_keys(self::reasonCategories()))],

            // The note is the audit entry a reviewer will actually read. A
            // minimum length is set because "n/a" is not a reason.
            'grant_note' => ['required', 'string', 'min:10', 'max:500'],

            'recipient_customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'recipient_email' => ['nullable', 'email', 'max:190'],
            'sender_name' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:300'],

            'issued_by_store_id' => ['nullable', 'integer', 'exists:stores,id'],

            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'grant_note.required' => 'Explain why this value is being given away. This note is the audit record.',
            'grant_note.min' => 'Give enough detail for someone reviewing this months from now to understand it.',
            'grant_reason_category.required' => 'Choose the reason category for this grant.',
            'amount.max' => 'A single gift card is limited to $10,000.',
        ];
    }
}
