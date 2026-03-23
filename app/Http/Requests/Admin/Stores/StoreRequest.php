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
            'lunch_start_time' => ['nullable'],
        ];

        // Days of the week
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {

            // Closed input REQUIRED + boolean
            $rules["{$day}_closed"] = ['required', 'boolean'];

            // Time fields optional
            $rules["{$day}_start"] = ['nullable', 'string'];
            $rules["{$day}_end"] = ['nullable', 'string'];
        }

        return $rules;
    }
}
