<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders\Extension;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description'        => ['required', 'string', 'max:255'],
            'base_amount'        => ['required', 'numeric', 'min:0.01'],
            'add_tax'            => ['required', 'boolean'],
            'responsible_person' => ['required', 'exists:users,id'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ];
    }
}
