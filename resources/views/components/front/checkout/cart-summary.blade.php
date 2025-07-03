<div>
    @if (!empty($cart['cart_data']))
        @foreach ($cart['cart_data'] as $item)
            <div class="border-b pb-4 mb-6">
                <div class="flex">
                    <div class="w-1/3">
                        <div class="border rounded relative">
                            <img src="{{ $item['product_image_url'] }}" alt="Product" class="object-cover rounded">
                            <span
                                class="bg-yellow-400 w-[22px] h-[22px] text-black rounded-full absolute -top-2 -right-[8px] text-[14px] text-center font-bold">
                                {{ $item['quantity'] ?? 1 }}
                            </span>
                        </div>
                    </div>
                    <div class="ml-4 w-2/3">
                        <h4 class="text-sm flex items-center gap-2">
                            {{ $item['product_name'] }}
                            @if ($item['product_type'] === 'Rental' && !empty($item['product_variant']))
                                <span class="text-xs font-normal">
                                    - {{ ucfirst($item['product_variant']) }}
                                </span>
                            @endif
                            <span
                                class="font-bold ml-auto">{{ App\Helpers\CustomHelper::formatCurrency($item['product_price'] ?? 0) }}</span>
                        </h4>
                        @if (!empty($item['store_address']))
                            <div class="flex flex-col gap-y-1">
                                <div class="text-xs underline">
                                    Store:
                                </div>
                                <ul class="flex flex-col">
                                    <li class="text-xs before:content-['-'] before:pr-1">
                                        {{ ucfirst($item['store_address']) }}</li>
                                </ul>
                            </div>
                        @endif

                        @if (!empty($item['distance_range']))
                            <div class="flex flex-col gap-y-1">
                                <div class="text-xs underline">
                                    Distance Range:
                                </div>
                                <ul class="flex flex-col">
                                    <li class="text-xs before:content-['-'] before:pr-1">
                                        {{ ucfirst($item['distance_range']) }}</li>
                                </ul>
                            </div>
                        @endif

                        @if (!empty($item['service_option']))
                            <div class="flex flex-col gap-y-1">
                                <div class="text-xs flex justify-between items-center">
                                    <span class="underline">Delivery:</span>
                                    <span class="text-black font-bold">
                                        +{{ App\Helpers\CustomHelper::formatCurrency($item['service_option_price']) }}
                                    </span>
                                </div>
                                <ul class="flex flex-col">
                                    <li class="text-xs before:content-['-'] before:pr-1">{{ $item['service_option'] }}
                                    </li>
                                </ul>
                            </div>
                        @endif

                        @php
                            $optionsTotal = 0;
                            $optionRows = [];
                            // Rental items (always x1)
                            foreach ($item['product_rental_items_prices'] ?? [] as $rentalKey => $optPrice) {
                                $optionsTotal += $optPrice ?? 0;
                                $name = ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey)));
                                $optionRows[] = [
                                    'name' =>
                                        $name . ' (' . App\Helpers\CustomHelper::formatCurrency($optPrice) . 'x1)',
                                    'price' => App\Helpers\CustomHelper::formatCurrency($optPrice),
                                ];
                            }

                            // Product options (x{quantity} if charged == Unlimited, else x1)
                            foreach ($item['product_option_items'] ?? [] as $opt) {
                                $optionsTotal += $opt['price'] ?? 0;
                                if (!empty($opt['name'])) {
                                    $multiplier =
                                        ' (' . App\Helpers\CustomHelper::formatCurrency($opt['price']) . 'x1)';
                                    if (!empty($opt['charged']) && $opt['charged'] === 'Unlimited') {
                                        $multiplier =
                                            ' (' .
                                            App\Helpers\CustomHelper::formatCurrency($opt['price']) .
                                            'x' .
                                            ($item['quantity'] ?? 1) .
                                            ')';
                                        $opt['price'] = $opt['price'] * ($item['quantity'] ?? 1);
                                    }
                                    $optionRows[] = [
                                        'name' => $opt['name'] . $multiplier,
                                        'price' => App\Helpers\CustomHelper::formatCurrency($opt['price']),
                                    ];
                                }
                            }
                        @endphp
                        <h5 class="text-sm underline">Options:</h5>
                        {{-- <div class="flex justify-between items-center">
                            <h5 class="text-sm">Options Total:</h5>
                            <span class="text-sm font-bold">{{ App\Helpers\CustomHelper::formatCurrency($optionsTotal) }}</span>
                        </div> --}}
                        <ul class="flex flex-col">
                            @foreach ($optionRows as $row)
                                <li class="flex justify-between leading-[16px]">
                                    <span class="text-xs before:content-['-'] before:pr-1">{{ $row['name'] }}</span>
                                    <span class="text-xs font-bold">+ {{ $row['price'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <h4 class="mt-3 text-[15px]">Schedule Date: {{ $item['schedule_start_date'] ?? '-' }}</h4>
            </div>
        @endforeach

        <div class="border-b pb-6">
            <ul class="flex flex-col">
                <li class="flex justify-between mb-1">
                    <span>Subtotal:</span>
                    <span class="font-bold">+
                        {{ App\Helpers\CustomHelper::formatCurrency($cart['sub_total'] ?? 0) }}</span>
                </li>
                <li class="flex justify-between mb-1">
                    <span>Tax</span>
                    <span class="font-bold">+
                        {{ App\Helpers\CustomHelper::formatCurrency($cart['tax_total'] ?? 0) }}</span>
                </li>
                <li class="flex justify-between mb-1">
                    <span class="font-bold">Total</span>
                    <span class="font-bold">+
                        {{ App\Helpers\CustomHelper::formatCurrency($cart['grand_total'] ?? 0) }}</span>
                </li>
            </ul>
        </div>
    @else
        <div class="border-b pb-4 mb-6">
            <h2 class="text-2xl font-bold mb-2">Cart Summary</h2>
            <p class="text-sm text-gray-600">Review your items before proceeding to checkout.</p>
        </div>
        <div class="border-b pb-6">
            <ul class="flex flex-col">
                <li class="flex justify-between mb-1">
                    <span class="">Subtotal:</span>
                    <span class=" font-bold">+ $0.00</span>
                </li>
                <li class="flex justify-between mb-1">
                    <span class="">Tax</span>
                    <span class=" font-bold">+ $0.00</span>
                </li>
                <li class="flex justify-between mb-1">
                    <span class=" font-bold">Total</span>
                    <span class=" font-bold">+ $0.00</span>
                </li>
            </ul>
        </div>
    @endif
</div>
