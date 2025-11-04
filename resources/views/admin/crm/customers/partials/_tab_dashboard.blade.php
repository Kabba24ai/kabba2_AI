<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <!-- Left Card -->
    <div class="bg-white rounded-md border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

            <!-- Left: Customer Info -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $customer->full_name }}</h2>
                <p class="text-sm text-gray-500 mt-1">Account: {{ $customer->unique_id }}</p>
            </div>

            <!-- Right: Tax Status -->
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-1">Tax Status</p>
                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border border-green-200 bg-green-50 text-green-700">
                    @if ($customer->tax_status === 'Exempt')
                    <x-heroicon-o-shield-check class="w-5 h-5 text-green-500" />
                    @else
                    <x-heroicon-o-shield-check class="w-5 h-5 text-gray-500" />
                    @endif
                    {{ $customer->tax_status }}
                </div>
                <p class="text-xs text-gray-400 mt-1">Valid until N/A</p>
            </div>
        </div>
    </div>

    <!-- Right Card -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <!-- Assign to Funnels -->
        <div class="md:col-span-2 flex flex-col flex-grow">
            <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Funnels</label>
            <div
                class="border border-gray-300 rounded-md p-5 flex-grow flex items-center justify-center text-gray-400 text-sm bg-gray-50 transition-all">
                Add or assign funnels here
            </div>
        </div>
    </div>
</div>

