  <div class="bg-white mt-6 rounded-md shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border border-gray-200">
                        <!-- Left: Name and Account -->
                        <div class="text-left">
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4  mx-auto mt-6">
                        <!-- Current Balance -->
                        <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4 border border-gray-200">
                            <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                                <!-- Dollar Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Current Balance</p>
                                <p class="text-xl font-semibold text-gray-900">    {{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance ) }}
</p>
                            </div>
                        </div>

                        <!-- Available Credit -->
                        <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4 border border-gray-200">
                            <div class="bg-green-100 text-green-600 rounded-md p-2">
                                <!-- Trending Up Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-6 h-6"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Available Credit</p>
                                <p class="text-xl font-semibold text-gray-900"> {{ \App\Helpers\CustomHelper::formatCurrency(($customer->credit_limit ?? 0) - ($customer->total_account_order_amount ?? 0)) }}</p>
                            </div>
                        </div>

                        <!-- Open Invoices -->
                        <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4 border border-gray-200">
                            <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
                                <!-- Document Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-6 h-6"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Open Invoices</p>
                                <p class="text-xl font-semibold text-gray-900">2</p>
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
                                        <th class="px-4 py-3 font-medium uppercase">Product Name</th>
                                        <th class="px-4 py-3 font-medium uppercase">Amount</th>
                                        <th class="px-4 py-3 font-medium uppercase">Payment Methods</th>
                                        <th class="px-4 py-3 font-medium uppercase">Status</th>
                                        <th class="px-4 py-3 font-medium uppercase">Created</th>
                                        <th class="px-4 py-3 font-medium uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">

                                     @php $rowCount = 0; @endphp

                          @forelse ($customer->orders as $order)
                                @foreach ($order->products as $product)

                                  @if ($rowCount >= 5)
                                    @break(2) 
                                  @endif

                                    @php $rowCount++; @endphp

                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">  {{ $order->order_number }}</td>
                                        <td class="px-4 py-3">{{ $product->product_name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3"> {{ \App\Helpers\CustomHelper::formatCurrency($product->total ) }}</td>
                                        <td class="px-4 py-3"> {!! \App\Helpers\CustomHelper::paymentMethodLabel($order->payments->first()?->payment_method) !!}</td>
                                        <td class="px-4 py-3">
                                            <!-- <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">paid</span> -->

                                          {!! \App\Helpers\CustomHelper::statusBadge($order->payments->first()->status ?? 'N/A') !!}


                                        </td>
                                        <td class="px-4 py-3">   {{ App\Helpers\CustomHelper::formatDate($order->order_date) ?? 'N/A' }} </td>
                                        <td class="px-4 py-3 whitespace-nowrap items-center text-blue-600">
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

                                    <!-- <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">ORD-2025-002</td>
                                        <td class="px-4 py-3">Standard Widget Pack</td>
                                        <td class="px-4 py-3">$875.50</td>
                                        <td class="px-4 py-3">COD</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">paid</span>
                                        </td>
                                        <td class="px-4 py-3">Jan 20, 2025</td>
                                        <td class="px-4 py-3 flex items-center gap-3 text-blue-600">
                                            <button title="View">
                                                <x-heroicon-o-eye class="w-5 h-5 cursor-pointer" />
                                            </button>
                                            <button title="Download">
                                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600 cursor-pointer" />
                                            </button>
                                        </td>
                                    </tr>

                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">ORD-2025-003</td>
                                        <td class="px-4 py-3">Widget Accessories Kit</td>
                                        <td class="px-4 py-3">$624.50</td>
                                        <td class="px-4 py-3">Account</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Acct.</span>
                                        </td>
                                        <td class="px-4 py-3">Jan 25, 2025</td>
                                        <td class="px-4 py-3 flex items-center gap-3 text-blue-600">
                                            <button title="View">
                                                <x-heroicon-o-eye class="w-5 h-5 cursor-pointer" />
                                            </button>
                                            <button title="Download">
                                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600 cursor-pointer" />
                                            </button>
                                        </td>
                                    </tr> -->


                                </tbody>
                            </table>
                        </div>
                    </div>