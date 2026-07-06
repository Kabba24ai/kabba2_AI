<?php

namespace App\Http\Requests\Admin\Documents;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class SaveDocumentTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Sanitize editable text — no script injection through document blocks. */
    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->all()));
    }

    public function rules(): array
    {
        return [
            'price_list_title'         => 'required|string|max:120',
            'price_list_value_message' => 'nullable|string|max:1000',
            'price_list_disclaimer'    => 'nullable|string|max:5000',
        ];
    }
}
