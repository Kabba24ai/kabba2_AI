<?php

namespace App\Http\Requests\Admin\Crm\Customers;

use App\Http\Requests\ApiBaseFormRequest;

// Helpers
use App\Helpers\PurifyHelper;

class BulkDeleteRequest extends ApiBaseFormRequest
{
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
            'unique_ids.required' => 'Please select at least one Customer to delete.',
            'unique_ids.array' => 'The unique_ids field must be an array.',
            'unique_ids.*.required' => 'Each selected Customer must have a unique ID.',
            'unique_ids.*.string' => 'Each selected Customer ID must be a string.',
            'unique_ids.*.distinct' => 'Duplicate Customer IDs are not allowed.',
        ];
    }
}
