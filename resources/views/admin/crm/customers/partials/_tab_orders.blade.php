 <div class="bg-white rounded-md shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Left: Name and Account -->
                <div class="text-left">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $customer->full_name }}</h2>
                    <p class="text-sm text-gray-600">Account:  {{ $customer->unique_id }}</p>
                </div>

                <!-- Center: Tax Status (Responsive) -->
                <div class="text-left md:text-center w-full md:w-auto">
                    <p class="text-xs uppercase tracking-wide text-gray-500 font-medium mb-1">Tax Status</p>
                    <div class="inline-flex items-center gap-2">
                        <span 
                        class="inline-flex items-center gap-2 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                            @if ($customer->tax_status === 'Exempt')
                                    <x-heroicon-o-shield-check class="w-4 h-4 text-green-500" />
                                @else
                                    <x-heroicon-o-shield-check class="w-4 h-4 text-gray-500" />
                                @endif

                           
                          {{ $customer->tax_status }} 
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1"> Valid until  {{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) ?? 'N/A' }}  </p>
                </div>

                <!-- Right: Status + Button -->
               <div class="flex flex-col items-start md:items-end gap-2 text-left md:text-right w-auto">
                    <!-- Status Badge -->
                    <span 
                    class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 w-auto">
                        Good Standing
                    </span>

                    <!-- Visit Website Button -->
                    <a target="_blank" href="{{ route('admin.crm.customers.login', ['unique_id' => $customer->unique_id]) }}" 
                    class="inline-flex items-center gap-2 px-3 py-1 text-xs font-medium rounded-full bg-blue-600 text-white hover:bg-blue-700 w-auto">
                        <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                        Website Login
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mx-auto mt-6 mb-6">
                <!-- Paid Sales -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 rounded-md p-2">
                        <!-- Dollar Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            width="20" height="20" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-check-circle w-4 h-4">
                            <path d="M9 12l2 2l4 -4"></path>
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Paid Sales</p>
                    <p class="text-lg font-semibold text-gray-900">{{ config('app.currency.code') }}{{ $customer->credit_limit ?? 0 }}</p>
                    </div>
                </div>

                <!-- Pending Sales -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            width="20" height="20" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-alert-circle w-4 h-4">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Pending Sales</p>
                    <p class="text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                </div>

                <!-- Account Balance -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                        <x-heroicon-o-calendar class="w-5 h-5 " />
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Account Balance</p>
                    <p class="text-lg font-semibold text-gray-900">{{ config('app.currency.code') }}{{ $customer->credit_limit ?? 0 }}</p>
                    </div>
                </div>

                <!-- Open Invoices -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-red-100 text-red-600 rounded-md p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            width="20" height="20" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-alert-circle w-4 h-4">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Open Invoices</p>
                    <p class="text-lg font-semibold text-gray-900">$0</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-md shadow-sm p-6">
                <!-- Header with Filter -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800  mb-4 ">All Customer Orders</h2>
                    <div class="mb-6">
                        <label for="statusFilter" class="text-sm font-medium text-gray-700 mr-2">Filter by Status:</label>
                        <select id="statusFilter" class="border border-gray-300 rounded-md px-3 py-1 text-sm">
                            <option value="all">All Orders</option>
                            <option value="pending">Pending</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="bg-gray-50 text-gray-500 text-sm border-b border-gray-200">
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

                               <tr class="order-row" data-status="{{ strtolower(str_replace(' ', '-', $order->status)) }}">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $order->order_number }}</td>
                                <td class="px-4 py-3">---</td>
                                <td class="px-4 py-3">{{ config('app.currency.code') }}{{ number_format($order->grand_total, 2) }}</td>
                                <td class="px-4 py-3">{{ $order->payment_type ?? 'N/A' }}</td>
                                <td class="px-4 py-3">

                                    @php
                                        $statusColors = [
                                            'Pending' => 'bg-yellow-100 text-yellow-800',
                                            'In Progress' => 'bg-blue-100 text-blue-800',
                                            'Completed' => 'bg-green-100 text-green-800',
                                            'Cancelled' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusColor = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp

                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $statusColor }} ">{{ $order->status }}</span>
                                </td>
                                <td class="px-4 py-3">{{ App\Helpers\CustomHelper::formatDate($order->order_date) ?? 'N/A' }}</td>
                                <td class="px-4 py-3 flex items-center gap-3 text-blue-600">
                                    <!-- View -->
                                    <a href="{{ route('admin.order-management.orders.edit', $order->unique_id) }}" title="View">
                                        <x-heroicon-o-eye class="w-5 h-5" />
                                    </a>
                                    <!-- Download -->
                                     <a href="javascript:void(0)" title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600" />
                                     </a>
                                </td>
                            </tr>

                             @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                                    No order found.
                                </td>
                            </tr>
   @endforelse
                        </tbody>
                    </table>
                </div>
            </div>