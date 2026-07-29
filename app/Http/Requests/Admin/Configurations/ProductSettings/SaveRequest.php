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

        // Smart price rounding: the form posts individual ending digits;
        // drop blanks and persist as one comma-separated setting value.
        if ($this->has('price_endings')) {
            $endings = array_values(array_filter(
                (array) $this->input('price_endings', []),
                fn ($digit) => $digit !== null && $digit !== '',
            ));
            $input['price_endings'] = $endings;
            $input['allowed_price_endings'] = implode(',', $endings);
        }

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
            // Custom 1–4 tiers: blank = not configured; values need not be
            // sequential or ascending, so no cross-tier comparison rules.
            'custom_1_delivery_range' => 'nullable|numeric|min:0',
            'custom_2_delivery_range' => 'nullable|numeric|min:0',
            'custom_3_delivery_range' => 'nullable|numeric|min:0',
            'custom_4_delivery_range' => 'nullable|numeric|min:0',
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
            'weekend_multiplier' => 'nullable|numeric|gt:0',
            'weekly_multiplier' => 'nullable|numeric|gt:0',
            'monthly_multiplier' => 'nullable|numeric|gt:0',

            // Smart price rounding: unique whole digits 0–9 plus a whole-dollar
            // hundred-entry threshold. The CSV field is assembled in
            // prepareForValidation and is what actually persists.
            'price_endings' => 'nullable|array|max:3',
            'price_endings.*' => 'integer|between:0,9|distinct',
            'allowed_price_endings' => 'nullable|string|max:20',
            'hundred_entry_threshold' => 'nullable|integer|min:0',

            'prepaid_fuel_decline_label' => 'nullable|string|max:255',
            'prepaid_fuel_approve_label' => 'nullable|string|max:255',
            'prepaid_fuel_info' => 'nullable|string|max:500',

            'prepaid_cleaning_decline_label' => 'nullable|string|max:255',
            'prepaid_cleaning_approve_label' => 'nullable|string|max:255',
            'prepaid_cleaning_info' => 'nullable|string|max:500',

            'std_clean_req' => 'nullable|numeric|min:0',
            'moderate_clean_req' => 'nullable|numeric|min:0',
            'extreme_clean_req' => 'nullable|numeric|min:0',

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

            'small_custom_1_delivery_fee' => 'nullable|numeric|min:0',
            'medium_custom_1_delivery_fee' => 'nullable|numeric|min:0',
            'large_custom_1_delivery_fee' => 'nullable|numeric|min:0',
            'x_large_custom_1_delivery_fee' => 'nullable|numeric|min:0',
            '2x_large_custom_1_delivery_fee' => 'nullable|numeric|min:0',
            'commercial_custom_1_delivery_fee' => 'nullable|numeric|min:0',

            'small_custom_2_delivery_fee' => 'nullable|numeric|min:0',
            'medium_custom_2_delivery_fee' => 'nullable|numeric|min:0',
            'large_custom_2_delivery_fee' => 'nullable|numeric|min:0',
            'x_large_custom_2_delivery_fee' => 'nullable|numeric|min:0',
            '2x_large_custom_2_delivery_fee' => 'nullable|numeric|min:0',
            'commercial_custom_2_delivery_fee' => 'nullable|numeric|min:0',

            'small_custom_3_delivery_fee' => 'nullable|numeric|min:0',
            'medium_custom_3_delivery_fee' => 'nullable|numeric|min:0',
            'large_custom_3_delivery_fee' => 'nullable|numeric|min:0',
            'x_large_custom_3_delivery_fee' => 'nullable|numeric|min:0',
            '2x_large_custom_3_delivery_fee' => 'nullable|numeric|min:0',
            'commercial_custom_3_delivery_fee' => 'nullable|numeric|min:0',

            'small_custom_4_delivery_fee' => 'nullable|numeric|min:0',
            'medium_custom_4_delivery_fee' => 'nullable|numeric|min:0',
            'large_custom_4_delivery_fee' => 'nullable|numeric|min:0',
            'x_large_custom_4_delivery_fee' => 'nullable|numeric|min:0',
            '2x_large_custom_4_delivery_fee' => 'nullable|numeric|min:0',
            'commercial_custom_4_delivery_fee' => 'nullable|numeric|min:0',

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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Smart rounding is active when a hundred-entry threshold is set;
            // it can do nothing without at least one allowed ending.
            $threshold = $this->input('hundred_entry_threshold');
            $hasThreshold = $threshold !== null && $threshold !== '' && (int) $threshold > 0;
            $hasEndings = ! empty($this->input('price_endings'));

            if ($this->has('price_endings') && $hasThreshold && ! $hasEndings) {
                $validator->errors()->add(
                    'price_endings',
                    'At least one allowed price ending is required when smart rounding is active.',
                );
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        // remove from the validated payload only (price_endings persists via
        // the assembled allowed_price_endings setting value)
        unset($data['fuel'], $data['clean'], $data['price_endings']);

        return $key ? Arr::get($data, $key, $default) : $data;
    }
}
