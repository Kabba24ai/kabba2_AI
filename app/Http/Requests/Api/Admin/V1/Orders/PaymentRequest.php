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
            'payment_type'       => ['required', 'string', 'in:Cash,CreditCard,Cheque,BankTransfer,Other'],
            'payment_note'       => ['nullable', 'string', 'max:500'],
            'responsible_person' => ['required', 'exists:users,id'],
            'cheque_number'      => ['nullable', 'required_if:payment_type,Cheque', 'string', 'max:50'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'payment_type.required' => 'The payment_type field is required. Accepted: Cash, CreditCard, Cheque, BankTransfer, Other.',
            'payment_type.in'       => 'Invalid payment_type. Accepted values: Cash, CreditCard, Cheque, BankTransfer, Other.',
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
                'description' => 'Payment method. Accepted values: Cash · CreditCard · Cheque · BankTransfer · Other',
                'example'     => 'Cash',
                'type'        => 'string',
                'enum'        => ['Cash', 'CreditCard', 'Cheque', 'BankTransfer', 'Other'],
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
                'description' => 'Optional note for this payment (max 500 characters).',
                'example'     => 'Paid at front desk.',
                'type'        => 'string',
                'required'    => false,
            ],
        ];
    }
}
