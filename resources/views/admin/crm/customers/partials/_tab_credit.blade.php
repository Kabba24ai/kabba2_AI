   <div class="bg-white rounded-md shadow-sm p-6 md:flex-row md:items-center md:justify-between gap-4  mt-6">
                <!-- Left: Name and Account -->
                <div class="text-left">
                    <h2 class="text-2xl font-bold text-gray-900">Customer Credit Account Management</h2>
                    <p class="text-sm text-gray-600">Manage customer credit account transactions and balance</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mx-auto  mt-6">
                <!-- Current Balance -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                        <!-- Dollar Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm  text-gray-500">Current Balance</p>
                        <p class="text-xl font-semibold text-gray-900">

                         {{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance) }}

                    </p>
                    </div>
                </div>

                <!-- Available Credit -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 rounded-md p-2">
                        <!-- Trending Up Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-6 h-6"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                    </div>
                    <div>
                        <p class="text-sm  text-gray-500">Available Credit</p>
                        <p class="text-xl font-semibold text-gray-900">
                        {{ \App\Helpers\CustomHelper::formatCurrency(\App\Helpers\CustomHelper::getAvailableCredit($customer)) }}
                        <!-- {{ \App\Helpers\CustomHelper::formatCurrency(($customer->credit_limit ?? 0) - ($customer->total_account_order_amount ?? 0) ) }} -->
                  
                    </p>
                    </div>
                </div>

                <!-- Credit Limit -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
                       <svg xmlns="http://www.w3.org/2000/svg" 
                            width="20" height="20" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-alert-circle w-6 h-6">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Credit Limit</p>
                    <p class="text-xl font-semibold text-gray-900"> 
                
                                                {{ \App\Helpers\CustomHelper::formatCurrency($customer->credit_limit) }}

                
                </p>
                    </div>
                </div>

                <!-- Last Payment -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-purple-100 text-purple-600 rounded-md p-2">
                        <!-- Calendar Icon -->
                        <x-heroicon-o-calendar class="w-6 h-6" />
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Last Payment Date</p>
                    <p class="text-xl font-semibold text-gray-900"> {{ App\Helpers\CustomHelper::formatDate($lastpaymentdate) ?? 'N/A' }}  </p>
                    </div>
                </div>
            </div>

                <div class="p-6 bg-white rounded-md shadow-sm mt-6">
                    <div class="flex justify-between items-center mb-2">
                        <h2 class="text-base font-semibold text-gray-800">Credit Utilization</h2>
                    </div>

                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600 mb-2">
                            Available: 
                            <span id="availableAmount">
                                {{ \App\Helpers\CustomHelper::formatCurrency(\App\Helpers\CustomHelper::getAvailableCredit($customer)) }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-600 mb-2">
                            Used: <span id="usedAmount">$0.00</span>
                        </div>
                    </div>

                    <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                        <div id="progressBar" class="h-full bg-blue-500 transition-all duration-500" style="width: 0%"></div>
                    </div>

                    <p class="text-sm text-center text-gray-500 mt-2">
                        Credit Limit: <span id="limitAmount">$0.00</span>
                    </p>
                </div>
                
            <div class=" mx-auto bg-white shadow rounded-md mt-6 mb-6">
                <!-- Header and Filter -->
                <div class="border-b border-gray-200 p-4 ">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        
                        <!-- Title -->
                        <h2 class="text-base font-semibold text-gray-800">Account Transactions</h2>

                        <!-- Buttons and Filter -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full lg:w-auto">
                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-2">
                                <a href="javascript:void(0)" id="openTemplatesModal"
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-credit-card w-4 h-4 mr-2">
                                        <rect width="20" height="14" x="2" y="5" rx="2" />
                                        <line x1="2" x2="22" y1="10" y2="10" />
                                    </svg>
                                    Payment
                                </a>

                                <a href="javascript:void(0)" id="openRefundModal"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-trending-up w-4 h-4 mr-2">
                                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                        <polyline points="16 7 22 7 22 13" />
                                    </svg>
                                    Refund
                                </a>

                                <a href="javascript:void(0)" id="openDiscountModal" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-sm flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-award w-4 h-4 mr-2">
                                        <circle cx="12" cy="8" r="6" />
                                        <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                    </svg>
                                    Discount
                                </a>

                                <a href="javascript:void(0)" id="openChargeModal" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-plus w-4 h-4 mr-2">
                                        <path d="M5 12h14" />
                                        <path d="M12 5v14" />
                                    </svg>
                                    New Charge
                                </a>
                            </div>

                            <!-- Divider (only for large screens) -->
                            <div class="hidden lg:block border-l border-gray-300 h-8"></div>

                            <!-- Filter -->
                            <div class="flex items-center space-x-2">
                                <label for="typeFilters" class="text-sm text-gray-600">Filter by Type:</label>
                                <select id="typeFilters" class="border border-gray-300 rounded px-3 py-1 text-sm">
                                <option value="all">All Transactions</option>
                                <option value="order">Order</option>

                                <option value="payment">Payment</option>
                                 <option value="refund">Refund</option>
                                
                                <option value="discount">Discount</option>
                              
                                 <option value="charge">Charge</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
                        <thead class="border-b bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3  truncate min-w-3xs max-w-3xs">Description</th>
                                <th class="px-4 py-3 w-40 text-right">Note</th>
                                <th class="px-4 py-3 w-40 text-right">Amount</th>
                                <th class="px-4 py-3 w-40 text-right">Sales Tax</th>
                                <th class="px-4 py-3 w-40 text-right">Balance Change</th>
                                <th class="px-4 py-3 w-40 text-right">Running Balance</th>
                                <th class="px-4 py-3 w-32 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id=" ">

                             @php
                                $typeStyles = [
                                    'charge' => [
                                        'bg' => 'bg-red-100',
                                        'text' => 'text-red-800',
                                        'amount' => 'text-red-600',
                                        'sign' => '+',
                                        'icon' => 'plus',
                                    ],
                                    'payment' => [
                                        'bg' => 'bg-green-100',
                                        'text' => 'text-green-800',
                                        'amount' => 'text-green-600',
                                        'sign' => '-',
                                        'icon' => 'credit-card',
                                    ],
                                    'order' => [
                                        'bg' => 'bg-red-100',
                                        'text' => 'text-red-800',
                                        'amount' => 'text-red-600',
                                        'sign' => '+',
                                        'icon' => 'cart',
                                    ],
                                    'discount' => [
                                        'bg' => 'bg-purple-100',
                                        'text' => 'text-purple-800',
                                        'amount' => 'text-green-600',
                                        'sign' => '-',
                                        'icon' => 'award',
                                    ],
                                    'credit' => [
                                        'bg' => 'bg-yellow-100',
                                        'text' => 'text-yellow-800',
                                        'amount' => 'text-green-600',
                                        'sign' => '-',
                                        'icon' => 'arrow-down-left',
                                    ],
                                    'debit' => [
                                        'bg' => 'bg-orange-100',
                                        'text' => 'text-orange-800',
                                        'amount' => 'text-red-600',
                                        'sign' => '+',
                                        'icon' => 'arrow-up-right',
                                    ],
                                    'refund' => [
                                        'bg' => 'bg-blue-100',
                                        'text' => 'text-blue-800',
                                        'amount' => 'text-green-600',
                                        'sign' => '-',
                                        'icon' => 'trending-up',
                                    ],
                                ];
                            @endphp

                            @foreach ($customer->accounts as $transaction)
                                @php
                                    $style = $typeStyles[$transaction->type] ?? $typeStyles['charge'];
                                @endphp
                                                    
                            <tr data-status="{{ $transaction->type }}" class="border-b status-row">
                                <td class="px-4 py-3"> {{ App\Helpers\CustomHelper::formatDate($transaction->date) ?? 'N/A' }} </td>
                                <td class="px-4 py-3 text-red-600 ">
                                    

                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $style['bg'] }} {{ $style['text'] }}">
                                                @if ($style['icon'] === 'plus')
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="lucide lucide-plus w-4 h-4">
                                                        <path d="M5 12h14" />
                                                        <path d="M12 5v14" />
                                                    </svg>
                                                @elseif ($style['icon'] === 'credit-card')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                                class="lucide lucide-credit-card w-4 h-4">
                                                                                <rect width="20" height="14" x="2" y="5" rx="2" />
                                                                                <line x1="2" x2="22" y1="10" y2="10" />
                                                                            </svg>
                                                @elseif ($style['icon'] === 'award')
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-award w-4 h-4">
                                                    <circle cx="12" cy="8" r="6" />
                                                    <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                                    </svg>      
                                                @elseif ($style['icon'] === 'arrow-down-left')
                                                    <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 7L7 17"/><path d="M17 17H7V7"/></svg>
                                                @elseif ($style['icon'] === 'arrow-up-right')
                                                            <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                                                @elseif ($style['icon'] === 'cart')
                                                            <x-heroicon-o-shopping-cart class="h-4 w-4" /> 
                                                @elseif ($style['icon'] === 'trending-up')

                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                                    class="lucide lucide-trending-up w-4 h-4">
                                                                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                                                                    <polyline points="16 7 22 7 22 13" />
                                                                                </svg>

                                                                                

                                                @endif
                                              <span class="ml-1 capitalize">{{ $transaction->type }}</span>
                                      </span>

                                                                                                                <div class="text-xs text-gray-500 mt-1">{{   $transaction->responsible_person_name ?? ''  }}
                                                                                                                    
                                                                        </div>
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm text-gray-900">
                                                                        <div class="max-w-xs truncate text-gray-700">  
                                                                    
                                                                       @if ($transaction->type === 'payment')
                                                                            {{ $transaction->payment_type ?? 'N/A' }}
                                                                        @else
                                                                            {{ $transaction->reason ?? 'N/A' }}
                                                                        @endif
                                                                        
                                                                        <!-- {{ $transaction->notes ?? 'N/A' }} -->
                                                                    
                                                                    </div>
                                                                        <div class="text-xs text-gray-500">Ref: {{ $transaction->unique_id }}</div>
                                                                    </td>
                                                                    <!-- Amount Without Tax -->

                                                          <td class="px-4 py-3 text-right whitespace-nowrap">  {{ $transaction->notes ?? 'N/A' }} </td>


                                                                        <td class="px-4 py-3 text-right"> 

                                                                             @if($transaction->sales_tax > 0 && ($transaction->type === 'payment' || ($transaction->type === 'charge' && $transaction->sales_tax_type === 'reverse')))
                                                                                    {{-- Tax is included in the amount (payment or reverse charge) --}}
                                                                                    {{ \App\Helpers\CustomHelper::formatCurrency(($transaction->amount ?? 0) / (1 + $transaction->sales_tax)) }}
                                                                                @else
                                                                                    {{-- No tax or tax added on top --}}
                                                                                    {{ \App\Helpers\CustomHelper::formatCurrency($transaction->amount ?? 0) }}
                                                                                @endif

                                                                        </td>
                                                                          {{-- Sales Tax Column --}}
                                                                        <td class="px-4 py-3 text-right">
                                                                            @if($transaction->sales_tax > 0 && ($transaction->type === 'payment' || ($transaction->type === 'charge' && $transaction->sales_tax_type === 'reverse')))
                                                                                {{-- Tax is included in the amount (payment or reverse charge) --}}
                                                                                @php
                                                                                    $taxAmount = ($transaction->amount ?? 0) - (($transaction->amount ?? 0) / (1 + $transaction->sales_tax));
                                                                                @endphp
                                                                                {{ \App\Helpers\CustomHelper::formatCurrency($taxAmount) }}
                                                                            @elseif($transaction->sales_tax > 0)
                                                                                {{-- Tax is added on top --}}
                                                                                {{ \App\Helpers\CustomHelper::formatCurrency(($transaction->amount ?? 0) * $transaction->sales_tax) }}
                                                                            @else
                                                                                {{ \App\Helpers\CustomHelper::formatCurrency(0) }}
                                                                            @endif
                                                                        </td>

                                                                        {{-- Total Amount with Tax if Applicable --}}
                                                                        <td class="px-4 py-3 text-right whitespace-nowrap {{ $style['amount'] }}">
                                                                            @php
                                                                                            $totalWithTax = $transaction->amount;

                                                                                                if ($transaction->sales_tax > 0 && !($transaction->type === 'payment' || ($transaction->type === 'charge' && $transaction->sales_tax_type === 'reverse'))) {
                                                                                                    // Only add tax if it's NOT already included
                                                                                                    $totalWithTax += ($transaction->amount * $transaction->sales_tax);
                                                                                                }
                                                                            @endphp

                                                                                            {{ $style['sign'] }}
                                                                                            {{ \App\Helpers\CustomHelper::formatCurrency($totalWithTax) }}
                                                                        </td>

                                                                    <td class="px-4 py-3 text-right whitespace-nowrap">  {{ \App\Helpers\CustomHelper::formatCurrency($transaction->balance ?? 0) }} </td>
                                                                    <td class="px-4 py-3 text-blue-600">
                                                                        <div class="flex gap-2 items-center justify-end">
                                                                        

                                                                        <button class="openTransactionViewModalBtn" title="View" data-transaction='@json($transaction)'    data-date="{{ App\Helpers\CustomHelper::formatDate($transaction->date) }}" >
                                                                            <x-heroicon-o-eye class="w-4 h-4 text-blue-600" />
                                                                        </button>

                                                                        @if ($transaction->type !== 'order')
                                                                            
                                                                            <button class="openEditPaymentModalBtn text-green-600 hover:text-green-800"
                                                                                    data-type="{{ $transaction->type }}"
                                                                                    data-amount="{{ $transaction->amount }}"
                                                                                    data-payment_type="{{ $transaction->payment_type }}"
                                                                                    data-reason="{{ $transaction->reason }}"
                                                                                    data-responsible_person="{{ $transaction->responsible_person_id }}"
                                                                                    data-notes="{{ $transaction->notes }}"
                                                                                    data-sales_tax="{{ $transaction->sales_tax }}"
                                                                                    data-sales_tax_type="{{ $transaction->sales_tax_type }}"
                                                                                    data-action="{{ route('admin.crm.customers.customeraccount.transactionupdate', $transaction->id) }}"
                                                                                    title="Edit">
                                                                                <x-heroicon-o-pencil class="w-4 h-4" />
                                                                            </button>

                                                                    
                                                                        @endif

                                                                            <!-- Download -->
                                                                            <form method="GET" action="{{ route('admin.crm.customers.customeraccount.download', $transaction->id) }}" target="_blank" style="display:flex;">
                                                                                <button title="Download" type="submit">
                                                                                    <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600" />
                                                                                </button>
                                                                            </form>

                                                                            <!-- Note -->
                                                                            <a href="javascript:void(0)" class="openNoteModalBtn" data-note="{{ $transaction->notes }}" data-id="{{ $transaction->unique_id }}" data-date="{{ App\Helpers\CustomHelper::formatDate($transaction->date) ?? 'N/A' }}" data-amount="{{ $transaction->amount }}">


                                                                                <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                                                                            </a>

                                    @if ($transaction->type !== 'order')
                                                                            <button class="openDeleteTransactionBtn text-red-600 hover:text-red-800"
                                                                                    data-id="{{ $transaction->id }}"
                                                                                    data-type="{{ $transaction->type }}"
                                                                                    data-action="{{ route('admin.crm.customers.customeraccount.transactiondelete', $transaction->id) }}"
                                                                                    title="Delete">
                                                                                <x-heroicon-o-trash class="w-4 h-4" />
                                                                            </button>
                                    @endif

                                    </div>
                                </td>
                            </tr>
                            @endforeach
                           
                        </tbody>
                    </table>
                </div>
            </div>


