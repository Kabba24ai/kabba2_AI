<?php

namespace App\Http\Requests\Admin\GiftCards;

use App\Enums\Orders\OrderPaymentMethod;
use App\Services\GiftCards\GiftCardPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Selling a gift card: real money in, stored value out.
 *
 * ── THE FUNDING METHOD IS THE POINT ───────────────────────────────────────
 *
 * This form records how the customer ACTUALLY paid — cash, cheque, card,
 * Zelle. `GiftCard` is rejected outright: a card that could fund itself
 * would create value from nothing. The service refuses it too, and so does
 * the database; this is the first of the three, not the only one.
 *
 * ── ADMINISTRATIVE FUNDING ONLY, FOR NOW ──────────────────────────────────
 *
 * The methods offered here are ones an employee can safely RECORD after the
 * money has been taken. There is no gateway call in this workflow, so a card
 * sale is currently a back-office entry, not an online checkout. Selecting
 * "Credit / Debit Card" therefore means "a card payment was taken and this
 * is its reference", not "charge this card now".
 */
class PurchaseGiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        // `can()` cannot refuse anyone here — AppServiceProvider registers
        // Gate::before(fn () => true) application-wide. hasPermissionTo() is
        // the only check that means anything. See GiftCardPermissions.
        return GiftCardPermissions::canSell($this->user());
    }

    /**
     * Funding methods an administrator may record without a gateway
     * workflow. Deliberately not OrderPaymentMethod::canonical(): that list
     * includes GiftCard (which can never fund a card) and Tap to Pay (which
     * implies a terminal interaction this screen does not perform).
     */
    public static function fundingMethods(): array
    {
        return [
            OrderPaymentMethod::Cash,
            OrderPaymentMethod::Card,
            OrderPaymentMethod::Cheque,
            OrderPaymentMethod::ZelleVenmo,
            OrderPaymentMethod::Other,
        ];
    }

    public function rules(): array
    {
        $allowed = array_map(fn (OrderPaymentMethod $m) => $m->value, self::fundingMethods());

        return [
            // A gift card is a financial instrument; its value is bounded so
            // a mistyped amount cannot quietly become a five-figure liability.
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],

            'funding_method' => ['required', Rule::in($allowed)],
            'funding_transaction_id' => ['nullable', 'string', 'max:100'],
            'funded_at' => ['nullable', 'date'],

            'purchaser_customer_id' => ['nullable', 'integer', 'exists:customers,id'],

            'recipient_name' => ['nullable', 'string', 'max:120'],
            'recipient_email' => ['nullable', 'email', 'max:190'],
            'sender_name' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:300'],

            'issued_by_store_id' => ['nullable', 'integer', 'exists:stores,id'],

            // Optional, and hashed by the model — never stored as typed.
            'pin' => ['nullable', 'digits_between:4,6'],

            // Minted by the form, reused across retries of that one
            // submission, so a double-click issues one card and not two.
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.max' => 'A single gift card is limited to $10,000. Issue more than one card for a larger amount.',
            'funding_method.in' => 'Choose how the customer actually paid. A gift card cannot fund another gift card.',
        ];
    }
}
