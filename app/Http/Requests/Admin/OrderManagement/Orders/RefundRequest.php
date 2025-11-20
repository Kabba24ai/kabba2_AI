<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

// Helpers
use App\Http\Requests\ApiBaseFormRequest;

class RefundRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
