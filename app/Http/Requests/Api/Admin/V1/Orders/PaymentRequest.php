<?php

namespace App\Http\Requests\Api\Admin\V1\Orders;

use App\Http\Requests\ApiBaseFormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_unique_id' => 'required|exists:orders,unique_id',

            // card fields only required when payment_type = CreditCard
            'customer_card' => [
                'nullable',
                Rule::requiredIf(fn() => $this->input('payment_type') === 'CreditCard' && !$this->input('card_number')),
                'exists:customer_cards,unique_id',
            ],
            'card_number' => [
                'nullable',
                Rule::requiredIf(fn() => $this->input('payment_type') === 'CreditCard' && !$this->input('customer_card')),
                'string', 'max:19',
            ],
            'mm_yy' => [
                'nullable',
                Rule::requiredIf(fn() => $this->input('payment_type') === 'CreditCard' && !$this->input('customer_card')),
                'string', 'max:19',
            ],
            'cvc' => [
                'nullable',
                Rule::requiredIf(fn() => $this->input('payment_type') === 'CreditCard' && !$this->input('customer_card')),
                'string', 'max:19',
            ],

            // ── new fields ────────────────────────────────────────────────
            'payment_type'       => ['required', 'string', 'in:' . implode(',', array_column(\App\Enums\Customers\PaymentMethod::canonical(), 'value'))],
            // "Other" carries no inherent meaning on its own — require a
            // description of what was actually used, same as the admin flow.
            'payment_note'       => ['nullable', 'required_if:payment_type,Other', 'string', 'max:500'],
            'responsible_person' => ['required', 'exists:users,id'],
            'cheque_number'      => ['nullable', 'required_if:payment_type,Cheque', 'string', 'max:50'],
            // Client-generated, one per distinct payment attempt — lets
            // Store Credit redemption recognize a duplicate network retry
            // of the same request rather than redeeming twice.
            'idempotency_token'  => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        $accepted = implode(', ', array_column(\App\Enums\Customers\PaymentMethod::canonical(), 'value'));

        return [
            'payment_type.required' => "The payment_type field is required. Accepted: {$accepted}.",
            'payment_type.in'       => "Invalid payment_type. Accepted values: {$accepted}.",
            'payment_note.required_if' => 'Describe the payment method used (e.g. Manufacturer Credit, Trade Credit) when payment_type is Other.',
            'responsible_person.required' => 'The responsible_person field is required. Send the user ID (integer).',
            'responsible_person.exists'   => 'The responsible_person ID does not exist in the users table.',
            'cheque_number.required_if'   => 'The cheque_number field is required when payment_type is Cheque.',
            'customer_card.required'      => 'The customer_card field is required when payment_type is CreditCard and no card details are provided.',
            'card_number.required'        => 'The card_number field is required when payment_type is CreditCard and no customer_card is provided.',
            'mm_yy.required'              => 'The mm_yy field is required when payment_type is CreditCard and no customer_card is provided.',
            'cvc.required'                => 'The cvc field is required when payment_type is CreditCard and no customer_card is provided.',
        ];
    }

    /**
     * Get the body parameters for the request documentation.
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            // ── required always ───────────────────────────────────────────
            'order_unique_id' => [
                'description' => 'Unique ID of the order being paid.',
                'example'     => 'ORD-BDTH-AHMX',
                'type'        => 'string',
                'required'    => true,
            ],
            'payment_type' => [
                'description' => 'Payment method. Accepted values: Cash · CreditCard · Cheque · TapToPay · StoreCredit · GiftCard · ZelleVenmo · Other. "Other" requires payment_note describing what was actually used.',
                'example'     => 'Cash',
                'type'        => 'string',
                'enum'        => ['Cash', 'CreditCard', 'Cheque', 'TapToPay', 'StoreCredit', 'GiftCard', 'ZelleVenmo', 'Other'],
                'required'    => true,
            ],
            'responsible_person' => [
                'description' => 'ID of the user responsible for recording this payment (from users table).',
                'example'     => 1,
                'type'        => 'integer',
                'required'    => true,
            ],

            // ── conditionally required ────────────────────────────────────
            'cheque_number' => [
                'description' => 'Cheque number. REQUIRED when payment_type = Cheque.',
                'example'     => 'CHQ-00123',
                'type'        => 'string',
                'required'    => false,
            ],
            'customer_card' => [
                'description' => 'Unique ID of a saved customer card. REQUIRED when payment_type = CreditCard and using a saved card.',
                'example'     => 'CARD-XXXX-XXXX',
                'type'        => 'string',
                'required'    => false,
            ],
            'card_number' => [
                'description' => 'Full card number. REQUIRED when payment_type = CreditCard and no customer_card is provided.',
                'example'     => '4111111111111111',
                'type'        => 'string',
                'required'    => false,
            ],
            'mm_yy' => [
                'description' => 'Card expiry MM/YY. REQUIRED when payment_type = CreditCard and no customer_card is provided.',
                'example'     => '12/25',
                'type'        => 'string',
                'required'    => false,
            ],
            'cvc' => [
                'description' => 'Card CVC. REQUIRED when payment_type = CreditCard and no customer_card is provided.',
                'example'     => '123',
                'type'        => 'string',
                'required'    => false,
            ],

            // ── optional always ───────────────────────────────────────────
            'payment_note' => [
                'description' => 'Note for this payment (max 500 characters). REQUIRED when payment_type = Other.',
                'example'     => 'Manufacturer Credit',
                'type'        => 'string',
                'required'    => false,
            ],
            'idempotency_token' => [
                'description' => 'Client-generated unique token, one per distinct payment attempt (e.g. a UUID). Strongly recommended when payment_type = StoreCredit, so a network retry of the same request is recognized rather than redeeming the customer\'s credit twice.',
                'example'     => '8f14e45f-ceea-4f9b-9e2c-1234567890ab',
                'type'        => 'string',
                'required'    => false,
            ],
        ];
    }
}
