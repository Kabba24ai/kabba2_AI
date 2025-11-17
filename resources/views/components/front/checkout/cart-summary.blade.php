<div>
    @if (!empty($cart['cart_items']))
        @if (session()->has('order.type'))
            <div class="mb-4 p-3 bg-green-100 border border-green-300 rounded text-green-800">
                <strong class="capitalize">{{ session('order.type') }} Order from:</strong>
                <span>{{ session('order.number') }}</span>
                {{-- <small>
                    @if (session('order.type') === 'reference')
                        <span class="text-xs text-gray-700">(This is a reference order )</span>
                    @elseif (session('order.type') === 'new')
                        <span class="text-xs text-gray-700">(This is a new order created from the selected order number and will be considered as a new order)</span>
                    @endif
                </small> --}}
            </div>
        @endif
        @foreach ($cart['cart_items'] as $item)
            <div class="border-b pb-4 mb-6">
                <div class=" flex flex-col sm:flex-row items-start gap-3 py-2">
                    <!-- Image Column -->
                    <div class="relative" style="min-width:80px;max-width:80px;">
                        <img src="{{ $item['product_image_url'] }}" alt="Product"
                            class="w-20 h-20 rounded border object-cover" />
                        <span
                            class="bg-yellow-400 w-[22px] h-[22px] text-black rounded-full absolute -top-2 -right-[8px] text-[14px] text-center font-bold">
                            {{ $item['quantity'] ?? 1 }}
                        </span>
                    </div>

                    <div class="flex-1 min-w-0 flex flex-col justify-center w-full">
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
                        @if (!empty($item['store_name']))
                            <div class="flex flex-col gap-y-1">
                                <div class="text-xs underline">
                                    Store:
                                </div>
                                <ul class="flex flex-col">
                                    <li class="text-xs before:content-['-'] before:pr-1">
                                        {{ ucfirst($item['store_name']) }}</li>
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

                                $case = collect(\App\Enums\Products\ProductCustomStaticLabel::cases())->firstWhere('name', $rentalKey);
                                $name = $case?->value ?? ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey)));
                                $quantity = $case?->value ? $item['quantity'] : 1;;
                                $optionRows[] = [
                                    'name' =>
                                        $name . ' ' . App\Helpers\CustomHelper::formatCurrency($optPrice) . " (x{$quantity})",
                                    'price' => App\Helpers\CustomHelper::formatCurrency($optPrice * $quantity),
                                ];
                            }

                            // Product options (x{quantity} if charged == Unlimited, else x1)
                            foreach ($item['product_option_items'] ?? [] as $opt) {
                                $optionsTotal += $opt['price'] ?? 0;
                                if (!empty($opt['name'])) {
                                    $multiplier =
                                        App\Helpers\CustomHelper::formatCurrency($opt['price']) . '(x1)';
                                    if (!empty($opt['charged']) && $opt['charged'] === 'Unlimited') {
                                        $multiplier =
                                            App\Helpers\CustomHelper::formatCurrency($opt['price']) .
                                            ' (x'.($item['quantity'] ?? 1) .')';
                                        $opt['price'] = $opt['price'] * ($item['quantity'] ?? 1);
                                    }
                                    $optionRows[] = [
                                        'name' => $opt['name'] . ' ' . $multiplier,
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
                @if ($item['delivery_date'])
                    <h4 class="mt-3 text-[15px]">Schedule Date: {{ $item['delivery_date'] ?? '-' }}</h4>
                @endif
            </div>
        @endforeach

        <div class="border-b pb-6">
            <ul class="flex flex-col">
                <li class="flex justify-between mb-1">
                    <span>Subtotal:</span>
                    <span class="font-bold">{{ App\Helpers\CustomHelper::formatCurrency($cart['sub_total'] ?? 0) }}</span>
                </li>
                <li class="flex justify-between mb-1">
                    <span>Tax</span>
                    <span class="font-bold">{{ App\Helpers\CustomHelper::formatCurrency($cart['tax_total'] ?? 0) }}</span>
                </li>
                <li class="flex justify-between mb-1">
                    <span class="font-bold">Total</span>
                    <span class="font-bold">{{ App\Helpers\CustomHelper::formatCurrency($cart['grand_total'] ?? 0) }}</span>
                </li>
            </ul>
        </div>
    @else
        <div class="border-b pb-4 mb-6">
            <h2 class="text-2xl font-bold mb-2">Cart Summary</h2>
            <p class="text-sm text-gray-600">Please add items to your cart.</p>
        </div>
        <div class="border-b pb-6">
            <ul class="flex flex-col">
                <li class="flex justify-between mb-1">
                    <span class="">Subtotal:</span>
                    <span class=" font-bold">$0.00</span>
                </li>
                <li class="flex justify-between mb-1">
                    <span class="">Tax</span>
                    <span class=" font-bold">$0.00</span>
                </li>
                <li class="flex justify-between mb-1">
                    <span class=" font-bold">Total</span>
                    <span class=" font-bold">$0.00</span>
                </li>
            </ul>
        </div>
    @endif
</div>
