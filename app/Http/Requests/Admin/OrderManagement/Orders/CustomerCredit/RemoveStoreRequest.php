<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders\CustomerCredit;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class RemoveStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->all(), ['content']));
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string'],
            'responsible_person' => ['required', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
