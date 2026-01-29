<?php

namespace App\Http\Requests\Admin\Configurations\NotificationSettings;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;

class SaveManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(
            PurifyHelper::purify($this->all())
        );
    }

    public function rules(): array
    {
        return [
            'type'  => 'required|in:order,emergency',
            'name'  => 'required',
            'phone' => [
                'required'
            ],
        ];
    }
}
