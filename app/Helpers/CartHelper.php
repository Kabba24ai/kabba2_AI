<?php

namespace App\Helpers;


use App\Enums\Products\ProductCustomStaticLabel;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductOptionItem;
use App\Models\ProductManagement\ProductRelatedProductChild;
use App\Models\Stores\Store;

class CartHelper
{
    public static function buildCartSummary(array $input): array
    {
        // --- Require cart_items ---
        if (!isset($input['cart_items']) || !is_array($input['cart_items']) || empty($input['cart_items'])) {
            throw new \InvalidArgumentException('The "cart_items" key is required.');
        }

        // --- Global/Config Settings ---
        $productSettings = ConfigurationHelper::getSettings('Product Settings');
        $allocatedHoursSettings = ConfigurationHelper::getSettings('Allocated Hours Settings');
        $taxRate = floatval($productSettings['sales_tax'] ?? 0);

        // --- Check session for tax exemption ---
        if (session()->has('tax_exempt')) {
            $taxExempt = session('tax_exempt');
        } else {
            $taxExempt = $input['tax_exempt'] ?? false; // Thank you page required $input['tax_exempt'] pass manually
        }
        // --- Cart-level meta ---
        $cartId = $input['cart_id'] ?? null;
        $orderNotes = $input['order_notes'] ?? null;
        $paymentMethod = $input['payment_method'] ?? null;
        $couponCode = $input['coupon_code'] ?? null;
        $discount = floatval($input['discount'] ?? 0);

        // Determine tax exemption based on authenticated customer
        // $customer = auth('customer')->check() ? auth('customer')->user() : null;

        // // Override $taxExempt if customer is authenticated and marked as Exempt
        // if ($customer && method_exists($customer, 'getTaxStatus')) {
        //     $taxExempt = $customer->getTaxStatus() !== 'Taxable';
        // }

        // --- Prepare Cart Data ---
        $cartData = $input['cart_items'];

        // Support: If single product, wrap in array
        if (!isset($cartData[0]) && is_array($cartData)) {
            $cartData = [$cartData];
        }

        $cartUniqueIds = collect($cartData)
            ->pluck('product_unique_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $cartProducts = Product::published()
            ->whereIn('unique_id', $cartUniqueIds)
            ->get()
            ->keyBy('unique_id');

        $cartProductIds = $cartProducts->pluck('id')->values()->all();

        $childProductIdsWithParentInCart = ProductRelatedProductChild::query()
            ->whereIn('related_product_id', $cartProductIds)
            ->whereIn('product_id', $cartProductIds)
            ->distinct()
            ->pluck('related_product_id')
            ->flip()
            ->all();

        $items = [];
        $subTotal = 0;
        $taxTotal = 0;
        $specialTaxTotal = 0;
        $addedFeesTotal = 0;
        $grandTotal = 0;

        foreach ($cartData as $validated) {
            $product = $cartProducts->get($validated['product_unique_id'] ?? null);
            if (!$product) {
                continue;
            }

            $hasParentInCart = isset($childProductIdsWithParentInCart[$product->id]);
            $item = self::buildCartItem($product, $validated, $taxRate, $productSettings, $taxExempt, $allocatedHoursSettings, $hasParentInCart);
            $items[] = $item;
            $subTotal += $item['sub_total'];
            $taxTotal += $taxExempt ? 0 : $item['tax'];
            $specialTaxTotal += $item['special_tax'];
            $addedFeesTotal += $item['added_fees'];
            $grandTotal += $item['sub_total'] + ($taxExempt ? 0 : $item['tax']) + $item['special_tax'] + $item['added_fees'];
        }

        $grandTotalAfterDiscount = $grandTotal - $discount;


        return [
            'cart_id' => $cartId,
            'tax_exempt' => $taxExempt,
            'order_notes' => $orderNotes,
            'payment_method' => $paymentMethod,
            'cart_items' => $items,
            'sub_total' => round($subTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'special_taxes_description' => $productSettings['special_taxes_description'] ?? 'Special Taxes',
            'special_tax_total' => round($specialTaxTotal, 2),
            'added_fees_description' => $productSettings['added_fees_description'] ?? 'Added Fees',
            'added_fees_total' => round($addedFeesTotal, 2),
            'coupon_code' => $couponCode,
            'discount' => $discount,
            'grand_total' => round($grandTotalAfterDiscount, 2),
        ];
    }

    private static function buildCartItem($product, $validated, $taxRate, $productSettings, $taxExempt, $allocatedHoursSettings, $hasParentInCart = false)
    {
        $quantity = $validated['quantity'];
        $variant = $validated['product_variant'] ?? null;

        $storeAddress = null;
        $storeName = null;
        if (!empty($validated['delivery_store_id'])) {
            $store = Store::find($validated['delivery_store_id']);
            $storeAddress = $store ? $store->full_address : null;
            $storeName = $store ? $store->store_name : null;
        }

        // Add distance_range from product settings based on distance_type
        $distanceRange = null;
        if (!empty($validated['distance_type'])) {
            $distanceType = $validated['distance_type'];
            // Example: keys like 'standard_distance_range', 'extended_distance_range'
            $settingKey = strtolower($distanceType) . '_delivery_range';
            if (isset($productSettings[$settingKey])) {
                $distanceRange = $productSettings[$settingKey] . ' ' . $productSettings['distance_unit'];
            }else{
                $distanceRange = $productSettings['extended_delivery_range'] . ' ' . $productSettings['distance_unit'];
            }
        }
        // --- Sale logic based on product type/variant ---
        $isSale = $product->product_type === 'Rental' ? $product->isRentalOnSale($variant) : $product->isRetailOnSale();

        // --- Get product base price ---
        $price = $product->product_type === 'Rental' ? $product->getRentalPrice($variant, $isSale) : $product->getRetailPrice($isSale);

        if ($hasParentInCart && $product->product_type === 'Rental' && !empty($variant)) {
            $relatedPrice = $product->getRelatedPrice(strtolower($variant));
            if ($relatedPrice !== false && $relatedPrice !== null) {
                $price = floatval($relatedPrice);
            }
        }

        // daily, weekend, weekly, monthly
        $allocatedHours = $product->product_type === 'Rental' ? floatval($allocatedHoursSettings[$variant.'_hours'] ?? 0) : 0.00;

        // --- Collect selected rental add-on items and their prices ---
        [$selectedRentalItemsWithPrices, $rentalItemsTotal] = self::resolveRentalItems($product, $validated, $variant, $quantity);

        // --- Calculate delivery/service option price ---
        $serviceOptionPrice = self::resolveServiceOptionPrice($product, $validated);

        // --- Calculate options/add-ons from product_option_items ---
        [$resolvedOptions, $optionsTotal] = self::resolveProductOptions($product, $validated, $variant, $quantity);


        $itemSubTotal = $price * $quantity + $optionsTotal + $serviceOptionPrice + $rentalItemsTotal;
        $itemTax = ($taxExempt || $product->is_tax_free_item) ? 0 : $itemSubTotal * $taxRate;
        $itemSpecialTax = $product->apply_special_tax ? $itemSubTotal * (floatval($productSettings['special_taxes'] ?? 0) / 100) : 0;
        $itemAddedFees = $product->apply_added_fees ? floatval($productSettings['added_fees'] ?? 0) * $quantity : 0;
        $itemTotal = $itemSubTotal + $itemTax + $itemSpecialTax + $itemAddedFees;

        $addDays = 1; // Default to 1 day per item
        $deliveryTime = null;
        $pickupTime = null;
        switch ($variant) {
            case 'weekend':
                $addDays = 3;
                $deliveryTime = '14:00:00';
                $pickupTime = '09:00:00';
                break;
            case 'weekly':
                $addDays = 7 * $quantity; // 7 days for weekly rental
                $deliveryTime = '09:00:00';
                $pickupTime = '09:00:00';
                break;
            case 'monthly':
                $addDays = 28 * $quantity; // 28 days for monthly rental
                $deliveryTime = '09:00:00';
                $pickupTime = '09:00:00';
                break;
            default:
                $addDays = 1 * $quantity; // 1 day for default rental
                $deliveryTime = '09:00:00';
                $pickupTime = '09:00:00';
                break;
        }

        $startDate = !empty($validated['delivery_date']) ? \Carbon\Carbon::parse($validated['delivery_date']) : null;
        $endDate = $startDate ? $startDate->copy()->addDays($addDays) : null;

        $deliveryTransportMode = 'Store';
        $pickupTransportMode = 'Store';
        if ($validated['service_method'] === 'In Store Pickup') {
            $deliveryTransportMode = 'Store';
            $pickupTransportMode = 'Store';
        } elseif ($validated['service_method'] === 'Delivery') {
            switch ($validated['service_option']) {
                case 'Delivery + Pickup':
                    $deliveryTransportMode = 'Truck';
                    $pickupTransportMode = 'Truck';
                    break;
                case 'Delivery Only':
                    $deliveryTransportMode = 'Truck';
                    $pickupTransportMode = 'Store';
                    break;
                case 'Return Only':
                    $deliveryTransportMode = 'Store';
                    $pickupTransportMode = 'Truck';
                    break;
                default:
                    $pickupTransportMode = 'Store';
                    $deliveryTransportMode = 'Store';
            }
        }



        return [
            'product_id' => $product->id,
            'product_unique_id' => $product->unique_id,
            'product_slug' => $product->slug,
            'product_name' => $product->product_name,
            'product_image_url' => $product->image_url,
            'product_type' => $product->product_type,
            'product_variant' => $variant,
            'product_sale_active' => $isSale ? true : false,
            'product_price' => $price,
            'quantity' => $quantity,
            'hour_tracking' => $product->hour_tracking ?? 'No',
            'hour_rate' => $product->hour_rate ?? 0,
            'allocated_hours' => $allocatedHours,

            'service_method' => $validated['service_method'] ?? null,
            'distance_type' => $validated['distance_type'] ?? null,
            'distance_range' => $distanceRange,
            'service_option' => $validated['service_option'] ?? null,
            'service_option_price' => round($serviceOptionPrice, 2),
            'store_address' => $storeAddress ?? null,
            'store_name' => $storeName ?? null,

            'delivery_transport_mode' => $deliveryTransportMode ?? null,
            'delivery_store_id' => $validated['delivery_store_id'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'delivery_time' => $deliveryTime,

            'pickup_transport_mode' => $pickupTransportMode ?? null,
            'pickup_store_id' => $validated['delivery_store_id'] ?? null,
            'pickup_date' => $endDate ? $endDate->format(config('app.date.date_format')) : null,
            'pickup_time' => $pickupTime ?? null,

            'product_option_items' => $resolvedOptions,
            'product_rental_items' => $validated['product_rental_items'],
            'product_rental_items_prices' => $selectedRentalItemsWithPrices,
            'sub_total' => round($itemSubTotal, 2),
            'tax' => round($itemTax, 2),
            'special_tax' => round($itemSpecialTax, 2),
            'added_fees' => round($itemAddedFees, 2),
            'total' => round($itemTotal, 2),
        ];
    }

    private static function resolveRentalItems($product, $validated, $variant, $quantity)
    {
        $rentalItemsTotal = 0;
        $selectedRentalItemsWithPrices = [];
        if ($product->product_type === 'Rental') {
            $productRentalItems = $validated['product_rental_items'] ?? [];
            foreach ($productRentalItems as $itemKey) {
                $case = collect(ProductCustomStaticLabel::cases())->firstWhere('name', $itemKey);
                if ($case) {
                    $priceKey = $case->name."_".strtolower($variant);
                    $price = floatval($product->$priceKey ?? 0);
                    $selectedRentalItemsWithPrices[$itemKey] = $price;
                    $rentalItemsTotal += $price * $quantity;
                } else {
                    $price = floatval($product->$itemKey ?? 0);
                    $selectedRentalItemsWithPrices[$itemKey] = $price;
                    $rentalItemsTotal += $price;
                }
            }
        }
        return [$selectedRentalItemsWithPrices, $rentalItemsTotal];
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
            } elseif ($distanceType === 'Custom') {
                $deliveryFee = floatval($product->extended_delivery_fee ?? 0);
            } else {
                $deliveryFee = 0;
            }

            switch ($serviceOption) {
                case 'Delivery + Pickup':
                    $serviceOptionPrice = $deliveryFee * 2;
                    break;
                case 'Delivery Only':
                case 'Return Only':
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
            'cart_items' => [
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
                'service_option' => 'Delivery + Pickup/Delivery Only/Return Only', // nullable
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
