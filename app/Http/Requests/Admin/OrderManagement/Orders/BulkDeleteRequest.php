<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Http\Requests\ApiBaseFormRequest;

// Helpers
use App\Helpers\PurifyHelper;

class BulkDeleteRequest extends ApiBaseFormRequest
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

    public function rules()
    {
        return [
            'unique_ids' => 'required|array',
            'unique_ids.*' => 'required|string|distinct',
        ];
    }

    public function messages()
    {
        return [
            'unique_ids.required' => 'Please select at least one order to delete.',
            'unique_ids.array' => 'The unique_ids field must be an array.',
            'unique_ids.*.required' => 'Each selected order must have a unique ID.',
            'unique_ids.*.string' => 'Each selected order ID must be a string.',
            'unique_ids.*.distinct' => 'Duplicate order IDs are not allowed.',
        ];
    }
}
