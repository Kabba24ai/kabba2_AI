<?php
namespace App\Http\Requests\Admin\Roles;

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
            'street' => ['nullable'],
            'city' => ['nullable'],
            'state' => ['nullable'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'payType' => ['required', Rule::in(['Hourly', 'Salary'])],
            'clockCode' => ['nullable', 'string', 'max:100'],
            'limit_start' => ['nullable', 'boolean'],
            'limit_end' => ['nullable', 'boolean'],
            // Emergency Contact 1
            'emergency_first_name' => ['nullable', 'string', 'max:100'],
            'emergency_middle_name' => ['nullable', 'string', 'max:100'],
            'emergency_last_name' => ['nullable', 'string', 'max:100'],
            'emergency_email' => ['nullable', 'email', 'max:255'],
            'emergency_mobile_phone' => ['nullable', 'string', 'max:20'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'emergency_address' => ['nullable', 'string', 'max:255'],
            'emergency_city' => ['nullable', 'string', 'max:255'],
            'emergency_state' => ['nullable', 'string', 'max:100'],
            'emergency_zip' => ['nullable', 'string', 'max:20'],
            'emergency_country' => ['nullable', 'string', 'max:100'],

            // Emergency Contact 2
            'emergency2_first_name' => ['nullable', 'string', 'max:100'],
            'emergency2_middle_name' => ['nullable', 'string', 'max:100'],
            'emergency2_last_name' => ['nullable', 'string', 'max:100'],
            'emergency2_email' => ['nullable', 'email', 'max:255'],
            'emergency2_mobile_phone' => ['nullable', 'string', 'max:20'],
            'emergency2_phone_number' => ['nullable', 'string', 'max:20'],
            'emergency2_street_address' => ['nullable', 'string', 'max:255'],
            'emergency2_city' => ['nullable', 'string', 'max:255'],
            'emergency2_state' => ['nullable', 'string', 'max:100'],
            'emergency2_zip' => ['nullable', 'string', 'max:20'],
            'emergency2_country' => ['nullable', 'string', 'max:100'],

            'roles' => ['nullable', 'array'],


        ];
    }

    public function messages(): array
    {
        return [];
    }
}
