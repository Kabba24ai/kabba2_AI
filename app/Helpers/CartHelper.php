<?php

namespace App\Helpers;

use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductOptionItem;
use App\Models\Stores\Store;

class CartHelper
{
    public static function buildCartSummary(array $input): array
    {
        // --- Global/Config Settings ---
        $productSettings = ConfigurationHelper::getSettings('Product Settings');
        $taxRate = floatval($productSettings['sales_tax'] ?? 0);

        // --- Cart-level meta ---
        $cartId = $input['cart_id'] ?? null;
        $taxExempt = $input['tax_exempt'] ?? false;
        $orderNotes = $input['order_notes'] ?? null;
        $paymentMethod = $input['payment_method'] ?? null;
        $couponCode = $input['coupon_code'] ?? null;
        $discount = floatval($input['discount'] ?? 0);

        // --- Prepare Cart Data ---
        $cartData = $input['cart_data'] ?? [];
        // Support: If single product, wrap in array
        if (!isset($cartData[0]) && is_array($cartData)) {
            $cartData = [$cartData];
        }

        $items = [];
        $subTotal = 0;
        $taxTotal = 0;
        $grandTotal = 0;

        foreach ($cartData as $validated) {
            $product = Product::published()->where('unique_id', $validated['product_unique_id'])->first();
            if (!$product) {
                continue;
            }

            $item = self::buildCartItem($product, $validated, $taxRate, $productSettings);
            $items[] = $item;
            $subTotal += $item['sub_total'];
            $taxTotal += $item['tax'];
            $grandTotal += $item['total'];
        }

        $grandTotalAfterDiscount = $grandTotal - $discount;

        return [
            'cart_id' => $cartId,
            'tax_exempt' => $taxExempt,
            'order_notes' => $orderNotes,
            'payment_method' => $paymentMethod,
            'cart_data' => $items,
            'sub_total' => round($subTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'coupon_code' => $couponCode,
            'discount' => $discount,
            'grand_total' => round($grandTotalAfterDiscount, 2),
        ];
    }

    private static function buildCartItem($product, $validated, $taxRate, $productSettings)
    {
        $quantity = $validated['quantity'];
        $variant = $validated['product_variant'] ?? null;

        $storeAddress = null;
        if (!empty($validated['store_id'])) {
            $store = Store::find($validated['store_id']);
            $storeAddress = $store ? $store->getFullAddress() : null;
        }

        // Add distance_range from product settings based on distance_type
        $distanceRange = null;
        if (!empty($validated['distance_type'])) {
            $distanceType = $validated['distance_type'];
            // Example: keys like 'standard_distance_range', 'extended_distance_range'
            $settingKey = strtolower($distanceType) . '_delivery_range';
            if (isset($productSettings[$settingKey])) {
                $distanceRange = $productSettings[$settingKey] . ' ' . $productSettings['distance_unit'];
            }
        }
        // --- Sale logic based on product type/variant ---
        $isSale = $product->product_type === 'Rental' ? $product->isRentalOnSale($variant) : $product->isRetailOnSale();

        // --- Get product base price ---
        $price = $product->product_type === 'Rental' ? $product->getRentalPrice($variant, $isSale) : $product->getRetailPrice($isSale);

        // --- Collect selected rental add-on items and their prices ---
        $selectedRentalItemsWithPrices = self::resolveRentalItems($product, $validated, $variant);

        // --- Calculate delivery/service option price ---
        $serviceOptionPrice = self::resolveServiceOptionPrice($product, $validated);

        // --- Calculate options/add-ons from product_option_items ---
        [$resolvedOptions, $optionsTotal] = self::resolveProductOptions($product, $validated, $variant, $quantity);

        // --- Calculate all totals ---
        $rentalItemsTotal = array_sum($selectedRentalItemsWithPrices);

        $itemSubTotal = $price * $quantity + $optionsTotal + $serviceOptionPrice + $rentalItemsTotal;
        $itemTax = $itemSubTotal * $taxRate;
        $itemTotal = $itemSubTotal + $itemTax;

        return [
            'product_unique_id' => $product->unique_id,
            'product_name' => $product->product_name,
            'product_image_url' => $product->image_url,
            'product_type' => $product->product_type,
            'product_variant' => $variant,
            'product_sale_active' => $isSale ? true : false,
            'product_price' => $price,
            'quantity' => $quantity,
            'schedule_start_date' => $validated['schedule_start_date'] ?? null,
            'service_method' => $validated['service_method'] ?? null,
            'distance_type' => $validated['distance_type'] ?? null,
            'distance_range' => $distanceRange,
            'service_option' => $validated['service_option'] ?? null,
            'service_option_price' => round($serviceOptionPrice, 2),
            'store_id' => $validated['store_id'] ?? null,
            'store_address' => $storeAddress ?? null,
            'product_option_items' => $resolvedOptions,
            'product_rental_items' => $validated['product_rental_items'],
            'product_rental_items_prices' => $selectedRentalItemsWithPrices,
            'sub_total' => round($itemSubTotal, 2),
            'tax' => round($itemTax, 2),
            'total' => round($itemTotal, 2),
        ];
    }

    private static function resolveRentalItems($product, $validated, $variant)
    {
        $selectedRentalItemsWithPrices = [];
        if ($product->product_type === 'Rental') {
            $productRentalItems = $validated['product_rental_items'] ?? [];
            foreach ($productRentalItems as $itemKey) {
                if ($itemKey === 'rental_damage_waiver' && $variant) {
                    $damageWaiverKey = 'rental_damage_waiver_' . strtolower($variant);
                    $selectedRentalItemsWithPrices[$itemKey] = floatval($product->$damageWaiverKey ?? 0);
                } else {
                    $selectedRentalItemsWithPrices[$itemKey] = floatval($product->$itemKey ?? 0);
                }
            }
        }
        return $selectedRentalItemsWithPrices;
    }

    private static function resolveServiceOptionPrice($product, $validated)
    {
        $serviceOptionPrice = 0;
        if (($validated['service_method'] ?? null) === 'Delivery' && !empty($validated['distance_type']) && !empty($validated['service_option'])) {
            $distanceType = $validated['distance_type'];
            $serviceOption = $validated['service_option'];

            if ($distanceType === 'Standard') {
                $deliveryFee = floatval($product->standard_delivery_fee ?? 0);
            } elseif ($distanceType === 'Extended') {
                $deliveryFee = floatval($product->extended_delivery_fee ?? 0);
            } else {
                $deliveryFee = 0;
            }

            switch ($serviceOption) {
                case 'Delivery + Pickup':
                    $serviceOptionPrice = $deliveryFee * 2;
                    break;
                case 'Delivery + Return':
                case 'Pickup + Return':
                    $serviceOptionPrice = $deliveryFee;
                    break;
                default:
                    $serviceOptionPrice = 0;
            }
        }
        return $serviceOptionPrice;
    }

    private static function resolveProductOptions($product, $validated, $variant, $quantity)
    {
        $resolvedOptions = [];
        $options = $validated['product_option_items'] ?? [];
        $optionsTotal = 0;
        foreach ($options as $option) {
            $objOption = ProductOptionItem::where('unique_id', $option['unique_id'])->first();
            if (!$objOption) {
                continue;
            }
            // Use price based on product type/variant
            if ($product->product_type === 'Rental' && isset($variant)) {
                $optionPrice = floatval($objOption->$variant ?? 0); // e.g. daily, weekend
            } else {
                $optionPrice = $objOption->retail_price;
            }
            // "Unlimited" charge per quantity, else once
            if ($objOption->charged == 'Unlimited') {
                $optionsTotal += $optionPrice * $quantity;
            } else {
                $optionsTotal += $optionPrice;
            }
            $resolvedOptions[] = [
                'unique_id' => $objOption->unique_id ?? null,
                'name' => $objOption->label ?? null,
                'price' => $optionPrice,
                'charged' => $objOption->charged ?? null,
                'comment' => $objOption->comment ?? null,
            ];
        }
        return [$resolvedOptions, $optionsTotal];
    }

    public static function calculateRentalCartTotals($cart)
    {
        [
            'cart_id' => 'Cart-001',
            'tax_exempt' => 'true/false',
            'order_notes' => 'test', // nullable
            'payment_method' => 'COD/Account/Card',
            'cart_data' => [
                'product_unique_id' => 'PRO-001', // not null
                'product_type' => 'Rental/Retail', // not null
                'product_variant' => 'Daily/Monthly/Weekend/Weekly', // null
                'product_sale_active' => 'true/false', // true then sales price otherwise rental price  // not null
                'product_price' => '100', // not null
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
                        'comment' => 'test',
                    ],
                ],
                'sub_total' => '110',
                'tax' => '0.10',
                'total' => '110.10',
            ],
            'sub_total' => '110',
            'tax_total' => '0.10',
            'coupon_code' => '', // nullable
            'discount' => '0', // zero default
            'grand_total' => '110.10',
        ];
    }
}