<!-- Single Transaction Modal -->
<div id="transactionViewModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-eye class="w-5 h-5 text-blue-600" />
                    <h2 class="text-lg font-medium text-gray-900">Transaction Details</h2>
                </div>
                <button id="closeTransactionViewModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class="px-6 overflow-y-auto" id="transactionViewModalBody">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>
</div>


<!-- Record Payment Wrapper -->
<div id="templatesModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 text-green-600">
                        <rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line>
                    </svg>
                    <h2 class="text-lg font-medium text-gray-900">Record Payment</h2>
                </div>
                <button id="closeModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" px-6 overflow-y-auto">
                <!-- <form> -->

                
                         {{ html()->form('POST', route('admin.crm.customers.customeraccount.paymentstore'))->id('recordpayment')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->open() }}

                        
                        
            {!! html()->text('customer_id', $customer->id ?? '')->class('hidden')->attributes([
                    'id' => 'customer_id',
                    'autocomplete' => 'off'
                ]) !!}


                    <!-- Payment Amount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount</label>
                        <div class="relative">
                        <span class="absolute inset-y-0 left-0 h-[35px] pl-3 flex items-center text-gray-500">$</span>


                        <!-- <input type="number" placeholder="0.00"
                                class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/> -->

                                  {!! html()->text('amount', old('amount'))->attributes([
                                        'placeholder' => '0',
                                        'autocomplete' => 'off',
                                    
                                        'data-digit-input' => 'true',
                                        'data-parsley-maxlength' => 8,
                                        'maxlength' => 8,
                                    
                                        ])->class('pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm')
                                    ->placeholder('0.00')->required() 
                                  !!}


    
                        </div>
                    </div>
                    <!-- Payment Method -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                       


                        {!! html()->select('payment_type', [
            '' => 'Select payment method',
            'CreditCard' => 'Credit / Debit Card',
            'Cash' => 'Cash',
            'Cheque' => 'Check',
            'BankTransfer' => 'Bank Transfer',
            'Other' => 'Other',
        ], old('payment_type'))
        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->required() !!}


                    </div>
                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>

                       

                        {!! html()
                        ->select('responsible_person',
                            $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                            old('responsible_person')
                        )
                        ->id('responsible_person')
                        ->class([
                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700',
                            'border-red-500' => $errors->has('responsible_person'),
                        ])
                        ->required()
                    !!}


                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <!-- <textarea rows="3" placeholder="Enter any additional notes..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700"></textarea> -->

                            {!! html()->textarea('notes', old('notes'))
        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
        ->rows(3)
        ->placeholder('Enter any additional notes...') !!}


                    </div>

                    <div class="flex justify-end gap-2 pb-4">
                        <button type="button" id="canceltempBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitTemplatesBtn" class="relative px-4 py-2 text-sm rounded bg-teal-600 text-white flex items-center justify-center gap-2 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                            <span id="btnText">Record Payment</span>
                            <svg id="btnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </button>



                    </div>

  {{ html()->form()->close() }}
            
            </div>
           
        </div>
    </div>
