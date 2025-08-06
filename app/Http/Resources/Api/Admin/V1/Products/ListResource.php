<?php

namespace App\Http\Resources\Api\Admin\V1\Products;

use App\Helpers\CustomHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\Api\Admin\V1\ProductCategories\ListResource as ProductCategoriesListResource;

class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request)
    {
        $return = [
            'id' => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? 0,
            'product_name' => $this->product_name ?? '',
            'slug' => $this->slug ?? '',
            'product_type' => $this->product_type ?? '',
            'seo_title' => $this->seo_title ?? '',
            'seo_description' => $this->seo_description ?? '',
            'short_description' => $this->short_description ?? '',
            'description' => $this->description ?? '',
            'is_general_term_type' => $this->is_general_term_type ?? false,
            'is_custom_term_type' => $this->is_custom_term_type ?? false,

            'sku' => $this->sku ?? '',
            'barcode' => $this->barcode ?? '',
            'retail_price' => $this->retail_price ?? 0,
            'retail_sale_price' => $this->retail_sale_price ?? 0,
            'retail_product_cost' => $this->retail_product_cost ?? 0,

            'rental_daily' => $this->rental_daily ?? 0,
            'rental_weekend' => $this->rental_weekend ?? 0,
            'rental_weekly' => $this->rental_weekly ?? 0,
            'rental_monthly' => $this->rental_monthly ?? 0,

            'rental_damage_waiver_daily' => $this->rental_damage_waiver_daily ?? 0,
            'rental_damage_waiver_weekend' => $this->rental_damage_waiver_weekend ?? 0,
            'rental_damage_waiver_weekly' => $this->rental_damage_waiver_weekly ?? 0,
            'rental_damage_waiver_monthly' => $this->rental_damage_waiver_monthly ?? 0,

            'rental_prepaid_cleaning' => $this->rental_prepaid_cleaning ?? 0,
            'rental_prepaid_fuel' => $this->rental_prepaid_fuel ?? 0,
            'rental_fuel_gallons' => $this->rental_fuel_gallons ?? 0,
            'rental_fuel_type' => $this->rental_fuel_type ?? '',
            'rental_def_gallons' => $this->rental_def_gallons ?? 0,

            'sale_price_daily' => $this->sale_price_daily ?? 0,
            'sale_price_weekend' => $this->sale_price_weekend ?? 0,
            'sale_price_weekly' => $this->sale_price_weekly ?? 0,
            'sale_price_monthly' => $this->sale_price_monthly ?? 0,

            'standard_delivery_fee' => $this->standard_delivery_fee ?? 0,
            'extended_delivery_fee' => $this->extended_delivery_fee ?? 0,

            'in_store_pickup' => $this->in_store_pickup ?? false,
            'delivery_and_pickup' => $this->delivery_and_pickup ?? false,

            'hour_tracking' => $this->hour_tracking ?? false,
            'hour_rate' => $this->hour_rate ?? 0,

            'status' => $this->status ?? '',

            'image_url' => $this->image_url ?? '',

            'categories' => ProductCategoriesListResource::collection($this->categories) ?? [],

        ];

        return $return;
    }
}
