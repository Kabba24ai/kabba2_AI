<div>
    <div class="flex items-center justify-between mb-4">
        <span class="font-semibold">Your cart</span>
        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-800" />
    </div>

    @if (!empty($cart['cart_items']))
        @foreach ($cart['cart_items'] as $item)
            <div class="flex items-start gap-4 mb-4">

                <img src="{{ $item['product_image_url'] }}" alt="Product" class="w-14 h-14 object-cover rounded border border-yellow-400">
                <div class="flex-1 flex flex-col gap-y-1">
                    <div class="font-semibold text-sm flex items-center gap-x-2 capitalize">
                        {{ $item['product_name'] }}
                        @if ($item['product_type'] === 'Rental' && !empty($item['product_variant']))
                            <span class="text-xs font-normal">
                                - ({{ ucfirst($item['product_variant']) }})
                            </span>
                        @endif
                    </div>

                    @if (!empty($item['product_type']))
                        <div class="text-xs text-yellow-600 font-semibold">
                            {{ $item['product_type'] }}
                        </div>
                    @endif

                    <div class="font-semibold text-sm text-black">
                        Price: {{ App\Helpers\CustomHelper::formatCurrency($item['product_price']) }}
                        (x{{ $item['quantity'] }})
                    </div>

                    <div class="text-sm text-black">
                        <span class="font-semibold">Schedule Date :</span>
                        <span>{{ $item['schedule_start_date'] }}</span>
                    </div>

                    {{-- @if (!empty($item['service_method']))
                        <div class="text-sm text-gray-700">
                            <span class="font-semibold">Service:</span>
                            <span>{{ ucfirst($item['service_method']) }}</span>
                        </div>
                    @endif --}}

                    @if (!empty($item['store_name']))
                        <div class="text-sm text-gray-700">
                            <span class="font-semibold">Store:</span>
                            <span>{{ ucfirst($item['store_name']) }}</span>
                        </div>
                    @endif

                    @if (!empty($item['distance_range']))
                        <div class="text-sm text-gray-700">
                            <span class="font-semibold">Distance Range:</span>
                            <span>{{ ucfirst($item['distance_range']) }}</span>
                        </div>
                    @endif

                    @if (!empty($item['service_option']))
                        <div class="flex flex-col gap-y-1">
                            <div class="text-sm font-semibold text-gray-800">
                                Delivery:
                                <span class="text-black">
                                    +{{ App\Helpers\CustomHelper::formatCurrency($item['service_option_price']) }}
                                </span>
                            </div>
                            <ul class="ml-3 text-sm text-gray-500 list-disc list-inside">
                                <li>{{ $item['service_option'] }}</li>
                            </ul>
                        </div>
                    @endif

                    {{-- Option/addon breakdown --}}
                    @php
                        $optionsTotal = 0;
                        $optionNames = [];
                        // Rental items (always x1)
                        foreach ($item['product_rental_items_prices'] ?? [] as $rentalKey => $optPrice) {
                            $optionsTotal += $optPrice ?? 0;
                            $name = ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey)));
                            // Always x1 for rental items
                            $optionNames[] = $name .' '. App\Helpers\CustomHelper::formatCurrency($optPrice).' (x1)';
                        }

                        // Product options (x{quantity} if charged == Unlimited, else no multiplier)
                        foreach ($item['product_option_items'] ?? [] as $opt) {
                            $optionsTotal += $opt['price'] ?? 0;
                            if (!empty($opt['name'])) {
                                $multiplier = ' '.  App\Helpers\CustomHelper::formatCurrency($opt['price']).' (x1)';
                                if (!empty($opt['charged']) && $opt['charged'] === 'Unlimited') {
                                    $multiplier = ' '. App\Helpers\CustomHelper::formatCurrency($opt['price']).' (x' . ($item['quantity'] ?? 1) .')';
                                }
                                $optionNames[] = $opt['name'].$multiplier;
                            }
                        }
                    @endphp

                    @if ($optionsTotal > 0)
                        <div class="flex flex-col gap-y-1">
                            <div class="text-sm font-semibold text-gray-800">
                                Options:
                                <span class="text-black">
                                    +{{ App\Helpers\CustomHelper::formatCurrency($optionsTotal) }}
                                </span>
                            </div>
                            <ul class="ml-3 text-sm text-gray-500 list-disc list-inside">
                                @foreach ($optionNames as $optName)
                                    <li>{{ $optName }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="text-xs flex items-center gap-x-2">
                        <a href="javascript:void(0)" class="text-red-500 hover:text-red-700 font-medium remove-cart-item"
                            data-remove-uid="{{ $item['product_unique_id'] }}">
                            Remove
                        </a>
                        <span class="text-gray-300">|</span>
                        <a href="{{ route('front.products.details', ['slug' => $item['product_slug'], 'productVariant' => $item['product_variant']]) }}" class="text-blue-500 hover:text-blue-700 font-medium">
                            Update
                        </a>
                    </div>
                </div>

            </div>
            @if (!$loop->last)
                <hr class="my-3 border-gray-100">
            @endif
        @endforeach

        <div class="space-y-1 text-sm mt-4 border-t pt-4">
            <div class="flex justify-between">
                <span class="text-gray-600">Sub Total:</span>
                <span class="font-medium">{{ App\Helpers\CustomHelper::formatCurrency($cart['sub_total'] ?? 0) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Tax:</span>
                <span class="font-medium">{{ App\Helpers\CustomHelper::formatCurrency($cart['tax_total'] ?? 0) }}</span>
            </div>
            <div class="flex justify-between text-lg font-bold">
                <span>Total:</span>
                <span>{{ App\Helpers\CustomHelper::formatCurrency($cart['grand_total'] ?? 0) }}</span>
            </div>
        </div>

        <a href="{{ route('front.checkout.index') }}"
            class="block w-full mt-4 bg-yellow-400 hover:bg-yellow-500 text-black font-bold py-3 rounded transition-all text-center">
            CHECKOUT
        </a>
    @else
        <p class="text-center py-10 text-gray-600">Your cart is empty.</p>
    @endif

</div>
