<?php

namespace App\Http\Requests\Admin\Stores;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Sanitize all input
        $cleaned = PurifyHelper::purify($this->all(), []);

        // Days of the week
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        // Convert checkboxes ("on" or missing) into real booleans
        foreach ($days as $day) {
            $cleaned["{$day}_closed"] = $this->boolean("{$day}_closed");

            $cleaned["{$day}_lunch"] = $this->boolean("{$day}_lunch");
        }

         if (!empty($cleaned['lunch_start_time'])) {
            try {
                $cleaned['lunch_start_time'] = \Carbon\Carbon::parse($cleaned['lunch_start_time'])
                    ->format('H:i:s'); //  13:00:00
            } catch (\Exception $e) {
                $cleaned['lunch_start_time'] = null;
            }
    }

        $this->merge($cleaned);
    }

    public function rules(): array
    {
        $rules = [
            'store_name' => ['required', 'string', 'max:240', 'unique:stores,store_name'],
            'status' => ['required', 'in:Active,Inactive,Archived'],
            'is_primary' => ['nullable', 'in:Yes,No'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'state_id' => ['required', 'exists:states,id'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'zip_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'string'],
            'longitude' => ['required', 'string'],
            'details' => ['nullable', 'string', 'max:255'],
            // 'lunch_start_time' => ['nullable'],
            'lunch_start_time' => [
                    'nullable',
                    function ($attribute, $value, $fail) {

                        try {
                            $time = \Carbon\Carbon::parse($value);
                            $minutes = $time->minute;

                            if ($minutes % 5 !== 0) {
                                $fail('Lunch start time must be in 5-minute intervals (00, 05, 10...).');
                            }

                        } catch (\Exception $e) {
                            $fail('Invalid time format.');
                        }
                    }
                ],
        ];

        // Days of the week
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {

            // Closed input REQUIRED + boolean
            $rules["{$day}_closed"] = ['required', 'boolean'];

            $rules["{$day}_lunch"] = ['nullable', 'boolean'];

            // Time fields optional
            $rules["{$day}_start"] = ['nullable', 'string'];
            $rules["{$day}_end"] = ['nullable', 'string'];
        }

        return $rules;
    }
}
