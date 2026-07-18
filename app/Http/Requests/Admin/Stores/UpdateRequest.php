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
        // Sanitize all input
        $cleaned = PurifyHelper::purify($this->all(), []);

        // Days of the week
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        // Convert checkboxes ("on" or missing) into real booleans
        foreach ($days as $day) {
            $cleaned["{$day}_closed"] = $this->boolean("{$day}_closed");
            $cleaned["{$day}_lunch"]  = $this->boolean("{$day}_lunch");
        }

        if (!empty($cleaned['lunch_start_time'])) {
            try {
                $cleaned['lunch_start_time'] = \Carbon\Carbon::parse($cleaned['lunch_start_time'])->format('H:i:s');
            } catch (\Exception $e) {
                $cleaned['lunch_start_time'] = null;
            }
        }

        // Normalise service area boolean fields
        $cleaned['service_area_enable_radius']   = (int) $this->boolean('service_area_enable_radius');
        $cleaned['service_area_delivery_allowed'] = (int) $this->boolean('service_area_delivery_allowed');
        $cleaned['service_area_pickup_allowed']   = (int) $this->boolean('service_area_pickup_allowed');

        // Public Website Page checkbox
        $cleaned['show_contact_strip'] = $this->boolean('show_contact_strip');

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
            'country' => ['nullable', 'string', 'max:100'],
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
            $rules["{$day}_lunch"]  = ['nullable', 'boolean'];
            $rules["{$day}_start"]  = [
                'nullable',
                'regex:/^((1[0-2]|0?[1-9]):[0-5][0-9]\s?(AM|PM)|([01]?[0-9]|2[0-3]):[0-5][0-9])$/i',
            ];
            $rules["{$day}_end"] = [
                'nullable',
                'regex:/^((1[0-2]|0?[1-9]):[0-5][0-9]\s?(AM|PM)|([01]?[0-9]|2[0-3]):[0-5][0-9])$/i',
            ];
        }

        // Service area — radius
        $rules['service_area_enable_radius']    = ['nullable', 'boolean'];
        $rules['service_area_radius_miles']     = ['nullable', 'numeric', 'min:0.1'];
        $rules['service_area_delivery_allowed'] = ['nullable', 'boolean'];
        $rules['service_area_pickup_allowed']   = ['nullable', 'boolean'];

        // Public Website Page
        $rules['page_status']            = ['nullable', 'in:Active,Inactive'];
        $rules['show_contact_strip']     = ['nullable', 'boolean'];
        $rules['page_heading']           = ['nullable', 'string', 'max:240'];
        $rules['intro_text']             = ['nullable', 'string', 'max:500'];
        $rules['page_description']       = ['nullable', 'string', 'max:5000'];
        $rules['page_image_media_id']    = ['nullable', 'integer', 'exists:media,id'];
        $rules['seo_title']              = ['nullable', 'string', 'max:240'];
        $rules['page_meta_description']  = ['nullable', 'string', 'max:500'];
        $rules['og_title']               = ['nullable', 'string', 'max:240'];
        $rules['og_description']         = ['nullable', 'string', 'max:500'];
        $rules['page_og_image_media_id'] = ['nullable', 'integer', 'exists:media,id'];
        $rules['canonical_url']          = ['nullable', 'url', 'max:500'];

        // Service area — included/excluded rows (wildcard)
        foreach (['included', 'excluded'] as $group) {
            $rules["service_areas_{$group}"]                    = ['nullable', 'array'];
            $rules["service_areas_{$group}.*.area_type"]        = ['required', 'in:city,county,zip,custom_area'];
            $rules["service_areas_{$group}.*.name"]             = ['nullable', 'string', 'max:255'];
            $rules["service_areas_{$group}.*.city"]             = ['nullable', 'string', 'max:100'];
            $rules["service_areas_{$group}.*.county"]           = ['nullable', 'string', 'max:100'];
            $rules["service_areas_{$group}.*.state"]            = ['nullable', 'string', 'max:100'];
            $rules["service_areas_{$group}.*.zip_code"]         = ['nullable', 'string', 'max:20'];
            $rules["service_areas_{$group}.*.notes"]            = ['nullable', 'string', 'max:1000'];
            $rules["service_areas_{$group}.*.delivery_allowed"] = ['nullable', 'in:0,1'];
            $rules["service_areas_{$group}.*.pickup_allowed"]   = ['nullable', 'in:0,1'];
            $rules["service_areas_{$group}.*.is_active"]        = ['nullable', 'in:0,1'];
        }

        return $rules;
    }
}
