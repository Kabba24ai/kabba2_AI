<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use Illuminate\Foundation\Http\FormRequest;

// Helpers
use App\Helpers\PurifyHelper;

class BulkDeleteRequest extends FormRequest
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
}
