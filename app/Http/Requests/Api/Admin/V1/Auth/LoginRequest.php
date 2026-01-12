<?php

namespace App\Http\Requests\Api\Admin\V1\Auth;

use App\Http\Requests\ApiBaseFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:200|exists:users,email',
            'password' => 'required|max:50',
        ];
    }

    /**
     * Get the body parameters for the request documentation.
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'The email address of the user.',
                'example' => 'rajkc.webdev@gmail.com',
                'type' => 'string',
            ],
            'password' => [
                'description' => 'The password for the user account.',
                'example' => 'Raj#1234',
                'type' => 'string',
            ],
        ];
    }

    public function messages()
    {
        return [
            'email.exists' => 'The email or password you entered is incorrect. Please try again.',
        ];
    }
}
