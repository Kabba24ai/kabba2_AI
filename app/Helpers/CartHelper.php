<?php

namespace App\Helpers;

use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductOption;
use App\Models\ProductManagement\ProductOptionItem;

class CartHelper
{
    public static function calculateCartTotals($cart)
    {
        $productSettings = ConfigurationHelper::getSettings('Product Settings');
        $taxRate = $productSettings['sales_tax'];
        $result = [];
        $subTotal = 0;
        $taxAmount = 0;
        $grandTotal = 0;

        foreach ($cart as $item) {
            // 1. Find product by id/unique_id
            $product = Product::Where('unique_id', $item['product_unique_id'])->first();

            if (!$product) continue;

            // if($product->product_type == $item['varient_type']){
            //     switch ($item['product_type']) {
            //         case '':
            //             # code...
            //             break;

            //         default:
            //             # code...
            //             break;
            //     }
            // }

            $productPrice = $item['base_price'];

            // 2. Addons: Find each by unique_id or name
            $addonsTotal = 0;
            $addonsDetails = [];
            if (!empty($item['addons'])) {
                foreach ($item['addons'] as $addon) {
                    // Use unique_id if possible, fallback to name
                    if (!empty($addon['unique_id'])) {
                        $productOptionItem = ProductOptionItem::where('unique_id', $addon['unique_id'])->first();
                        if ($productOptionItem) {
                            $addonsTotal += $productOptionItem->price;
                            $addonsDetails[] = [
                                'name' => $productOptionItem->name,
                                'price' => $productOptionItem->price,
                            ];
                        }
                    } else {
                        //$productOptionItem->where('name', $addon['name']);
                    }
                }
            }

            $qty = (int)($item['qty'] ?? 1);
            $deliveryFee = isset($item['delivery_fee']) ? floatval($item['delivery_fee']) : 0;

            // Total calculation for this cart item
            $lineSubTotal = ($productPrice + $addonsTotal + $deliveryFee) * $qty;
            $tax = round($lineSubTotal * $taxRate, 2);
            $total = round($lineSubTotal + $tax, 2);

            // For summary use
            $subTotal += $lineSubTotal;
            $taxAmount += $tax;
            $grandTotal += $total;

            $result[] = [
                'product_id'   => $product->id,
                'name'         => $product->product_name,
                'qty'          => $qty,
                'price'        => $productPrice,
                'addons'       => $addonsDetails,
                'addons_total' => $addonsTotal,
                'delivery_fee' => $deliveryFee,
                'tax_rate'     => $taxRate,
                'tax'          => $tax,
                'total'        => $total,
                // ...other fields as needed
            ];
        }

        return [
            'items'      => $result,
            'subtotal'   => $subTotal,
            'tax_amount' => $taxAmount,
            'grand_total'=> $grandTotal,
        ];
    }

    public static function calculateRentalCartTotals($cart)
    {
        [
            'cart_id' => 'Cart-001',
            'tax_exempt' => 'true/false',
            'order_notes' => 'test', // nullable
            'payment_method' => 'COD/Account/Card',
            'cart_data' => [
                'product_unique_id' => "PRO-001", // not null
                'product_type' => "Rental/Retail", // not null
                'product_variant' => "Daily/Monthly/Weekend/Weekly", // null
                'product_sale_active' => "true/false", // true then sales price otherwise rental price  // not null
                'product_price' => "100", // not null
                'quantity' => '1', // not null
                'schedule_start_date' => '25/08/2025', // not null
                'service_method' => 'In Store Pickup/Delivery', // nullable
                'distance_type' => 'Standard/Extended/Custom', // nullable
                'distance_range' => '15 (Miles/Kilometers)/30 (Miles/Kilometers)', // nullable
                'service_option' => 'Delivery + Pickup/Delivery + Return/Pickup + Return', // nullable
                'store_id' => '1', // nullable
                'product_option_items' => [
                    [
                        'unique_id' => 'PRO-OPT-ITM',
                        'name' => 'Fuel Gallons',
                        'price' => '10', // price according the product variant if rental or retail
                        'charged' => 'Unlimited/1 Time Max',
                        'comment' => 'test'
                    ]
                ],
                'sub_total' => '110',
                'tax' => '0.10',
                'total' => '110.10',
            ],
            'sub_total'=> '110',
            'tax_total'=> '0.10',
            'coupon_code'=> '', // nullable
            'discount'=> '0', // zero default
            'grand_total'=> '110.10',
        ];
    }
}
