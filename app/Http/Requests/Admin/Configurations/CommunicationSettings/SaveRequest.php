<?php

namespace App\Http\Requests\Admin\Configurations\CommunicationSettings;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
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
        $this->merge(PurifyHelper::purify($this->all()));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'sms_gateway' => 'nullable|string',
            'twilio_sid' => 'nullable|string',
            'twilio_auth_token' => 'nullable|string',
            'twilio_from_number' => 'nullable|string',
            'twilio_messaging_service_sid' => 'nullable|string',
            'sms_test_mode' => 'nullable|boolean',
        ];
    }

}
