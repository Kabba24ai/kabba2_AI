<?php

namespace App\Http\Requests\Admin\GiftCards;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Suspend, reinstate, cancel and replace all take exactly one thing from the
 * operator: a reason.
 *
 * Authorization is NOT decided here. Each of these actions has its own
 * permission (SUSPEND, CANCEL, REPLACE) and the service asserts the right one
 * at its own boundary. A FormRequest that guessed would be a second, weaker
 * copy of that rule — so this one validates shape and lets the service decide
 * authority.
 */
class GiftCardLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware gates the workspace; the service gates the action.
        return true;
    }

    public function rules(): array
    {
        return [
            // Long enough to be a reason rather than a keystroke. These rows
            // are read during disputes and audits.
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required — this is recorded against the card permanently.',
            'reason.min' => 'Give a reason someone reviewing this later can actually use.',
        ];
    }
}
