<?php

namespace App\Http\Requests\Api\Admin\V1\Orders;

use App\Http\Requests\ApiBaseFormRequest;

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
            'customer_card' => ['nullable', 'required_without_all:card_number,mm_yy,cvc', 'exists:customer_cards,unique_id'],
            'card_number' => ['required_without:customer_card', 'string', 'max:19'],
            'mm_yy' => ['required_without:customer_card', 'string', 'max:19'],
            'cvc' => ['required_without:customer_card', 'string', 'max:19'],
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
            'order_unique_id' => [
                'description' => 'The unique identifier of the order to be paid.',
                'example' => 'ORD123456',
                'type' => 'string',
            ],
            'customer_card' => [
                'description' => 'The unique identifier of the saved customer card to be used for payment.',
                'example' => 'CARD123456',
                'type' => 'string',
            ],
            'card_number' => [
                'description' => 'The credit card number for payment.',
                'example' => '4111111111111111',
                'type' => 'string',
            ],
            'mm_yy' => [
                'description' => 'The expiration date of the credit card in MM/YY format.',
                'example' => '12/25',
                'type' => 'string',
            ],
            'cvc' => [
                'description' => 'The CVC code of the credit card.',
                'example' => '123',
                'type' => 'string',
            ],
        ];
    }
}
