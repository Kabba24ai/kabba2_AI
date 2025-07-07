<?php

namespace App\Http\Requests\Front\Auth\Register;

use App\Helpers\PurifyHelper;
use App\Rules\Email\EmailShouldNotContainSelectedSpecialCharactersRule;
use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
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
        $this->merge(PurifyHelper::purify($this->all(),[]));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:240',
                'unique:customers,email',
                new EmailShouldNotContainSelectedSpecialCharactersRule(),
            ],
            'password' => ['required', 'min:6', 'max:100', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
