<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders\Notes;

use App\Http\Requests\ApiBaseFormRequest;

class PostRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'note' => 'required|string|max:1000',
            'user_id' => 'required|exists:users,id',
        ];
    }
}
