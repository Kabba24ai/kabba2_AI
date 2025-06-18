<?php

namespace App\Http\Requests\Admin\ProductManagement\Products;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
        $input = PurifyHelper::purify($this->all(), ['short_description', 'description']);

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

            'is_general_term_type' => ['nullable', 'boolean'],
            'is_custom_term_type' => ['nullable', 'boolean'],

            'terms' => ['required_if:is_custom_term_type,1', 'array'],
            'terms.*' => ['exists:terms_and_conditions,id'],

            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:1000'],

            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['required', 'string'],

            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'existing_images' => ['nullable', 'array'],
            'existing_hover_image' => ['nullable', 'string'],

            // Retail
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'retail_sale_price' => ['nullable', 'numeric', 'min:0'],
            'retail_product_cost' => ['nullable', 'numeric', 'min:0'],

            // Rental pricing
            'rental_daily' => ['nullable', 'numeric', 'min:0'],
            'rental_weekend' => ['nullable', 'numeric', 'min:0'],
            'rental_weekly' => ['nullable', 'numeric', 'min:0'],
            'rental_monthly' => ['nullable', 'numeric', 'min:0'],

            // Damage waivers
            'rental_damage_waiver_daily' => ['nullable', 'numeric', 'min:0'],
            'rental_damage_waiver_weekend' => ['nullable', 'numeric', 'min:0'],
            'rental_damage_waiver_weekly' => ['nullable', 'numeric', 'min:0'],
            'rental_damage_waiver_monthly' => ['nullable', 'numeric', 'min:0'],

            // Prepaid options
            'rental_prepaid_cleaning' => ['nullable', 'numeric', 'min:0'],
            'rental_prepaid_fuel' => ['nullable', 'numeric', 'min:0'],
            'rental_fuel_gallons' => ['nullable', 'numeric', 'min:0'],
            'rental_fuel_type' => ['nullable', 'in:Diesel,Gas'],
            'rental_def_gallons' => ['nullable', 'numeric', 'min:0'],

            // Sale prices
            'sale_price_daily' => ['nullable', 'numeric', 'min:0'],
            'sale_price_weekend' => ['nullable', 'numeric', 'min:0'],
            'sale_price_weekly' => ['nullable', 'numeric', 'min:0'],
            'sale_price_monthly' => ['nullable', 'numeric', 'min:0'],

            // Delivery
            'standard_delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'extended_delivery_fee' => ['nullable', 'numeric', 'min:0'],

            'in_store_pickup' => ['nullable', 'in:Yes,No'],
            'delivery_and_pickup' => ['nullable', 'in:Yes,No'],

            // Hour tracking
            'hour_tracking' => ['nullable', 'in:Yes,No'],
            'hour_rate' => ['nullable', 'numeric', 'min:0'],

            // Status
            'status' => ['nullable', 'in:Published,Draft,Pending'],

            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:product_categories,id'],

            'options' => ['nullable', 'array'],
            'options.*' => ['exists:product_options,id'],

            'related_products' => ['nullable', 'array'],
            'related_products.*' => ['exists:products,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.required_if' => 'You must select at least one term when using custom terms.',
            'terms.array' => 'The terms must be a valid list.',
            'terms.*.exists' => 'Please select valid terms.',
        ];
    }
}
