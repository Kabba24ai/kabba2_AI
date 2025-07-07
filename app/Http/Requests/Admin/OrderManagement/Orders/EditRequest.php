<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class EditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify([$this->all()],[]));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'unique_id' => ['required', 'max:50', 'exists:orders,unique_id'],
        ];
    }
}
