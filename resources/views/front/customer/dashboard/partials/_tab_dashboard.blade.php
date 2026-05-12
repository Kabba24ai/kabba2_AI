                    <div class="bg-white mt-6 rounded-md shadow-sm p-6
                            flex flex-col md:flex-row gap-6
                            border border-gray-200">
                        <!-- Left: Name and Account -->
                        <div class="text-left w-full md:w-1/3">
                            <h2 class="text-2xl font-bold text-gray-900">{{ $customer->full_name }}</h2>
                            <p class="text-sm text-gray-600">Account: {{ $customer->unique_id }}</p>



                        </div>

                        <!-- Center: Tax Status (Responsive) -->
                        <div class="w-full md:w-1/4 text-left md:text-center">
                            <p class="text-xs tracking-wide text-gray-500 font-medium mb-1">Tax Status</p>
                            <div class="inline-flex items-center gap-2">
                                <span class="inline-flex items-center px-2 gap-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">

                                    @if ($customer->tax_status === 'Exempt')
                                    <x-heroicon-o-shield-check class="w-5 h-5 text-green-500" />
                                    @else
                                    <x-heroicon-o-shield-check class="w-5 h-5 text-gray-500" />
                                    @endif
                                    {{ $customer->tax_status }}

                                </span>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Valid until {{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) ?? 'N/A' }}</p>
                        </div>

                        @php
                        $approved = $customer->is_credit_account == 1;
                        $hasCreditLimit = !empty($customer->credit_limit);
                        @endphp

                        <!-- Right: Status + Button -->

                        <div class="w-full md:w-5/12 flex flex-col gap-2 text-right items-end">


                           <div class="flex items-center gap-2">

                                <div>

                                       @if (!empty($customer->phone))
                                        <p class="text-xs text-gray-500 font-medium">Phone Number</p>
                                       <p class="text-sm text-gray-900">
                                            {{ App\Helpers\CustomHelper::formatPhone($customer->phone) }}
                                      </p>
                                    @else
                                        <a href="javascript:void(0)"
                                        onclick="OpenCustomerEditModal()"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                            + Add Phone Number Now
                                        </a>
                                    @endif


                                </div>
                            </div>





                            <div class="text-sm text-gray-700 space-y-4">
                                <hr class="border-gray-200">

                                @php
                                    $primaryAddresses = $customer->addresses->where('is_primary', 1);
                                @endphp

                                @if ($primaryAddresses->isNotEmpty())
                                    @foreach ($primaryAddresses as $addresse)
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 mb-1">
                                                {{ $addresse->type === 'Shipping' ? 'Delivery' : $addresse->type }} Address
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

                                           @if (!empty($parts))
                                                @if ($addresse->type === 'Shipping' && ($customer->same_as_billing ?? false))
                                                    <p class="text-gray-500 italic">
                                                        Same as Billing Address
                                                    </p>
                                                @elseif (!empty($parts))
                                                    <p>
                                                        {{ $addresse->address }}, {{ $addresse->city }}<br>
                                                        {{ $addresse->state?->name }}, {{ $addresse->zip_code }}, {{ $addresse->country }}
                                                    </p>
                                                 @endif
                                            @else
                                                <a href="javascript:void(0)"
                                                onclick="OpenCustomerEditModal()"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                    + Add {{ $addresse->type === 'Shipping' ? 'Delivery' : $addresse->type }} Address Now
                                                </a>
                                            @endif
                                        </div>
                                        <hr class="border-gray-200">
                                    @endforeach
                                @else
                                    <div class="text-right text-xs text-gray-500">

                                        <a href="javascript:void(0)"
                                        onclick="OpenCustomerEditModal()"
                                        class="text-blue-600 hover:underline font-medium">
                                            + Add Address Now
                                        </a>
                                    </div>
                                @endif
                            </div>



                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4  mx-auto mt-6">
                        <!-- Current Balance -->
                        <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4 border border-gray-200">
                            <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                                <!-- Dollar Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6">
                                    <line x1="12" x2="12" y1="2" y2="22"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Current Balance</p>
                                <p class="text-xl font-semibold text-gray-900"> {{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance ) }}
                                </p>
                            </div>
                        </div>

                        <!-- Available Credit -->
                        <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4 border border-gray-200">
                            <div class="bg-green-100 text-green-600 rounded-md p-2">
                                <!-- Trending Up Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-6 h-6">
                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                    <polyline points="16 7 22 7 22 13"></polyline>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Available Credit</p>
                                <p class="text-xl font-semibold text-gray-900"> {{ \App\Helpers\CustomHelper::formatCurrency(\App\Helpers\CustomHelper::getAvailableCredit($customer)) }}</p>
                            </div>
                        </div>

                        <!-- Open Invoices -->
                        <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4 border border-gray-200">
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
                                <p class="text-sm text-gray-500">Open Invoices</p>
                                <p class="text-xl font-semibold text-gray-900">{{ $customer->unpaid_invoices_count  }}</p>
                            </div>
                        </div>

                        <!-- Last Payment -->
                        <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4 border border-gray-200">
                            <div class="bg-green-100 text-green-600 rounded-md p-2">
                                <!-- Calendar Icon -->
                                <x-heroicon-o-calendar class="w-6 h-6 text-green-500" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Last Payment</p>
                                <p class="text-xl font-semibold text-gray-900">{{ App\Helpers\CustomHelper::formatDate($lastpaymentdate) ?? 'N/A' }} </p>
                            </div>
                        </div>
                    </div>

                    <div class=" mx-auto bg-white rounded-md shadow-sm border border-gray-200 mt-6 mb-6">
                        <div class="flex items-center justify-between p-4 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-gray-800">Recent Orders</h2>
                            <a href="#" class="text-sm text-blue-600" id="viewAllOrdersLink">View All</a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left text-gray-700">
                                <thead class="bg-gray-50 text-gray-500 text-xs border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 font-medium uppercase">Order ID</th>
                                        <th class="px-4 py-3 font-medium uppercase">PO#</th>

                                        <th class="px-4 py-3 font-medium uppercase">Product Name</th>
                                        <th class="px-4 py-3 font-medium uppercase">Amount</th>
                                        <th class="px-4 py-3 font-medium uppercase">Payment Method</th>
                                        <th class="px-4 py-3 font-medium uppercase">Status</th>
                                        <th class="px-4 py-3 font-medium uppercase">Created</th>
                                        <th class="px-4 py-3 font-medium uppercase">Action</th>
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

                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">


                                              <a href="{{ route('front.customer.dashboard.order.view', $order->unique_id) }}" class="text-brand-500 underline font-bold">{{$order->order_number}}</a>
                                        </td>
                                        <td class="px-4 py-3">{{ $product->po_id }}</td>

                                        <td class="px-4 py-3">{{ $product->product_name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3"> {{ \App\Helpers\CustomHelper::formatCurrency($product->total ) }}</td>
                                        <td class="px-4 py-3"> {{ $order?->last_payment_type?->label() ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <!-- <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">paid</span> -->

                                            {!! \App\Helpers\CustomHelper::statusBadge($order->last_payment_status) !!}


                                        </td>
                                        <td class="px-4 py-3"> {{ App\Helpers\CustomHelper::formatDate($order->order_date) ?? 'N/A' }} </td>
                                        <td class="px-4 py-3 whitespace-nowrap items-center text-blue-600 flex">
                                            <a href="{{ route('front.customer.dashboard.order.view', $order->unique_id) }}"
                                                class="text-blue-600 inline-flex items-center">
                                                <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                                            </a>
                                            <!-- Download -->
                                             <a href="{{ route('front.customer.dashboard.order.receipt-download', $order->unique_id) }}" title="Download">
                                                <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 cursor-pointer" />
                                             </a>

                                             <!-- Download -->

                                        </td>
                                    </tr>

                                    @endforeach
                                    @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                                            No orders found.
                                        </td>
                                    </tr>
                                    @endforelse

                                </tbody>
                            </table>
                        </div>
                    </div>
