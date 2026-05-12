<?php

namespace App\Http\Requests\Api\Admin\V1\Clients;

use App\Http\Requests\ApiBaseFormRequest;

class StoreApplicationCodeRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [

            'application_code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[A-Za-z0-9]+$/',
            ],

        ];
    }

    public function messages(): array
    {
        return [

            'application_code.required' =>
                'Application Code is required.',

            'application_code.size' =>
                'Application Code must be exactly 6 characters.',

            'application_code.regex' =>
                'Only letters and numbers are allowed.',

        ];
    }
}