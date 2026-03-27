<?php

namespace App\Http\Requests\Admin\Stores;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $cleaned = PurifyHelper::purify($this->all(), []);

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {
            // Convert checkbox into real boolean
            $cleaned["{$day}_closed"] = $this->boolean("{$day}_closed");
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
            'store_name' => [
                'required',
                'string',
                'max:240',
                'unique:stores,store_name,' . $this->route('unique_id') . ',unique_id'
            ],

            'status' => ['required', 'in:Active,Inactive,Archived'],
            'is_primary' => ['required', 'in:Yes,No'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:100'],
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

        // Days with time validation
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {

            $rules["{$day}_closed"] = ['required', 'boolean'];

            // Allow 12-hour or 24-hour format
            $rules["{$day}_start"] = [
                'nullable',
                'regex:/^((1[0-2]|0?[1-9]):[0-5][0-9]\s?(AM|PM)|([01]?[0-9]|2[0-3]):[0-5][0-9])$/i'
            ];

            $rules["{$day}_end"] = [
                'nullable',
                'regex:/^((1[0-2]|0?[1-9]):[0-5][0-9]\s?(AM|PM)|([01]?[0-9]|2[0-3]):[0-5][0-9])$/i'
            ];
        }

        return $rules;
    }
}
