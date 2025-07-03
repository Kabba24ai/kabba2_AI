<?php

namespace App\Http\Requests\Admin\Auth\Login;

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
            'email' => [
                'email',
                'required',
                'max:240',
                new EmailShouldNotContainSelectedSpecialCharactersRule()
            ],
            'password' => [
                'required',
                'min:6',
                'max:100',
            ],
        ];
    }
}
