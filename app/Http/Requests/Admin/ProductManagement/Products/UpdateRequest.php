<?php

namespace App\Http\Requests\Admin\ProductManagement\Products;

use App\Helpers\PurifyHelper;
use App\Http\Requests\ApiBaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends ApiBaseFormRequest
{
    protected function prepareForValidation()
    {
        $input = PurifyHelper::purify($this->all(), ['description', 'short_description']);

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
            'product_name' => ['required', 'string', 'max:240', Rule::unique('products', 'product_name')->ignore($this->route('unique_id'), 'unique_id')],
            'product_type' => ['required', 'in:Rental,Retail'],
            'slug' => ['required', 'string', 'max:240', Rule::unique('products', 'slug')->ignore($this->route('unique_id'), 'unique_id')],

            'is_general_term_type' => ['required', 'boolean'],
            'is_custom_term_type' => ['nullable', 'boolean'],

            'image_sort_order' => ['nullable'],

            'terms' => ['required_if:is_custom_term_type,1'],

            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:2000'],

            'short_description' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string'],

            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'existing_images' => ['nullable', 'array'],
            'existing_hover_image' => ['nullable', 'string'],

            // Retail
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'retail_price' => ['required_if:product_type,Retail', 'numeric', 'min:0', 'max:9999999', 'nullable'],
            'retail_sale_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'retail_product_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],

            // Rental pricing
            'rental_daily' => ['required_if:product_type,Rental', 'nullable', 'numeric', 'min:0', 'max:9999999'],
            'rental_weekend' => ['required_if:product_type,Rental', 'nullable', 'numeric', 'min:0', 'max:9999999'],
            'rental_weekly' => ['required_if:product_type,Rental', 'nullable', 'numeric', 'min:0', 'max:9999999'],
            'rental_monthly' => ['required_if:product_type,Rental', 'nullable', 'numeric', 'min:0', 'max:9999999'],

            // Damage waivers
            'rental_damage_waiver_daily' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'rental_damage_waiver_weekend' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'rental_damage_waiver_weekly' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'rental_damage_waiver_monthly' => ['nullable', 'numeric', 'min:0', 'max:9999999'],

            // Track insurance
            'rental_track_insurance_daily' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'rental_track_insurance_weekend' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'rental_track_insurance_weekly' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'rental_track_insurance_monthly' => ['nullable', 'numeric', 'min:0', 'max:9999999'],

            // Prepaid options
            'rental_prepaid_cleaning' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'rental_prepaid_fuel' => ['nullable', 'numeric', 'min:0', 'max:9999999'],

            // Sale prices
            'sale_price_daily' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'sale_price_weekend' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'sale_price_weekly' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'sale_price_monthly' => ['nullable', 'numeric', 'min:0', 'max:9999999'],

            // Delivery
            'standard_delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'extended_delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999'],

            'in_store_pickup' => ['nullable', 'in:Yes,No'],
            'delivery_and_pickup' => ['nullable', 'in:Yes,No'],

            // Hour tracking
            'hour_tracking' => ['nullable', 'in:Yes,No'],
            'hour_rate' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($this->input('product_type') === 'Rental' && $this->input('hour_tracking') === 'Yes' && (is_null($value) || $value === '')) {
                        $fail('The hour rate field is required when hour tracking is "Yes" and product type is "Rental".');
                    }
                    if ($this->input('product_type') === 'Rental' && $this->input('hour_tracking') === 'Yes' && (!is_null($value) && (!is_numeric($value) || $value < 0))) {
                        $fail('The hour rate must be a non-negative number.');
                    }
                },
            ],

            // Status
            'status' => ['required', 'in:Published,Draft,Pending'],

            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:product_categories,id'],

            'options' => ['nullable', 'array'],
            'options.*' => ['exists:product_options,id'],

            'related_products' => ['nullable', 'array'],
            'related_products.*' => ['exists:products,id'],

            'truck_fee_size_setting' => ['nullable', 'string', 'max:255'],
            'track_insurance_size_setting' => ['nullable', 'string', 'max:255'],
            'prepaid_cleaning_rate_setting' => ['nullable', 'string', 'max:255'],
            'prepaid_fuel_rate_setting' => ['nullable', 'string', 'max:255'],

            'is_default_funnel' => ['nullable', 'boolean'],
            'has_high_demand_alert' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Only apply if product_type is Rental
            if (($data['product_type'] ?? null) === 'Rental') {
                // At least one delivery fee required and > 0
                $stdFee = floatval($data['standard_delivery_fee'] ?? 0);
                $extFee = floatval($data['extended_delivery_fee'] ?? 0);

                // At least one pickup option "Yes"
                $pickup = $data['in_store_pickup'] ?? null;
                $deliveryPickup = $data['delivery_and_pickup'] ?? null;

                if ($stdFee <= 0 && $extFee <= 0 && $deliveryPickup == 'Yes') {
                    $validator->errors()->add('standard_delivery_fee', 'At least one delivery fee (standard or extended) must be greater than zero for rental products.');
                    //$validator->errors()->add('extended_delivery_fee', 'At least one delivery fee (standard or extended) must be greater than zero for rental products.');
                }

                if ($pickup !== 'Yes' && $deliveryPickup !== 'Yes') {
                    $validator->errors()->add('in_store_pickup', 'At least one pickup option (In Store or Delivery and Pickup) must be "Yes" for rental products.');
                    //$validator->errors()->add('delivery_and_pickup', 'At least one pickup option (In Store or Delivery and Pickup) must be "Yes" for rental products.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'terms.required_if' => 'You must select at least one term when using custom terms.',
            // 'terms.array' => 'The terms must be a valid list.',
            // 'terms.*.exists' => 'One or more selected terms are invalid or no longer exist.',
            'related_products.*.exists' => 'One or more selected related products are invalid or no longer exist.',
            'categories.*.exists' => 'One or more selected categories are invalid or no longer exist.',
            'options.*.exists' => 'One or more selected options are invalid or no longer exist.',
            'product_name.unique' => 'The product name must be unique. This name is already in use.',
            'product_type.in' => 'The product type must be either "Rental" or "Retail".',
            'is_general_term_type.boolean' => 'The general term type must be true or false.',
            'is_custom_term_type.boolean' => 'The custom term type must be true or false.',
            'categories.required' => 'You must select at least one category for the product.',
        ];
    }
}
