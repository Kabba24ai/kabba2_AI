<?php

namespace App\Http\Requests\Admin\Configurations\ProductSettings;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

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
        $exceptFields = ['prepaid_fuel_info', 'damage_waiver_info', 'track_insurance_info', 'tire_insurance_info'];

        $input = PurifyHelper::purify($this->all(), $exceptFields);
        $input['include_extended_range'] = $this->has('include_extended_range') ? true : false;
        $input['prepaid_fuel_rates'] = $this->input('fuel', []);
        $input['prepaid_cleaning_rates'] = $this->input('clean', []);

        $this->merge($input);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'fuel' => 'nullable|array',
            'fuel.*.description' => 'string|max:255',
            'fuel.*.rate' => 'numeric|min:0',
            'clean' => 'nullable|array',
            'clean.*.description' => 'string|max:255',
            'clean.*.rate' => 'nullable|numeric|min:0',

            'prepaid_fuel_rates' => 'nullable',
            'prepaid_cleaning_rates' => 'nullable',

            'standard_delivery_range' => 'nullable|numeric|min:0',
            'extended_delivery_range' => 'nullable|numeric|min:0',
            'include_extended_range' => 'nullable',
            'distance_unit' => 'nullable|string|in:Miles,Kilometers',
            'sales_tax' => 'nullable|numeric|min:0|max:100',
            'credit_card_processing_fee' => 'nullable|numeric|min:0|max:100',
            'special_taxes' => 'nullable|numeric|min:0|max:100',
            'special_taxes_description' => 'nullable|string|max:255',
            'added_fees' => 'nullable|numeric|min:0',
            'added_fees_description' => 'nullable|string|max:255',

            'daily_hours' => 'nullable|numeric|min:0',
            'weekend_hours' => 'nullable|numeric|min:0',
            'weekly_hours' => 'nullable|numeric|min:0',
            'monthly_hours' => 'nullable|numeric|min:0',
            'overage_rate_percentage' => 'nullable|numeric|min:0',
            'weekend_multiplier' => 'nullable|numeric|min:0',
            'weekly_multiplier' => 'nullable|numeric|min:0',
            'monthly_multiplier' => 'nullable|numeric|min:0',

            'prepaid_fuel_decline_label' => 'nullable|string|max:255',
            'prepaid_fuel_approve_label' => 'nullable|string|max:255',
            'prepaid_fuel_info' => 'nullable|string|max:500',

            'prepaid_cleaning_decline_label' => 'nullable|string|max:255',
            'prepaid_cleaning_approve_label' => 'nullable|string|max:255',
            'prepaid_cleaning_info' => 'nullable|string|max:500',

            'damage_waiver_percentage' => 'nullable|numeric|min:0|max:100',
            'damage_waiver_decline_label' => 'nullable|string|max:255',
            'damage_waiver_approve_label' => 'nullable|string|max:255',
            'damage_waiver_info' => 'nullable|string|max:500',

            'track_insurance_decline_label' => 'nullable|string|max:255',
            'track_insurance_approve_label' => 'nullable|string|max:255',
            'track_insurance_info' => 'nullable|string|max:500',

            'tire_insurance_decline_label' => 'nullable|string|max:255',
            'tire_insurance_approve_label' => 'nullable|string|max:255',
            'tire_insurance_info' => 'nullable|string|max:500',

            'small_standard_delivery_fee' => 'nullable|numeric|min:0',
            'medium_standard_delivery_fee' => 'nullable|numeric|min:0',
            'large_standard_delivery_fee' => 'nullable|numeric|min:0',
            'x_large_standard_delivery_fee' => 'nullable|numeric|min:0',
            '2x_large_standard_delivery_fee' => 'nullable|numeric|min:0',
            'commercial_standard_delivery_fee' => 'nullable|numeric|min:0',

            'small_extended_delivery_fee' => 'nullable|numeric|min:0',
            'medium_extended_delivery_fee' => 'nullable|numeric|min:0',
            'large_extended_delivery_fee' => 'nullable|numeric|min:0',
            'x_large_extended_delivery_fee' => 'nullable|numeric|min:0',
            '2x_large_extended_delivery_fee' => 'nullable|numeric|min:0',
            'commercial_extended_delivery_fee' => 'nullable|numeric|min:0',

            'small_daily_track_insurance_fee' => 'nullable|numeric|min:0',
            'medium_daily_track_insurance_fee' => 'nullable|numeric|min:0',
            'large_daily_track_insurance_fee' => 'nullable|numeric|min:0',
            'x_large_daily_track_insurance_fee' => 'nullable|numeric|min:0',
            '2x_large_daily_track_insurance_fee' => 'nullable|numeric|min:0',
            'commercial_daily_track_insurance_fee' => 'nullable|numeric|min:0',

            'small_weekend_track_insurance_fee' => 'nullable|numeric|min:0',
            'medium_weekend_track_insurance_fee' => 'nullable|numeric|min:0',
            'large_weekend_track_insurance_fee' => 'nullable|numeric|min:0',
            'x_large_weekend_track_insurance_fee' => 'nullable|numeric|min:0',
            '2x_large_weekend_track_insurance_fee' => 'nullable|numeric|min:0',
            'commercial_weekend_track_insurance_fee' => 'nullable|numeric|min:0',

            'small_weekly_track_insurance_fee' => 'nullable|numeric|min:0',
            'medium_weekly_track_insurance_fee' => 'nullable|numeric|min:0',
            'large_weekly_track_insurance_fee' => 'nullable|numeric|min:0',
            'x_large_weekly_track_insurance_fee' => 'nullable|numeric|min:0',
            '2x_large_weekly_track_insurance_fee' => 'nullable|numeric|min:0',
            'commercial_weekly_track_insurance_fee' => 'nullable|numeric|min:0',

            'small_monthly_track_insurance_fee' => 'nullable|numeric|min:0',
            'medium_monthly_track_insurance_fee' => 'nullable|numeric|min:0',
            'large_monthly_track_insurance_fee' => 'nullable|numeric|min:0',
            'x_large_monthly_track_insurance_fee' => 'nullable|numeric|min:0',
            '2x_large_monthly_track_insurance_fee' => 'nullable|numeric|min:0',
            'commercial_monthly_track_insurance_fee' => 'nullable|numeric|min:0',

            'small_daily_tire_insurance_fee' => 'nullable|numeric|min:0',
            'medium_daily_tire_insurance_fee' => 'nullable|numeric|min:0',
            'large_daily_tire_insurance_fee' => 'nullable|numeric|min:0',
            'x_large_daily_tire_insurance_fee' => 'nullable|numeric|min:0',
            '2x_large_daily_tire_insurance_fee' => 'nullable|numeric|min:0',
            'commercial_daily_tire_insurance_fee' => 'nullable|numeric|min:0',

            'small_weekend_tire_insurance_fee' => 'nullable|numeric|min:0',
            'medium_weekend_tire_insurance_fee' => 'nullable|numeric|min:0',
            'large_weekend_tire_insurance_fee' => 'nullable|numeric|min:0',
            'x_large_weekend_tire_insurance_fee' => 'nullable|numeric|min:0',
            '2x_large_weekend_tire_insurance_fee' => 'nullable|numeric|min:0',
            'commercial_weekend_tire_insurance_fee' => 'nullable|numeric|min:0',

            'small_weekly_tire_insurance_fee' => 'nullable|numeric|min:0',
            'medium_weekly_tire_insurance_fee' => 'nullable|numeric|min:0',
            'large_weekly_tire_insurance_fee' => 'nullable|numeric|min:0',
            'x_large_weekly_tire_insurance_fee' => 'nullable|numeric|min:0',
            '2x_large_weekly_tire_insurance_fee' => 'nullable|numeric|min:0',
            'commercial_weekly_tire_insurance_fee' => 'nullable|numeric|min:0',

            'small_monthly_tire_insurance_fee' => 'nullable|numeric|min:0',
            'medium_monthly_tire_insurance_fee' => 'nullable|numeric|min:0',
            'large_monthly_tire_insurance_fee' => 'nullable|numeric|min:0',
            'x_large_monthly_tire_insurance_fee' => 'nullable|numeric|min:0',
            '2x_large_monthly_tire_insurance_fee' => 'nullable|numeric|min:0',
            'commercial_monthly_tire_insurance_fee' => 'nullable|numeric|min:0',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        // remove from the validated payload only
        unset($data['fuel'], $data['clean']);

        return $key ? Arr::get($data, $key, $default) : $data;
    }
}
