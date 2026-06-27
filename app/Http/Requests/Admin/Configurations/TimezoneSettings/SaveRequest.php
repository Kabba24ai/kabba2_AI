<?php

namespace App\Http\Requests\Admin\Configurations\TimezoneSettings;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->all()));
    }

    public function rules(): array
    {
        return [
            'twilio_timezone' => 'required|string|max:100',
        ];
    }
}
