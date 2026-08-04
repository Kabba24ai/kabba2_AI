<?php

namespace App\Http\Requests\Admin\GiftCards;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Moving a card's balance by hand.
 *
 * An increase is {@see \App\Services\GiftCards\GiftCardService::grant()} in a
 * different shape — it creates spendable value from nothing — which is why
 * the service requires the ADJUST permission and a reason for both
 * directions. This request validates the shape; the service decides authority
 * and enforces the balance floor.
 */
class AdjustGiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['increase', 'decrease'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required — a hand adjustment with no explanation cannot be audited.',
            'direction.in' => 'Choose whether this adjustment adds or removes value.',
        ];
    }
}
