    <div class="bg-white rounded-md shadow-sm mt-6 mb-0">
        <!-- Header with Filter -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 p-4">
            <h2 class="text-base font-semibold text-gray-800">Customer Accounts ( <span id="customer-total-count"> {{ $customers->total() }} </span> )</h2>
        </div>
    
        <!-- Table -->
        <div class="max-w-full overflow-x-auto">
                    <table id="customerTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
                        <thead class="border-b bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3 truncate min-w-3xs max-w-3xs font-medium text-left cursor-pointer whitespace-nowrap" onclick="sortTable(0, 'string')">Customer Name <span id="icon-0" class="ml-1"></span></th>
                                <th class="px-4 py-3 truncate min-w-3xs max-w-3xs font-medium text-left cursor-pointer whitespace-nowrap" onclick="sortTable(1, 'string')">Company Name <span id="icon-1" class="ml-1"></span></th>
                                <th class="px-4 py-3 font-medium text-left whitespace-nowrap">Phone</th>
                                <th class="px-4 py-3 font-medium text-left whitespace-nowrap">Phone - Company</th>
                                <th class="px-4 py-3 font-medium text-left cursor-pointer whitespace-nowrap" onclick="sortTable(2, 'number')">Balance <span id="icon-2" class="ml-1"></span></th>
                                <th class="px-4 py-3 font-medium text-left whitespace-nowrap">Last Payment</th>
                                <th class="px-4 py-3 font-medium text-left cursor-pointer whitespace-nowrap" onclick="sortTable(3, 'date')">Last Payment Date <span id="icon-3" class="ml-1"></span></th>
                                <th class="px-4 py-3 font-medium text-left whitespace-nowrap">Payment Due</th>
                                <th class="px-4 py-3 font-medium text-left cursor-pointer whitespace-nowrap" onclick="sortTable(4, 'days')">Days Aging <span id="icon-4" class="ml-1"></span></th>
                                <th class="px-4 py-3 font-medium w-24 whitespace-nowrap text-center">Actions</th>
                            </tr>
                        </thead>
                        <div id="customer-loader" class="hidden"></div>

                        <tbody class="text-gray-700">
                             @forelse($customers as $customer)
                            <tr class="border-t hover:bg-gray-50">
                                <td  class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-4 h-4 text-gray-400 mr-2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <div class="text-sm font-medium text-gray-900">{{ $customer->full_name }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    @isset($customer->company_name)
                                        @if(trim($customer->company_name) !== '')
                                            <div class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2">
                                                    <rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect>
                                                    <path d="M9 22v-4h6v4"></path>
                                                    <path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path>
                                                    <path d="M12 10h.01"></path><path d="M12 14h.01"></path>
                                                    <path d="M16 10h.01"></path><path d="M16 14h.01"></path>
                                                    <path d="M8 10h.01"></path><path d="M8 14h.01"></path>
                                                </svg>
                                                <div class="text-sm text-gray-900"> {{ $customer->company_name }} </div>
                                            </div>
                                        @endif
                                    @endisset

                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-4 h-4 text-gray-400 mr-2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                        <div class="text-sm text-gray-700">{{ \App\Helpers\CustomHelper::formatPhone($customer->phone ?? '') ?: 'N/A' }}</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">

                                    @if(!empty($customer->company_phone))
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>
                                        <div class="text-sm text-gray-700">{{ \App\Helpers\CustomHelper::formatPhone($customer->company_phone ?? '') ?: 'N/A' }}</div>
                                    </div>

                                    @endif

                                </td>

                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance) }}</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm text-gray-700"> 

                                   {{ \App\Helpers\CustomHelper::formatCurrency(
                                        optional($customer->last_payment)->amount +
                                        (optional($customer->last_payment)->amount * (optional($customer->last_payment)->sales_tax ?? 0))
                                    ) }}


                                    <!-- {{ \App\Helpers\CustomHelper::formatCurrency(optional($customer->last_payment)->amount) }}  -->

                                
                                </div></td>
                                <td class="px-4 py-4 whitespace-nowrap" data-date="{{ App\Helpers\CustomHelper::formatDate(optional($customer->last_payment)->date) ?? '-' }}"><div class="text-sm text-gray-700">        {{ App\Helpers\CustomHelper::formatDate(optional($customer->last_payment)->date) ?? '-' }}</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-red-600">        - </div></td>
                            

                                <td class="px-4 py-4 whitespace-nowrap">
                                    @php
                                        $days = $customer->days_since_last_payment;
                                        $badge = $customer->payment_status_badge;
                                    @endphp

                                    @if($days === null)
                                        <span class="text-xs text-gray-500">No payment</span>
                                    @else
                                        @if($badge === 'safe')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                                                <span class="ml-1">{{ (int) $days }} days</span>
                                            </span>
                                        @elseif($badge === 'warning')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-4 h-4"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>

                                                <span class="ml-1">{{ (int) $days }} days</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-4 h-4"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>

                                                <span class="ml-1">{{ (int) $days }} days</span>
                                            </span>
                                        @endif
                                    @endif

                                </td>


                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <div class="flex items-center justify-center">
                                        <a href="{{ route('admin.crm.billingsummary.view' , $customer->unique_id) }}" target="_blank" class="text-blue-600  space-x-1">
                                            <x-heroicon-o-eye class="w-4 h-4" />
                                        </a>
                                   </div>
                                </td>
                            </tr>

                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                                        No customers found.
                                    </td>
                                </tr>
                            @endforelse

                            
                        </tbody>
                    </table>
        </div>
    </div>

{{-- Pagination --}}
<div class="mt-6">
    {{ $customers->links() }}
</div>
