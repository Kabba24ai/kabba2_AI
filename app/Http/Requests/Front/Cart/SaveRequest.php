<?php

namespace App\Http\Requests\Front\Cart;

use App\Helpers\PurifyHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
        $this->merge(PurifyHelper::purify($this->all(), []));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'product_unique_id' => ['required', 'string', 'exists:products,unique_id'],
            'product_type' => ['required', 'in:Rental,Retail'],
            'product_variant' => ['nullable', 'in:daily,monthly,weekend,weekly,retail'],
            'quantity' => ['required', 'integer', 'min:1'],
            'schedule_start_date' => ['required'],
            'service_method' => ['nullable', 'in:In Store Pickup,Delivery'],
            'distance_type'  => ['nullable','required_if:service_method,Delivery', 'in:Standard,Extended,Custom'],
            'service_option' => ['nullable', 'in:Delivery + Pickup,Delivery + Return,Pickup + Return'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'product_option_items' => ['nullable', 'array'],
            'product_option_items.*.unique_id' => ['required_with:product_option_items', 'string', 'exists:product_option_items,unique_id'],
            'product_rental_items' => ['nullable', 'array'],
            'product_rental_items.*' => ['required', 'in:rental_prepaid_fuel,rental_prepaid_cleaning,rental_fuel_gallons,rental_def_gallons,rental_damage_waiver'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('service_method', 'required', function ($input) {
            return $input->product_type === 'Rental';
        });

        $validator->sometimes('store_id', 'required', function ($input) {
            // In store pickup always needs a store
            if ($input->service_method === 'In Store Pickup') {
                return true;
            }
            // If delivery, only certain options need a store
            if ($input->service_method === 'Delivery' && in_array($input->service_option, ['Delivery + Return', 'Pickup + Return'])) {
                return true;
            }
            return false;
        });

        $validator->sometimes('service_option', 'required', function ($input) {
            // Only required if method is Delivery and product_type is Rental
            return $input->product_type === 'Rental' && $input->service_method === 'Delivery';
        });
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(
                [
                    'success' => false,
                    'message' => 'Validation error.',
                    'errors' => $validator->errors(),
                ],
                422,
            ),
        );
    }

    public function messages()
    {
        return [
            // General
            'required'     => 'The :attribute field is required.',
            'required_if'  => 'The :attribute field is required when :other is :value.',
            'in'           => 'The selected :attribute is invalid.',
            'exists'       => 'The selected :attribute does not exist.',

            // Specific fields
            'product_unique_id.required'   => 'A product is required.',
            'product_unique_id.exists'     => 'The selected product is not available.',
            'product_type.required'        => 'The product type is required.',
            'product_type.in'              => 'Product type must be Rental or Retail.',
            'product_variant.in'           => 'The product variant is invalid.',
            'quantity.required'            => 'Please enter a quantity.',
            'quantity.integer'             => 'Quantity must be a number.',
            'quantity.min'                 => 'Quantity must be at least 1.',
            'schedule_start_date.required' => 'Please select a start date.',
            'service_method.in'            => 'Please select a valid service option.',
            'service_method.required'      => 'Service option is required for rentals.',
            'distance_type.required_if'    => 'Please select a distance type for delivery.',
            'distance_type.in'             => 'Distance type must be Standard, Extended, or Custom.',
            'service_option.required'      => 'Delivery option is required for rental delivery.',
            'service_option.in'            => 'Please select a valid Delivery option.',
            'store_id.required'            => 'Please select a store location.',
            'store_id.exists'              => 'The selected store does not exist.',
            'store_id.integer'             => 'Store ID must be a valid number.',
            'product_option_items.array'   => 'Product options must be a list.',
            'product_option_items.*.unique_id.required_with' => 'Every selected product option must have a unique ID.',
            'product_option_items.*.unique_id.exists'        => 'One or more selected options are invalid.',
            'product_rental_items.array'   => 'Rental items must be a list.',
            'product_rental_items.*.in'    => 'One or more rental options are invalid.',
            'product_rental_items.*.required' => 'Each rental item must be specified.',
        ];
    }

}
