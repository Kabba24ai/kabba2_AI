@extends('admin.layouts.app')

@section('title', 'Reorder - ' . $order->order_number)

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90 capitalize">{{ $type }} order -
            {{ $order->order_number }}</h3>
        </h3>
    </div>

    @include('flash::message')
    @include('admin.partials.formErrors')
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-4 py-4">

                    <!-- Product Form -->
                    {{ html()->form()->attributes([
                            'action' => '#',
                            'id' => 'reorderForm',
                            'method' => 'POST',
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->open() }}

                    <div class="grid grid-cols-1 lg:grid-cols-8 gap-6">
                        <!-- Order Type -->
                        <div>
                            <label for="order_type"
                                class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
                                Order Type
                            </label>
                            {!! html()->text('order_type', ucWords($type))->class([
                                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                    'border-gray-300' => !$errors->has('order_type'),
                                    'border-red-500' => $errors->has('order_type'),
                                ])->attributes([
                                    'maxlength' => 240,
                                    'data-parsley-maxlength' => 240,
                                    'placeholder' => 'Enter Order Type',
                                    'autocomplete' => 'off',
                                    'id' => 'order_type',
                                    'readonly' => true,
                                ])->required() !!}
                            @error('order_type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <!-- Customer Name -->
                        <div>
                            <label for="customer_name"
                                class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
                                Customer Name
                            </label>
                            {!! html()->text('customer_name', $order->shippingAddress->full_name)->class([
                                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                    'border-gray-300' => !$errors->has('customer_name'),
                                    'border-red-500' => $errors->has('customer_name'),
                                ])->attributes([
                                    'maxlength' => 240,
                                    'data-parsley-maxlength' => 240,
                                    'placeholder' => 'Enter Customer Name',
                                    'autocomplete' => 'off',
                                    'id' => 'customer_name',
                                    'readonly' => true,
                                ])->required() !!}
                            @error('customer_name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <!-- Keep on Schedule -->
                        <div>
                            <label for="keep_on_schedule"
                                class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
                                Keep on Schedule
                            </label>
                            {!! html()->select('keep_on_schedule', ['Yes' => 'Yes', 'No' => 'No'])->class([
                                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                    'border-gray-300' => !$errors->has('keep_on_schedule'),
                                    'border-red-500' => $errors->has('keep_on_schedule'),
                                ])->required() !!}
                            @error('keep_on_schedule')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <!-- Payment Method -->
                        <div>
                            <label for="payment_method"
                                class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
                                Payment Method
                            </label>
                            {!! html()->select('payment_method', ['Card' => 'Credit Card', 'COD' => 'POD', 'Account' => 'Account'])->class([
                                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                    'border-gray-300' => !$errors->has('payment_method'),
                                    'border-red-500' => $errors->has('payment_method'),
                                ])->required() !!}
                            @error('payment_method')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <!-- Customer Cards -->
                        <div id="customer_cards_div">
                            <label for="customer_cards"
                                class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
                                Customer Cards
                            </label>
                            {!! html()->select('customer_cards', $customerCards)->class([
                                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                    'border-gray-300' => !$errors->has('customer_cards'),
                                    'border-red-500' => $errors->has('customer_cards'),
                                ])->required() !!}
                            @error('customer_cards')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>


                    <!-- Product Search -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                        <div>
                            <label for="product_search"
                                class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">
                                Search & Add Product
                            </label>
                            <div class="flex gap-2">
                                <input type="text" id="product_search"
                                    class="w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white border-gray-300"
                                    placeholder="Type product name or SKU..." autocomplete="off">
                                <button type="button" id="add_product_btn"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-semibold">Add</button>
                            </div>
                            <div id="product_search_results"
                                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 mt-2 rounded shadow hidden">
                            </div>
                        </div>
                    </div>

                    <!-- Order Products -->
                    <div class="mt-6">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Order Products</h4>
                        <div class="overflow-x-auto rounded-lg shadow">
                            <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <thead>
                                    <tr>
                                        <th class="text-center text-gray-600 dark:text-gray-300">Remove</th>
                                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Product</th>
                                        <th class="px-4 py-2 text-center text-gray-600 dark:text-gray-300">Quantity</th>
                                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">SubTotal</th>
                                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">New Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->products as $index => $product)
                                        <tr class="border-b border-gray-100 dark:border-gray-800">
                                            <!-- Remove Button -->
                                            <td class="text-center">
                                                <button type="button"
                                                    class="text-red-500 hover:text-red-700 font-bold text-xl"
                                                    onclick="removeProductRow(this)" title="Remove">
                                                    <x-heroicon-o-trash class="w-5 h-5" />
                                                </button>
                                            </td>
                                            <!-- Product Info -->
                                            <td class="px-4 py-3 flex items-center gap-3">
                                                <img src="{{ $product->product->image_url }}"
                                                    alt="{{ $product->product_name }}"
                                                    class="w-12 h-12 rounded border object-cover" />
                                                <div>
                                                    <div class="font-semibold text-gray-800 dark:text-white">
                                                        {{ $product->product_name }}</div>
                                                    @if ($product->product_type === 'Rental' && !empty($product->product_variant))
                                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                                            - {{ ucfirst($product->product_variant) }}
                                                        </div>
                                                    @endif
                                                    @php
                                                        $optionsTotal = 0;
                                                        $optionRows = [];
                                                        // Rental items (always x1)
                                                        foreach (
                                                            $product['product_data']['product_rental_items_prices'] ??
                                                                []
                                                            as $rentalKey => $optPrice
                                                        ) {
                                                            $optionsTotal += $optPrice ?? 0;
                                                            $name = ucwords(
                                                                str_replace(
                                                                    '_',
                                                                    ' ',
                                                                    preg_replace('/^rental_/', '', $rentalKey),
                                                                ),
                                                            );
                                                            $optionRows[] = [
                                                                'name' =>
                                                                    $name .
                                                                    ' ' .
                                                                    App\Helpers\CustomHelper::formatCurrency(
                                                                        $optPrice,
                                                                    ) .
                                                                    ' (x1)',
                                                                'price' => App\Helpers\CustomHelper::formatCurrency(
                                                                    $optPrice,
                                                                ),
                                                            ];
                                                        }

                                                        // Product options (x{quantity} if charged == Unlimited, else x1)
                                                        foreach ($item['product_option_items'] ?? [] as $opt) {
                                                            $optionsTotal += $opt['price'] ?? 0;
                                                            if (!empty($opt['name'])) {
                                                                $multiplier =
                                                                    App\Helpers\CustomHelper::formatCurrency(
                                                                        $opt['price'],
                                                                    ) . '(x1)';
                                                                if (
                                                                    !empty($opt['charged']) &&
                                                                    $opt['charged'] === 'Unlimited'
                                                                ) {
                                                                    $multiplier =
                                                                        App\Helpers\CustomHelper::formatCurrency(
                                                                            $opt['price'],
                                                                        ) .
                                                                        ' (x' .
                                                                        ($item['quantity'] ?? 1) .
                                                                        ')';
                                                                    $opt['price'] =
                                                                        $opt['price'] * ($item['quantity'] ?? 1);
                                                                }
                                                                $optionRows[] = [
                                                                    'name' => $opt['name'] . ' ' . $multiplier,
                                                                    'price' => App\Helpers\CustomHelper::formatCurrency(
                                                                        $opt['price'],
                                                                    ),
                                                                ];
                                                            }
                                                        }
                                                    @endphp
                                                    <h5 class="text-sm underline">Options:</h5>
                                                    <ul class="flex flex-col">
                                                        @foreach ($optionRows as $row)
                                                            <li class="flex justify-between leading-[16px]">
                                                                <span
                                                                    class="text-xs before:content-['-'] before:pr-1">{{ $row['name'] }}</span>
                                                                <span class="text-xs font-bold">+
                                                                    {{ $row['price'] }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </td>
                                            <!-- Quantity -->
                                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-200">
                                                x{{ $product->quantity }}</td>
                                            <!-- Price Input -->
                                            <td class="px-4 py-3 text-right">
                                                {{ $product->price }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <input type="number" name="products[{{ $index }}][price]"
                                                    value="{{ old("products.$index.price", $product->price ?? 0) }}"
                                                    class="w-24 px-2 py-1 rounded border text-right dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                                    step="0.01" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4"
                                            class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">
                                            Subtotal</td>
                                        <td colspan="2"
                                            class="px-4 py-2 text-right font-semibold text-gray-800 dark:text-white">
                                            <span id="order_subtotal">
                                                {{ App\Helpers\CustomHelper::formatCurrency($order->products->sum(function ($p) {return ($p->price ?? 0) * ($p->quantity ?? 1);})) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4"
                                            class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Tax
                                        </td>
                                        <td colspan="2"
                                            class="px-4 py-2 text-right font-semibold text-gray-800 dark:text-white">
                                            <input type="text" name="tax" value="{{ old('tax', $order->tax ?? 0) }}"
                                                class="w-24 px-2 py-1 rounded border text-right dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                                step="0.01" id="order_tax" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4"
                                            class="px-4 py-2 text-right font-bold text-gray-900 dark:text-white text-lg">
                                            Grand Total</td>
                                        <td colspan="2"
                                            class="px-4 py-2 text-right font-bold text-gray-900 dark:text-white text-lg">
                                            <span id="order_grand_total">
                                                {{ App\Helpers\CustomHelper::formatCurrency(
                                                    ($order->products->sum(function ($p) {
                                                        return ($p->price ?? 0) * ($p->quantity ?? 1);
                                                    }) ??
                                                        0) +
                                                        ($order->tax ?? 0),
                                                ) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-center mt-8 space-x-4">
                        <!-- Save & Exit Button -->
                        <button type="submit" name="action" value="save_exit"
                            class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition">
                            Place Order & Exit
                            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 ml-2" />
                        </button>
                    </div>


                    {{ html()->form()->close() }}

                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethod = document.querySelector('select[name="payment_method"]');
            const customerCardsDiv = document.getElementById('customer_cards_div');
            const customerCardsSelect = document.querySelector('select[name="customer_cards"]');

            function toggleCustomerCards() {
                if (paymentMethod.value === 'Card') {
                    customerCardsDiv.classList.remove('opacity-50', 'pointer-events-none');
                    customerCardsSelect.disabled = false;
                } else {
                    customerCardsDiv.classList.add('opacity-50', 'pointer-events-none');
                    customerCardsSelect.disabled = true;
                    customerCardsSelect.selectedIndex = 0; // Select blank option
                }
            }

            paymentMethod.addEventListener('change', toggleCustomerCards);
            toggleCustomerCards();

            // Handle product search and addition
            const input = document.getElementById('product_search');
            const resultsBox = document.getElementById('product_search_results');
            let searchTimeout;

            // Helper to render products and their options
            function renderProductResults(products) {
                if (!products.length) {
                    resultsBox.innerHTML = '<div class="p-3 text-gray-400">No products found.</div>';
                    resultsBox.classList.remove('hidden');
                    return;
                }

                resultsBox.innerHTML = products.map(product => `
            <div class="border-b border-gray-100 dark:border-gray-700 last:border-0 p-3">
                <div class="font-medium text-gray-700 dark:text-gray-200">${product.product_name}</div>
                <div class="text-xs text-gray-500 mb-2">${product.unique_id}</div>
                ${product.options && product.options.length
                    ? `<div class="flex flex-wrap gap-2">
                            ${product.options.map(option => `
                            <label class="flex items-center gap-2">
                                <input type="checkbox" class="product-option-checkbox"
                                       data-product-id="${product.id}"
                                       data-option-id="${option.id}" />
                                <span>${option.name}</span>
                            </label>
                        `).join('')}
                        </div>`
                    : '<div class="text-xs text-gray-400">No options</div>'
                }
            </div>
        `).join('');
                resultsBox.classList.remove('hidden');
            }



            // Debounced product search
            input.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = input.value.trim();
                if (query.length < 2) {
                    resultsBox.classList.add('hidden');
                    return;
                }

                searchTimeout = setTimeout(async () => {
                    try {
                        const url = '{{ route('admin.product-management.products.search', [':search']) }}';
                        // Call your API endpoint here (adjust URL as needed)
                        const req = await fetch(
                            url.replace(':search', encodeURIComponent(query))
                        );

                        const res = await req.json();
                        // return matches.filter(p =>
                        //     !selectedProducts.some(sp => sp.id === p.id)
                        // );
                        // Assuming response res shape: { products: [ {id, name, sku, options: [{id, value}]} ] }
                        renderProductResults(res.products || []);
                    } catch (err) {
                        resultsBox.innerHTML =
                            '<div class="p-3 text-red-600">Search error.</div>';
                        resultsBox.classList.remove('hidden');
                    }
                }, 350); // 350ms debounce
            });

            // Optional: Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!resultsBox.contains(e.target) && e.target !== input) {
                    resultsBox.classList.add('hidden');
                }
            });

            // Example: Add product(s) when clicking "Add" button
            document.getElementById('add_product_btn').addEventListener('click', function() {
                const checked = resultsBox.querySelectorAll('.product-option-checkbox:checked');
                if (!checked.length) {
                    notyf.error('Please select at least one product option.');
                    return;
                }

                // Collect selected products/options
                const selections = Array.from(checked).map(cb => ({
                    product_id: cb.dataset.productId,
                    option_id: cb.dataset.optionId
                }));

                // You can now do something with `selections` (e.g., add to an order, show a summary, etc.)
                console.log('Selected:', selections);
                notyf.success('Product(s) added!');
                // Optionally clear/hide results:
                resultsBox.classList.add('hidden');
                input.value = '';
            });



        });

        function removeProductRow(btn) {
            if (confirm('Remove this product?')) {
                btn.closest('tr').remove();
            }
        }
    </script>
@endpush
