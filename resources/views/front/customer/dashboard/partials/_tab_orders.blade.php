   <div class="bg-white mt-6 rounded-md shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border border-gray-200">
                        <!-- Left: Name and Account -->
                        <div class="text-left ">
                            <h2 class="text-2xl font-bold text-gray-900">{{ $customer->full_name }}</h2>
                            <p class="text-sm text-gray-600">Account: {{ $customer->unique_id }}</p>
                        </div>

                        <!-- Center: Tax Status (Responsive) -->
                        <div class="text-left md:text-center w-full md:w-auto">
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
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Valid until {{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) ?? 'N/A' }}</p>
                        </div>

                        @php
                                $approved = $customer->is_credit_account == 1;
                                $hasCreditLimit = !empty($customer->credit_limit);
                            @endphp

                        <!-- Right: Status + Button -->
                        <div class="flex flex-col items-start md:items-end gap-2 text-left md:text-right w-auto">
                              @if ($approved && $hasCreditLimit)  
                                      <!-- Status Badge -->
                            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 w-auto">
                                Good Standing
                            </span>

                        
                            @elseif ($approved && !$hasCreditLimit)

                              <!-- Status Badge -->
                            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-green-800 w-auto">
                               Pending
                            </span>


                            @else

                            @endif 

                            <!-- Visit Website Button -->
                            <a href="#" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 w-auto">
                                <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                                Visit Website
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-md shadow-sm mt-6 mb-6 border border-gray-200">
                        <!-- Header with Filter -->
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 gap-4 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-gray-800">Your Orders</h2>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left text-gray-700">
                                <thead class="bg-gray-50 text-gray-500 text-xs border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 font-medium uppercase">Order ID</th>
                                        <th class="px-4 py-3 font-medium uppercase">Product Name</th>
                                        <th class="px-4 py-3 font-medium uppercase">Amount</th>
                                        <th class="px-4 py-3 font-medium uppercase">Payment Methods</th>
                                        <th class="px-4 py-3 font-medium uppercase">Status</th>
                                        <th class="px-4 py-3 font-medium uppercase">Created</th>
                                        <th class="px-4 py-3 font-medium uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="ordersTable" class="divide-y divide-gray-200">
                                          @forelse ($customer->orders as $order)

                                        @foreach ($order->products as $product)
                                                                <tr class="order-row">
                                                                    <td class="px-4 py-3 font-medium text-gray-900"> {{ $order->order_number }}</td>
                                                                    <td class="px-4 py-3">{{ $product->product_name ?? 'N/A' }}</td>
                                                                    <td class="px-4 py-3">  {{ \App\Helpers\CustomHelper::formatCurrency($product->total) }}</td>
                                                                    <td class="px-4 py-3">    {!! \App\Helpers\CustomHelper::paymentMethodLabel($order->payments->first()?->payment_method) !!}</td>
                                                                    <td class="px-4 py-3">
                                                         {!! \App\Helpers\CustomHelper::statusBadge($order->payments->first()->status ?? 'N/A') !!}
                                                                    </td>
                                                                    <td class="px-4 py-3">   {{ App\Helpers\CustomHelper::formatDate($order->order_date) ?? 'N/A' }}</td>
                                                                    <td class="px-4 py-3 items-center whitespace-nowrap text-blue-600">
                                                                        <!-- View -->
                                                                        <button title="View" class="openOrderModalBtn" data-order="{{ $order->order_number }}" data-date="{{ App\Helpers\CustomHelper::formatDate($order->order_date) }}" data-status="{{ \App\Helpers\CustomHelper::statusBadge($order->payments->first()->status ?? 'N/A') }}" data-method="{!! \App\Helpers\CustomHelper::paymentMethodLabel($order->payments->first()?->payment_method) !!}" data-total="{{ \App\Helpers\CustomHelper::formatCurrency($product->total) }}" data-product="{{ $product->product_name ?? 'N/A' }}">
                                                                            <x-heroicon-o-eye class="w-4 h-4 mr-1 cursor-pointer" />
                                                                        </button>

                                                                        <!-- Download -->
                                                                        <button title="Download">
                                                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 cursor-pointer" />
                                                                        </button>
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
