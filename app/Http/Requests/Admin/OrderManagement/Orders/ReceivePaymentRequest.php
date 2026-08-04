<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

// Helpers

use App\Enums\Customers\PaymentMethod;
use App\Http\Requests\ApiBaseFormRequest;
use Illuminate\Validation\Rule;

class ReceivePaymentRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'payment_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(PaymentMethod::options()))],
            'cheque_number' => ['nullable', 'required_if:payment_type,Cheque', 'string', 'max:50'],
            'card_option' => ['nullable', 'required_if:payment_type,CreditCard', 'in:NewCard,CardOnFile'],
            'responsible_person' => ['required', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'customer_card' => ['nullable', 'required_if:card_option,CardOnFile', 'exists:customer_cards,unique_id'],
            'firstName' => ['nullable', 'required_if:card_option,NewCard', 'string', 'max:50'],
            'lastName' => ['nullable', 'required_if:card_option,NewCard', 'string', 'max:50'],
            'opaqueDataValue' => ['nullable', 'required_if:card_option,NewCard', 'string', 'max:255'],
            'opaqueDataDescriptor' => ['nullable', 'required_if:card_option,NewCard', 'string', 'max:255'],
            // "Other" carries no inherent meaning on its own — require a
            // description of what was actually used so the record stays
            // as accurate as every named method.
            'payment_note' => ['nullable', 'required_if:payment_type,Other', 'max:255'],
            'card_number' => ['nullable','string','max:4'],
            'mm_yy' => ['nullable','string','max:6'],

            // ── Gift Card redemption ──────────────────────────────────────
            //
            // Selecting Gift Card no longer means "record a payment and type
            // the number in the notes". It means redeem a specific card, so
            // the number is required and the ledger is what moves.
            //
            // The AMOUNT is deliberately not bounded here. Its two limits —
            // the card's remaining balance and the order's remaining balance
            // — are checked inside GiftCardService::redeem() under a row
            // lock, because between this request being validated and the
            // redemption running the card may have been spent at another
            // register. A bound asserted here would be a bound asserted
            // against stale data.
            'gift_card_number' => ['nullable', 'required_if:payment_type,GiftCard', 'string', 'max:64'],
            'gift_card_pin' => ['nullable', 'string', 'max:12'],
            'partial_payment' => ['nullable', 'boolean'],
            'payment_amount' => ['nullable', 'numeric', 'min:0.01'],
            // Client-generated, one per modal-open — lets Store Credit
            // redemption recognize a duplicate double-click/network-retry
            // of the same submit rather than redeeming twice.
            'idempotency_token' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_note.required_if' => 'Describe the payment method used (e.g. Manufacturer Credit, Trade Credit).',
            'gift_card_number.required_if' => 'Enter the gift card number to redeem.',
        ];
    }
}
