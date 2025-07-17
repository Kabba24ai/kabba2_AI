<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Http\Requests\ApiBaseFormRequest;

// Helpers
use App\Helpers\PurifyHelper;

class UpdateNoteRequest extends ApiBaseFormRequest
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
            'order_note' => 'nullable|string|max:500',
        ];
    }

    // failedValidation now inherited from ApiBaseFormRequest
}