</div>

<!-- Process Refund Wrapper -->
<div id="refundModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-blue-600 rounded-md">
                        <!-- Trending Up Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Process Refund</h2>
                </div>
                <button id="closeRefundModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" px-6 overflow-y-auto">
                <!-- <form> -->

                 {{ html()->form('POST', route('admin.crm.customers.customeraccount.refundstore'))->id('processRefund')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ])->open() }}


                 {!! html()->hidden('customer_id', $customer->id ?? '') !!}
                    <!-- Payment Amount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Refund Amount</label>
                        <div class="relative">
                            <span class="absolute h-[35px] inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <!-- <input type="number" placeholder="0.00" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/> -->

                            {!! html()->text('amount', old('amount'))->attributes([
                                'placeholder' => '0',
                                'autocomplete' => 'off',
                                'data-digit-input' => 'true',
                                'data-parsley-maxlength' => 8,
                                'maxlength' => 8,
                                ])
                            ->class('pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm')
                            ->placeholder('0.00')->required() !!}

                        </div>
                    </div>
                    <!-- Payment Method -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Refund Reason</label>
                        

                        {!! html()->select('reason', [
                        '' => 'Select refund reason',
                        'Damaged Item' => 'Damaged Item',
                        'Wrong Item Shipped' => 'Wrong Item Shipped',
                        'Customer Cancellation' => 'Customer Cancellation',
                        'Billing Overcharge' => 'Billing Overcharge',
                        'Duplicate Charge' => 'Duplicate Charge',
                        'Other' => 'Other',
                    ], old('reason'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->required() !!}

                    </div>
                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>
                        <!-- <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 ">
                        <option>Select person responsible</option>
                        <option>John Doe</option>
                        <option>Jane Smith</option>
                        </select> -->

                        {!! html()
                        ->select('responsible_person',
                            $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                            old('responsible_person')
                        )
                        ->id('responsible_person')
                        ->class([
                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700',
                            'border-red-500' => $errors->has('responsible_person'),
                        ])
                        ->required()
                    !!}
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <!-- <textarea rows="3" placeholder="Describe the reason for this refund..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700"></textarea> -->

                            {!! html()->textarea('notes', old('notes'))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                        ->rows(3)
                        ->placeholder('Describe the reason for this refund...') !!}
                    </div>

                    <div class="flex justify-end gap-2 pb-4">
                        <button type="button" id="cancelRefundBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                       <button type="submit" id="submitRefundBtn" class="relative px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 flex items-center justify-center gap-2">
                            <span id="refundBtnText">Process Refund</span>
                            <svg id="refundBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </button>

                    </div>
             {{ html()->form()->close() }}
            
            </div>
            
        </div>
    </div>
</div>



<!-- Apply Discount Wrapper -->
<div id="discountModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-purple-600 rounded-md">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-award w-4 h-4">
                                <circle cx="12" cy="8" r="6" />
                                <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                            </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Apply Discount</h2>
                </div>
                <button id="closeDiscountModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class="px-6 overflow-y-auto">
                <!-- <form> -->

                 {{ html()->form('POST', route('admin.crm.customers.customeraccount.discountstore'))->id('applyDiscount')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ])->open() }}

                
            {!! html()->text('customer_id', $customer->id ?? '')->class('hidden')->attributes([
                    'id' => 'customer_id',
                    'autocomplete' => 'off'
                ]) !!}

                    <!-- Discount Amount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 h-[35px] left-0 pl-3 flex items-center text-gray-500">$</span>
                            <!-- <input type="number" placeholder="0.00" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/> -->

                             <!-- {!! html()->number('amount', old('amount'))
                                ->class('pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm')
                                ->placeholder('0.00')->required() !!} -->

                                {!! html()->text('amount' , old('amount') )->attributes([
                                'placeholder' => '0',
                                'autocomplete' => 'off',
                              
                                'data-digit-input' => 'true',
                                'data-parsley-maxlength' => 8,
                                'maxlength' => 8,
                               
                                ])->class('pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm')
                                    ->placeholder('0.00')->required()
                            !!}


                        </div>
                    </div>
                    <!-- Discount Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Reason</label>
                     


                        {!! html()->select('reason', [
                            '' => 'Select discount reason',
                            'Volume Discount' => 'Volume Discount',
                            'Repeat Customer Discount' => 'Repeat Customer Discount',
                            'Damage Waiver Protection' => 'Damage Waiver Protection',
                            'Misc. Management Discount' => 'Misc. Management Discount',
                            'Other' => 'Other',
                        ], old('reason'))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->required() !!}


                    </div>
                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>
                        
                         {!! html()
                            ->select('responsible_person',
                                $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                                old('responsible_person')
                            )
                            ->id('responsible_person')
                            ->class([
                                'w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700',
                                'border-red-500' => $errors->has('responsible_person'),
                            ])
                            ->required()
                        !!}

                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <!-- <textarea rows="3" placeholder="Enter any additional notes about this discount..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700"></textarea> -->


                            {!! html()->textarea('notes', old('notes'))
                            ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                            ->rows(3)
                            ->placeholder('Enter any additional notes about this discount...') !!}


                    </div>

                    <div class="flex justify-end gap-2 pb-4">
                        <button type="button" id="cancelDiscountBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitDiscountBtn" class="relative px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 flex items-center justify-center gap-2">
                            <span id="discountBtnText">Apply Discount</span>
                            <svg id="discountBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </button>

                    </div>
                <!-- </form> -->

            {{ html()->form()->close() }}

                    
            
            </div>
        </div>
    </div>