<div class="mx-auto mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
    <!-- Contact Information -->
    <div class="bg-white rounded-md shadow-sm p-5">
        <h3 class="text-base font-semibold text-gray-900 flex items-center gap-1 mb-4">
            <svg class="w-5 h-5 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path>
            </svg>
            Contact Information
        </h3>
        <div class="space-y-4 text-sm text-gray-700">
            <div class="flex items-center gap-1 ">
                <svg class="w-4 h-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                </svg>
                <div>
                    <p class="text-sm text-gray-900"> {{ $customer->email ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 font-medium">Email Address</p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <svg class="w-4 h-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                </svg>
                <div>
                    <p class="text-sm text-gray-900">{{ App\Helpers\CustomHelper::formatPhone($customer->phone) ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 font-medium">Personal Phone</p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <svg class="w-4 h-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"></path>
                </svg>
                <div>
                    <p class="text-sm text-gray-900">{{ App\Helpers\CustomHelper::formatPhone($customer->company_phone) ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 font-medium">Company Phone</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Address Information -->
    <div class="bg-white rounded-md shadow-sm p-5  overflow-y-scroll overflow-x-hidden">
        <h3 class="text-base font-semibold text-gray-800 flex items-center gap-1 mb-4">
            <svg class="w-5 h-5 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"></path>
            </svg>
            Address Information
        </h3>
        <div class="text-sm text-gray-700 space-y-4">

            @php
            $billingAddress = $customer->addresses->firstWhere('type', 'billing');
            $deliveryAddress = $customer->addresses->firstWhere('type', 'delivery');
            @endphp



            @foreach ($customer->addresses->where('is_primary', 1) as $addresse)
            <div>
                <p class="text-xs font-medium text-gray-500 mb-1">
                   {{ ($addresse->type=='Shipping' ? 'Delivery' : $addresse->type) }} Address
                    <span class="ml-2 inline-block bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">
                        Default Address
                    </span>
                </p>
                @php
                // Collect all non-empty fields except country
                $mainParts = array_filter([
                $addresse->address,
                $addresse->city,
                $addresse->state?->name,
                $addresse->zip_code,
                ]);

                // Add country only if there is at least one other part
                $parts = $mainParts;
                if (!empty($mainParts) && !empty($addresse->country)) {
                $parts[] = $addresse->country;
                }
                @endphp

                <p>{{ implode(', ', $parts) ?: 'No address provided.' }}</p>

            </div>
            <hr class="border-gray-200">
            @endforeach

        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col">
        <div class="flex items-center justify-between mb-2">
            <label for="ContactTags" class="block text-sm font-medium text-gray-700">Tags</label>
        </div>
        <div class="flex flex-wrap gap-2">

            @foreach ($customer->tag_objects as $tag)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 mr-1">
                    <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                    <path d="M7 7h.01"></path>
                </svg>
                #{{ $tag->name }}
            </span>
            @endforeach


        </div>
    </div>

    <!-- Notes Section -->
    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col">
        <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium text-gray-700">Notes</label>
        </div>

        <!-- Notes List -->
        <div id="noteListContainer" class="p-2 max-h-60 overflow-y-auto">
            <ul id="" class="list-disc text-sm text-gray-700 space-y-1 pl-3 ">
                @foreach ($customer->notes as $note)

                <li class="list-disc border-b border-gray-200 pb-3" data-id="5">
                    <div class="flex justify-between items-start">
                        <div class="flex-1 pr-3">
                            <p class="text-sm text-gray-800 leading-relaxed font-semibold">{{ $note->description }} </p>
                            <div class="mt-1 text-xs text-gray-500 space-y-1">
                                <div>Created {{ $note->created_date }} {{ $note->created_time }} by <span class="font-semibold">{{ $note->user->full_name }}</span></div>
                                <div>Updated {{ $note->updated_at }} </div>
                            </div>
                        </div>
                    </div>
                </li>
                @endforeach

            </ul>
        </div>
    </div>
</div>

<!-- <div class="bg-white rounded-md shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 mt-6">
    <div class="text-left">
        <h2 class="text-2xl font-bold text-gray-900">{{ $customer->full_name }}</h2>
        <p class="text-sm text-gray-600">Account: {{ $customer->unique_id }}</p>
    </div>

    <div class="text-left md:text-center w-full md:w-auto">
        <p class="text-xs tracking-wide text-gray-500 font-medium mb-1">Tax Status</p>
        <div class="inline-flex items-center gap-2">
            <span class="text-xs font-medium rounded-full bg-green-100 text-green-800">
                <span class="leading-[1.2] inline-flex items-center gap-2 px-2 py-1">
                    @if ($customer->tax_status === 'Exempt')
                    <x-heroicon-o-shield-check class="w-5 h-5 text-green-500" />
                    @else
                    <x-heroicon-o-shield-check class="w-5 h-5 text-gray-500" />
                    @endif
                    {{ $customer->tax_status }}
                </span>



            </span>
        </div>
        <p class="text-xs text-gray-500 mt-1">Valid until {{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) ?? 'N/A' }} </p>
    </div>

    @php
    $approved = $customer->is_credit_account == 1;
    $hasCreditLimit = !empty($customer->credit_limit);
    @endphp

    <div class="flex flex-col items-start md:items-end gap-2 text-left md:text-right w-auto">

        @if ($approved && $hasCreditLimit)
        <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 w-auto">
            Good Standing
        </span>

        @elseif ($approved && !$hasCreditLimit)

        <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 w-auto">
            Pending
        </span>

        @else

        @endif


    </div>
</div> -->

<!-- <div class="mx-auto mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    
    <div class="bg-white rounded-md shadow-sm p-5">
        <h3 class="text-base font-semibold text-gray-900 flex items-center gap-1 mb-4">
            <x-heroicon-o-user class="w-5 h-5 text-gray-900" />
            Contact Information
        </h3>
        <div class="space-y-4 text-sm text-gray-700">
            <div class="flex items-center gap-1 ">
                <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                <div>
                    <p class="text-sm text-gray-900"> {{ $customer->email ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 font-medium">Email Address</p>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                <div>
                    <p class="text-sm text-gray-900">{{ App\Helpers\CustomHelper::formatPhone($customer->phone) ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 font-medium">Personal Phone</p>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <x-heroicon-o-device-phone-mobile class="w-4 h-4 text-gray-900" />
                <div>
                    <p class="text-sm text-gray-900">{{ App\Helpers\CustomHelper::formatPhone($customer->company_phone) ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 font-medium">Company Phone</p>
                </div>
            </div>
        </div>
    </div>

  
    <div class="bg-white rounded-md shadow-sm p-5 h-[250px] overflow-y-scroll overflow-x-hidden">
        <h3 class="text-base font-semibold text-gray-800 flex items-center gap-1 mb-4">
            <x-heroicon-o-map-pin class="w-5 h-5 text-gray-900" />
            Address Information
        </h3>

        <div class="text-sm text-gray-700 space-y-4">
            @php
            $billingAddress = $customer->addresses->firstWhere('type', 'billing');
            $deliveryAddress = $customer->addresses->firstWhere('type', 'delivery');
            @endphp



            @foreach ($customer->addresses->where('is_primary', 1) as $addresse)
            <div>
                <p class="text-xs font-medium text-gray-500 mb-1">
                    {{ ucfirst($addresse->type) }} Address
                    <span class="ml-2 inline-block bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">
                        Default Address
                    </span>
                </p>
                @php
              
                $mainParts = array_filter([
                $addresse->address,
                $addresse->city,
                $addresse->state?->name,
                $addresse->zip_code,
                ]);

               
                $parts = $mainParts;
                if (!empty($mainParts) && !empty($addresse->country)) {
                $parts[] = $addresse->country;
                }
                @endphp

                <p>{{ implode(', ', $parts) ?: 'No address provided.' }}</p>

            </div>
            <hr class="border-gray-200">
            @endforeach

        </div>
    </div>
</div> -->

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4  mx-auto mt-6">
    <!-- Current Balance -->
    <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
        <div class="bg-blue-100 text-blue-600 rounded-md p-2">
            <!-- Dollar Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6">
                <line x1="12" x2="12" y1="2" y2="22"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Current Balance</p>
            <p class="text-xl font-semibold text-gray-900">

                {{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance ) }}


                <!-- {{ config('app.currency.code') }}{{ $customer->available_credit_balance ?? 0 }} -->

            </p>
        </div>
    </div>

    <!-- Available Credit -->
    <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
        <div class="bg-green-100 text-green-600 rounded-md p-2">
            <!-- Trending Up Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-6 h-6">
                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                <polyline points="16 7 22 7 22 13"></polyline>
            </svg>
        </div>
        <div>
            <p class="text-sm  text-gray-500">Available Credit</p>
            <p class="text-xl font-semibold text-gray-900">

                <!-- {{ config('app.currency.code') }}{{ number_format(($customer->credit_limit ?? 0) - ($customer->total_account_order_amount ?? 0), 2) }}  -->

                {{ \App\Helpers\CustomHelper::formatCurrency(\App\Helpers\CustomHelper::getAvailableCredit($customer)) }}


            </p>
        </div>
    </div>

    <!-- Open Invoices -->
    <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
        <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
            <!-- Document Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-6 h-6">
                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                <path d="M10 9H8"></path>
                <path d="M16 13H8"></path>
                <path d="M16 17H8"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm  text-gray-500">Open Invoices</p>
            <p class="text-xl font-semibold text-gray-900">{{ $customer->unpaid_invoices_count  }}</p>
        </div>
    </div>

    <!-- Last Payment -->
    <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
        <div class="bg-green-100 text-green-600 rounded-md p-2">
            <!-- Calendar Icon -->
            <x-heroicon-o-calendar class="w-6 h-6 text-green-500" />
        </div>
        <div>
            <p class="text-sm  text-gray-500">Last Payment</p>
            <p class="text-xl font-semibold text-gray-900">{{ App\Helpers\CustomHelper::formatDate($lastpaymentdate) ?? 'N/A' }} </p>
        </div>
    </div>
</div>

<div class=" mx-auto  bg-white rounded-md shadow-sm mt-6">
    <div class="flex items-center p-4 justify-between border-b border-gray-200">
        <h2 class="text-base font-semibold text-gray-900">Recent Orders</h2>
        <a href="#" class="text-sm text-blue-600" id="viewAllOrdersLink">View All</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
            <thead class="border-b bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Order ID</th>
                    <th class="px-4 py-3">Product Name</th>
                    <th class="px-4 w-32 py-3">Amount</th>
                    <th class="px-4 w-32 py-3 text-right">Payment Methods</th>
                    <th class="px-4 w-32 py-3 text-right">Status</th>
                    <th class="px-4 w-32 py-3 text-right">Created</th>
                    <th class="px-4 w-24 py-3 text-end">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @php $rowCount = 0; @endphp

                @forelse ($customer->orders->sortByDesc('order_date') as $order)
                @foreach ($order->products as $product)

                @if ($rowCount >= 5)
                @break(2)
                @endif

                @php $rowCount++; @endphp

                <tr class="hover:bg-gray-50" data-status="{{ strtolower($order->status) }}">
                    {{-- Order Number --}}
                    <td class="px-4 py-3 font-medium text-gray-900">
                        {{ $order->order_number }}
                    </td>

                    {{-- Product Name --}}
                    <td class="px-4 py-3 truncate min-w-3xs max-w-3xs ">
                        {{ $product->product_name ?? 'N/A' }}
                    </td>

                    {{-- Total (for this product) --}}
                    <td class="px-4 py-3">
                        <!-- {{ config('app.currency.code') }}{{ number_format($product->total, 2) }} -->

                        {{ \App\Helpers\CustomHelper::formatCurrency($product->total ) }}


                    </td>

                    {{-- Payment Type --}}
                    <td class="px-4 py-3 text-right">
                        {{ $order->last_payment_type->value === 'Cheque' ? 'Check' : $order->last_payment_type->value }}
                    </td>

                    {{-- Status Badge --}}
                    <td class="px-4 py-3 text-right">


                        {!! \App\Helpers\CustomHelper::statusBadge($order->last_payment_status) !!}


                    </td>

                    {{-- Order Date --}}
                    <td class="px-4 py-3 text-right">
                        {{ App\Helpers\CustomHelper::formatDate($order->order_date) ?? 'N/A' }}
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3 ">
                        <div class="flex gap-2 items-center justify-end">
                            <a href="{{ route('admin.order-management.orders.edit', $order->unique_id) }}" target="_blank" title="View">
                                <x-heroicon-o-eye class="w-5 h-5 text-blue-600" />
                            </a>
                            <a href="javascript:void(0)" title="Download">
                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600" />
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-4 text-center text-gray-500">
                        No orders found.
                    </td>
                </tr>
                @endforelse


            </tbody>
        </table>
    </div>
</div>