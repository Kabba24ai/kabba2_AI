<?php

namespace App\Http\Requests\Front\Customer\Profile;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = PurifyHelper::purify($this->all(), []);

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'number'     => ['nullable', 'string', 'max:20'],
            'dob'        => ['nullable'],
          
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