</div>

<!-- New Charge Wrapper -->
<div id="chargeModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-red-600 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-plus w-4 h-4">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">New Charge</h2>
                </div>
                <button id="closeChargeModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class="px-6 overflow-y-auto">
                <!-- <form> -->

                {{ html()->form('POST', route('admin.crm.customers.customeraccount.chargestore'))->id('applyCharge')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ])->open() }}

                
                {!! html()->text('customer_id', $customer->id ?? '')->class('hidden')->attributes([
                    'id' => 'customer_id',
                    'autocomplete' => 'off'
                ]) !!}



                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Amount</label>
                         <div class="relative">
                            <span class="absolute h-[35px] inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <!-- <input type="number" placeholder="0.00" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/> -->

                              {!! html()->text('amount', old('amount'))->attributes([
                                'placeholder' => '0',
                                'autocomplete' => 'off',
                              
                                'data-digit-input' => 'true',
                                'data-parsley-maxlength' => 8,
                                'maxlength' => 8,
                               
                                ])
                            ->class('pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm')
                            ->placeholder('0.00')->required() !!}


                        </div>
                    </div>
                    <!-- Sales Tax Treatment -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sales Tax Treatment</label>
                        <div class="space-y-2 text-sm text-gray-700">
                            

                            @foreach([
                            'add' => ['label' => 'Add Sales Tax', 'desc' => 'Add 9.75% sales tax to the entered amount'],
                            'free' => ['label' => 'Tax Free', 'desc' => 'No sales tax applied to this charge'],
                            'reverse' => ['label' => 'Reverse Sales Tax', 'desc' => 'Split entered amount proportionally between base amount and tax'],
                        ] as $value => $info)
                            <label class="flex items-start gap-2">
                                {!! html()->radio('sales_tax', $value === 'add', $value)->class('mt-1.5 text-blue-600 focus:ring-blue-500') !!}
                                <div>
                                    <p class="font-medium">{{ $info['label'] }}</p>
                                    <p class="text-gray-500">{{ $info['desc'] }}</p>
                                </div>
                            </label>
                        @endforeach

                        </div>
                    </div>

                    <!-- Charge Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Reason</label>
                        
                         {!! html()->select('reason', [
                        '' => 'Select charge reason',
                        'New Rental' => 'New Rental',
                        'Rental Extension' => 'Rental Extension',
                        'Damages' => 'Damages',
                        'Fuel Charge' => 'Fuel Charge',
                        'Cleaning Charge' => 'Cleaning Charge',
                        'Missing Items' => 'Missing Items',
                        'Product Purchase' => 'Product Purchase',
                    ], old('reason'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500')
                    ->required() !!}

                    </div>

                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>
                        
                         {!! html()
                        ->select('responsible_person',
                            $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                            old('responsible_person')
                        )
                        ->id('responsible_person')
                        ->class([
                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700',
                            'border-red-500' => $errors->has('responsible_person'),
                        ])
                        ->required()
                    !!}

                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                       
                             {!! html()->textarea('notes', old('notes'))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                        ->rows(3)
                        ->placeholder('Enter any additional notes about this charge...') !!}


                    </div>

                    <div class="flex justify-end gap-2 pb-4">
                        <button type="button" id="cancelChargeBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitChargeBtn" class="relative px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 flex items-center justify-center gap-2">
                            <span id="chargeBtnText">Add Charge</span>
                            <svg id="chargeBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </button>

                    </div>
               
                    
            {{ html()->form()->close() }}

            
            </div>
        </div>
    </div>
</div>


<!-- Transaction Note Wrapper -->
<div id="noteModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                     <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                    <h2 class="text-lg font-medium text-gray-900">Transaction Note</h2>
                </div>
                <button id="closeNoteModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class="px-6 overflow-y-auto">
                <div class="bg-gray-50 rounded-lg mb-4 text-sm space-y-1">

                    <input type="hidden" id="noteTransactionId">


                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Transaction:</span>
                        <span class="text-gray-700" data-note-field="id"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Date:</span>
                        <span class="text-gray-700" data-note-field="date"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Amount:</span>
                        <span class="text-gray-700" data-note-field="amount"></span>
                    </div>

                </div>

                 <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700 block mb-1">Note</label>
                    <div id="noteContainer" class="border border-gray-300 rounded-md p-3 text-sm text-gray-700 bg-white">
                        Customer requested expedited processing due to urgent project deadline. Approved by management for priority handling.
                    </div>
                </div>

                 <!-- Footer -->
                <div id="actionButtons" class="flex justify-end gap-2 py-4 border-t">
                    <button onclick="closeModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Close</button>
                    <button onclick="enableEdit()" class="px-4 py-2 text-sm rounded bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">Edit Note</button>
                </div>

            </div>
        </div>
    </div>
</div>


@push('js')

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.openDeleteTransactionBtn').forEach(button => {
        button.addEventListener('click', () => {
            const type = button.dataset.type;
            const id = button.dataset.id;
            const action = button.dataset.action;

            if (confirm(`Are you sure you want to delete this ${type} transaction?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = action;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild(csrfInput);
                form.appendChild(methodInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalWrapper = document.getElementById('transactionViewModalWrapper');
    const closeBtn = document.getElementById('closeTransactionViewModalBtn');
    const modalBody = document.getElementById('transactionViewModalBody');

    document.querySelectorAll('.openTransactionViewModalBtn').forEach(button => {
        button.addEventListener('click', () => {
            const tx = JSON.parse(button.getAttribute('data-transaction') || '{}');
            const txdate = button.getAttribute('data-date') || '' ;
            modalBody.innerHTML = '';

            if (!tx || !tx.amount) {
                modalBody.innerHTML = `<p class="text-gray-500 text-sm">Transaction not found.</p>`;
                return;
            }

            const taxAmount = tx.sales_tax_type === 'add' && tx.sales_tax > 0
                ? parseFloat(tx.amount) * parseFloat(tx.sales_tax)
                : 0;

            const total = parseFloat(tx.amount) + taxAmount;

            const html = `
                <div class=" rounded-lg mb-4 text-sm space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Transaction:</span>
                        <span class="text-gray-700">${tx.unique_id}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Date:</span>
                        <span class="text-gray-700">${txdate} </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Type:</span>
                        <span class="text-gray-700">${tx.type}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Amount:</span>
                        <span class="text-gray-700">$${parseFloat(tx.amount).toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Sales Tax:</span>
                        <span class="text-gray-700">$${taxAmount.toFixed(2)} (${(tx.sales_tax * 100).toFixed(2)}%)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Total With Tax:</span>
                        <span class="text-gray-700">$${total.toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Running Balance:</span>
                        <span class="text-gray-700">$${tx.balance || '-'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Reason:</span>
                        <span class="text-gray-700">${tx.reason || '-'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Responsible:</span>
                        <span class="text-gray-700">${tx.responsible_person_name || 'N/A'}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700 block mb-1">Note</label>
                    <div id="noteContainer" class="border border-gray-300 rounded-md p-3 text-sm text-gray-700 bg-white">
                        ${tx.notes || '—'}
                    </div>
                </div>

                
            `;

            modalBody.innerHTML = html;
            modalWrapper.style.display = 'flex';
        });
    });

   
    closeBtn.addEventListener('click', () => {
        modalWrapper.style.display = 'none';
    });

  
    modalWrapper.addEventListener('click', e => {
        if (e.target === modalWrapper) {
            modalWrapper.style.display = 'none';
        }
    });
});
</script>



<script>

document.getElementById('recordpayment').addEventListener('submit', function () {
  const btn = document.getElementById('submitTemplatesBtn');
  const btnText = document.getElementById('btnText');
  const spinner = document.getElementById('btnSpinner');

  btn.disabled = true;
  btnText.textContent = 'Processing...';
  spinner.classList.remove('hidden');
});


document.getElementById('processRefund').addEventListener('submit', function () {
  const btn = document.getElementById('submitRefundBtn');
  const btnText = document.getElementById('refundBtnText');
  const spinner = document.getElementById('refundBtnSpinner');

  btn.disabled = true;
  btnText.textContent = 'Processing...';
  spinner.classList.remove('hidden');
});

document.getElementById('applyDiscount').addEventListener('submit', function () {
  const btn = document.getElementById('submitDiscountBtn');
  const btnText = document.getElementById('discountBtnText');
  const spinner = document.getElementById('discountBtnSpinner');

  btn.disabled = true;
  btnText.textContent = 'Applying...';
  spinner.classList.remove('hidden');
});

document.getElementById('applyCharge').addEventListener('submit', function () {
  const btn = document.getElementById('submitChargeBtn');
  const btnText = document.getElementById('chargeBtnText');
  const spinner = document.getElementById('chargeBtnSpinner');

  btn.disabled = true;
  btnText.textContent = 'Adding...';
  spinner.classList.remove('hidden');
});


</script>


@endpush
