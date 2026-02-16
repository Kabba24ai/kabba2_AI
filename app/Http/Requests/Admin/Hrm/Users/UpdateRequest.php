<?php
namespace App\Http\Requests\Admin\Hrm\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required'],
            'middle_name' => ['nullable'],
            'last_name' => ['required'],
            'email' => ['required', 'email'],
            'mobile_phone' => ['nullable'],
            'phone_number' => ['nullable'],
            'password' => 'nullable|confirmed',
            'street' => ['nullable'],
            'city' => ['nullable'],
            'state' => ['nullable'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'payType' => ['required', Rule::in(['Hourly', 'Salary'])],
            'clockCode' => ['nullable'],
            'limit_start' => ['nullable', 'boolean'],
            'limit_end' => ['nullable', 'boolean'],

            'shift_start_time' => ['nullable'],
            'shift_end_time'   => ['nullable'],
            'social_security'   => ['nullable'],
            
            // Emergency Contact 1
            'emergency_first_name' => ['nullable'],
            'emergency_middle_name' => ['nullable'],
            'emergency_last_name' => ['nullable'],
            'emergency_email' => ['nullable', 'email'],
            'emergency_mobile_phone' => ['nullable', 'string', 'max:20'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'emergency_address' => ['nullable'],
            'emergency_city' => ['nullable'],
            'emergency_state' => ['nullable'],
            'emergency_zip' => ['nullable', 'string', 'max:20'],
            'emergency_country' => ['nullable'],
            // Emergency Contact 2
            'emergency2_first_name' => ['nullable'],
            'emergency2_middle_name' => ['nullable'],
            'emergency2_last_name' => ['nullable'],
            'emergency2_email' => ['nullable', 'email', 'max:255'],
            'emergency2_mobile_phone' => ['nullable', 'string', 'max:20'],
            'emergency2_phone_number' => ['nullable', 'string', 'max:20'],
            'emergency2_street_address' => ['nullable'],
            'emergency2_city' => ['nullable'],
            'emergency2_state' => ['nullable'],
            'emergency2_zip' => ['nullable'],
            'emergency2_country' => ['nullable'],
            'roles' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
