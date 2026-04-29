   <div class="bg-white rounded-xl shadow-sm p-6 md:flex-row md:items-center md:justify-between gap-4  mt-6">
       <!-- Left: Name and Account -->
       <div class="text-left">
           <h2 class="text-2xl font-bold text-gray-900">Customer Credit Account Management</h2>
           <p class="text-sm text-gray-600">Manage customer credit account transactions and balance</p>
       </div>
   </div>

   <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mx-auto  mt-6">
       <!-- Current Balance -->
       <div class="bg-white p-4 rounded-xl shadow-sm flex items-center gap-4">
           <div class="bg-blue-100 text-blue-600 rounded-md p-2">
               <!-- Dollar Icon -->
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6">
                   <line x1="12" x2="12" y1="2" y2="22"></line>
                   <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
               </svg>
           </div>
           <div>
               <p class="text-sm  text-gray-500">Current Balance</p>
               <p class="text-xl font-semibold text-gray-900">

                   {{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance) }}

               </p>
           </div>
       </div>

       <!-- Available Credit -->
       <div class="bg-white p-4 rounded-xl shadow-sm flex items-center gap-4">
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
                   {{ \App\Helpers\CustomHelper::formatCurrency(\App\Helpers\CustomHelper::getAvailableCredit($customer)) }}
                   

               </p>
           </div>
       </div>

      <!-- Credit Limit -->
<div class="bg-white p-4 rounded-xl shadow-sm relative flex items-center gap-4">

    <!-- Edit Icon -->
    <button
        type="button"
        onclick="OpenCustomerEditModal()"
        class="absolute top-3 right-3 text-gray-400 hover:text-blue-600 transition"
        title="Edit Credit Limit"
    >
        <svg class="w-5 h-5 " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"></path>
</svg>
    </button>

    <!-- Icon -->
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

    <!-- Content -->
    <div>
        <p class="text-sm text-gray-500">Credit Limit</p>
        <p class="text-xl font-semibold text-gray-900">
            {{ \App\Helpers\CustomHelper::formatCurrency($customer->credit_limit) }}
        </p>
    </div>

</div>


       <!-- Last Payment -->
       <div class="bg-white p-4 rounded-xl shadow-sm flex items-center gap-4">
           <div class="bg-purple-100 text-purple-600 rounded-md p-2">
               <!-- Calendar Icon -->
               <x-heroicon-o-calendar class="w-6 h-6" />
           </div>
           <div>
               <p class="text-sm text-gray-500">Last Payment Date</p>
               <p class="text-xl font-semibold text-gray-900"> {{ App\Helpers\CustomHelper::formatDate($lastpaymentdate) ?? 'N/A' }} </p>
           </div>
       </div>
   </div>

   <div class="p-6 bg-white rounded-xl shadow-sm mt-6">
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

   <div class=" mx-auto bg-white shadow rounded-2xl mt-6 mb-6">
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
                           class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-md flex items-center">
                           {{-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                               viewBox="0 0 24 24" fill="none" stroke="currentColor"
                               stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                               class="lucide lucide-credit-card w-4 h-4 mr-2">
                               <rect width="20" height="14" x="2" y="5" rx="2" />
                               <line x1="2" x2="22" y1="10" y2="10" />
                           </svg> --}}

                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4 mr-2">
                    <line x1="12" x2="12" y1="2" y2="22"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>

                           Payment
                       </a>

                       <a href="javascript:void(0)" id="openRefundModal"
                           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-md flex items-center">
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                               viewBox="0 0 24 24" fill="none" stroke="currentColor"
                               stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                               class="lucide lucide-trending-up w-4 h-4 mr-2">
                               <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                               <polyline points="16 7 22 7 22 13" />
                           </svg>
                           Refund
                       </a>

                       <a href="javascript:void(0)" id="openDiscountModal" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg text-md flex items-center">
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                               viewBox="0 0 24 24" fill="none" stroke="currentColor"
                               stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                               class="lucide lucide-award w-4 h-4 mr-2">
                               <circle cx="12" cy="8" r="6" />
                               <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                           </svg>
                           Discount
                       </a>

                       <a href="javascript:void(0)" id="openChargeModal" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg text-md flex items-center">
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
                       <select id="typeFilters" class="border border-gray-300 rounded px-3 py-3 text-sm">
                           <option value="all">All Transactions</option>
                           <option value="order">Order</option>

                           <option value="payment">Payment</option>
                           <option value="refund">Refund</option>

                           <option value="discount">Discount</option>

                           <option value="charge">Charge</option>
                           <option value="account_invoice">Account Invoice</option>
                       </select>
                   </div>
               </div>
           </div>
       </div>


       <!-- Table -->
       <div class="overflow-x-auto">
           <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
               <thead class="border-b bg-gray-50 border-gray-200 font-semibold text-gray-700">
                   <tr>
                       <th class="py-4 px-6">Date</th>
                       <th class="py-4 px-6">Type</th>
                       <th class="py-4 px-6">ID#</th>
                       <th class="py-4 px-6  truncate min-w-3xs max-w-3xs">Description</th>
                       <th class="py-4 px-6 w-40 text-right">Note</th>
                       <th class="py-4 px-6 w-40 text-right">Amount</th>
                       <th class="py-4 px-6 w-40 text-right">Sales Tax</th>
                       <th class="py-4 px-6 w-40 text-right">Balance Change</th>
                       <th class="py-4 px-6 w-40 text-right">Running Balance</th>
                       <th class="py-4 px-6 w-32 text-right">Actions</th>
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
                   'account_invoice' => [
                    'bg' => 'bg-indigo-100',
                    'text' => 'text-indigo-800',
                    'amount' => 'text-indigo-600', 
                    'sign' => '',
                    'icon' => 'file-text',
                     'label' => 'Invoice',
                ],
                   ];
                   @endphp

                   @foreach ($customer->accounts as $transaction)
                   @php
                   $style = $typeStyles[$transaction->type] ?? $typeStyles['charge'];
                   @endphp

                   <tr data-status="{{ $transaction->type }}" class="border-b status-row">
                       <td class="py-4 px-6"> {{ App\Helpers\CustomHelper::formatDate($transaction->date) ?? 'N/A' }} </td>
                       <td class="py-4 px-6 text-red-600 ">


                          <div class="flex items-center gap-2">  
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
                               <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                   <path d="M17 7L7 17" />
                                   <path d="M17 17H7V7" />
                               </svg>
                               @elseif ($style['icon'] === 'arrow-up-right')
                               <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                   <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                   <polyline points="16 7 22 7 22 13" />
                               </svg>

                               @elseif ($style['icon'] === 'file-text')
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6M7 4h6l4 4v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                </svg>

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
                              <span class="ml-1 capitalize">
                                {{ $style['label'] ?? ucfirst($transaction->type) }}
                                </span>

                                
                           </span>
                         
                            </div>

                           <div class="text-xs text-gray-500 mt-1">
                                 @if ($transaction->invoice)
                                     #{{ $transaction->invoice->invoice_number }}
                                @elseif ($transaction->type !== 'payment')
                                    {{ $transaction->responsible_person_name }}
                                @endif
                           </div>
                       </td>

                       <td class="py-4 px-6 text-sm text-gray-900">
                           <div class="max-w-xs truncate text-gray-700">

                                 <span>
                                  @if ($transaction->order_id && $transaction->order)
                                        {!! $transaction->order->view_link !!}

                                    @elseif($transaction->type === 'payment' && $transaction->payment_type?->label() === 'Check' && $transaction->payment_number_id)
                                        {{ $transaction->payment_number_id }}

                                    @else
                                        -
                                    @endif
                                </span>

                           </div>
                          
                       </td>

                       <td class="py-4 px-6 text-sm text-gray-900">
                           <div class="max-w-xs truncate text-gray-700">

                               @if ($transaction->type === 'payment')
                               {{ $transaction->payment_type?->label() ?? '' }}
                               @else
                               {{ $transaction->reason ?? '' }}
                               @endif

                           </div>
                           <div class="text-xs text-gray-500 hidden">Ref: {{ $transaction->unique_id }}</div>
                       </td>
                       <!-- Amount Without Tax -->

                       <td class="py-4 px-6 text-right whitespace-nowrap transaction-note-cell-{{ $transaction->unique_id }}"> {{ $transaction->notes ?? '' }} </td>


                       <td class="py-4 px-6 text-right">

                           @if($transaction->sales_tax > 0 && ($transaction->type === 'payment' || ($transaction->type === 'charge' && $transaction->sales_tax_type === 'reverse')))
                           {{-- Tax is included in the amount (payment or reverse charge) --}}
                           {{ \App\Helpers\CustomHelper::formatCurrency(($transaction->amount ?? 0) / (1 + $transaction->sales_tax)) }}

                           @elseif($transaction->type === 'account_invoice')

                           {{ \App\Helpers\CustomHelper::formatCurrency($transaction->amount - $transaction->sales_tax) }}

                           @else
                           {{-- No tax or tax added on top --}}
                           {{ \App\Helpers\CustomHelper::formatCurrency($transaction->amount ?? 0) }}
                           @endif

                       </td>
                       {{-- Sales Tax Column --}}
                       <td class="py-4 px-6 text-right">

                           @if($transaction->type === 'account_invoice') 
                            {{ \App\Helpers\CustomHelper::formatCurrency($transaction->sales_tax) }}
                           @elseif($transaction->sales_tax > 0 && ($transaction->type === 'payment' || ($transaction->type === 'charge' && $transaction->sales_tax_type === 'reverse')))
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
                       <td class="py-4 px-6 text-right whitespace-nowrap {{ $style['amount'] }}">
                           @php
                           $totalWithTax = $transaction->amount;

                           if($transaction->type === 'account_invoice')
                                                     $totalWithTax = $transaction->amount;
                           elseif ($transaction->sales_tax > 0 && !($transaction->type === 'payment' || ($transaction->type === 'charge' && $transaction->sales_tax_type === 'reverse'))) {
                           // Only add tax if it's NOT already included
                           $totalWithTax += ($transaction->amount * $transaction->sales_tax);
                           }
                           @endphp

                           {{ $style['sign'] }}
                           {{ \App\Helpers\CustomHelper::formatCurrency($totalWithTax) }}
                       </td>

                       <td class="py-4 px-6 text-right whitespace-nowrap"> 
                         @if($transaction->type === 'account_invoice') 
                         -
                         @else
                        {{ \App\Helpers\CustomHelper::formatCurrency($transaction->balance ?? 0) }} </td>
                        @endif
                       <td class="py-4 px-6 text-blue-600">
                           <div class="flex gap-2 items-center justify-end">

                        {{-- @if($transaction->type !== 'account_invoice') --}}

                        {{-- view icon  --}}
                            @if ($transaction->invoice_id && $transaction->type === 'payment')
                                <a href="{{ route('admin.crm.customers.invoice.show', $transaction->invoice->unique_id) }}"   title="View" >
                                        <x-heroicon-o-eye class="w-4 h-4 text-blue-600" />    
                                </a>
                            @else


                                    @if($transaction->invoice?->unique_id && $transaction->type === 'account_invoice')

                                    <a href="{{ route('admin.crm.customers.invoice.show', $transaction->invoice?->unique_id) }}"  title="View" >
                                        <x-heroicon-o-eye class="w-4 h-4 text-blue-600" />
                                    </a>

                                    @else

                                        <button class="openTransactionViewModalBtn" title="View" data-transaction='@json($transaction)' data-date="{{ App\Helpers\CustomHelper::formatDate($transaction->date) }}">
                                        <x-heroicon-o-eye class="w-4 h-4 text-blue-600" />
                                    </button>
                                    @endif

                            @endif
                        {{-- view icon  --}}

                        {{-- Edit --}}
                               @if ($transaction->type !== 'order')

                                @if ($transaction->invoice_id && in_array($transaction->type, ['payment', 'account_invoice']))

                                    <a href="{{ route('admin.crm.customers.invoice.edit', $transaction->invoice->unique_id) }}"  class=" text-green-600 hover:text-green-800"
                                    
                                    title="Edit">
                                    {{-- <x-heroicon-o-pencil class="w-4 h-4" /> --}}

                                    <svg class="w-4 h-4 " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"></path>
                                    </svg>

                                    </a>
                               
                              
                                @else
                                    <button class="openEditPaymentModalBtn text-green-600 hover:text-green-800"
                                        data-id="{{ $transaction->id }}"
                                        data-type="{{ $transaction->type }}"
                                        data-amount="{{ $transaction->amount }}"
                                        data-payment_type="{{ $transaction->payment_type }}"
                                        data-cheque_number="{{ $transaction->payment_number_id }}"
                                        data-reason="{{ $transaction->reason }}"
                                        data-responsible_person="{{ $transaction->responsible_person_id }}"
                                        data-notes="{{ $transaction->notes }}"
                                        data-sales_tax="{{ $transaction->sales_tax }}"
                                        data-sales_tax_type="{{ $transaction->sales_tax_type }}"
                                        data-action="{{ route('admin.crm.customers.customer-account.transactionupdate', $transaction->id) }}"

                                        @if($transaction->card)
                                        data-card_id="{{ $transaction->card->unique_id }}"
                                        data-card_masked="{{ $transaction->card->card_number }}"
                                        @endif

                                        title="Edit">
                                        {{-- <x-heroicon-o-pencil class="w-4 h-4" /> --}}

                                             <svg class="w-4 h-4 " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"></path>
                                            </svg>
                                        
                                    </button>

                                @endif

                               @endif
                        {{-- Edit --}}

                        {{-- Download --}}
                             @if ($transaction->invoice_id && in_array($transaction->type, ['payment', 'account_invoice']))
                                <a href="{{ route('admin.crm.customers.invoice.download', $transaction->invoice?->unique_id) }}"
                                class="text-green-600 inline-flex items-center">
                                    <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />
                                </a>
                            @else
                                <form method="GET"
                                    action="{{ route('admin.crm.customers.customer-account.download', $transaction->id) }}"
                                    
                                    style="display:flex;">
                                    <button title="Download" type="submit">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600" />
                                    </button>
                                </form>
                            @endif
                        {{-- Download --}}
                               <!-- Note -->
                               <a href="javascript:void(0)" class="openNoteModalBtn" data-note="{{ $transaction->notes }}" data-id="{{ $transaction->unique_id }}" data-date="{{ App\Helpers\CustomHelper::formatDate($transaction->date) ?? 'N/A' }}" data-amount="{{ $transaction->amount }}">

                                   <x-heroicon-o-document-text class="w-4 h-4 {{ !empty($transaction->notes) ? 'text-purple-600' : 'text-gray-600' }} cursor-pointer" />

                               </a>

                                @if($transaction->invoice_id && in_array($transaction->type, ['account_invoice']))
                               
                                    <!-- Transaction delete button -->
                                    <form action="{{ route('admin.crm.customers.invoice.delete-invoice', $transaction->invoice->unique_id) }}"
                                        method="POST"
                                        class="flex delete-transaction-form"
                                        data-transaction-type="Invoice">
                                        @csrf
                                        
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-800"
                                                title="Delete">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </form>
                                @elseif (
                                    $transaction->type !== 'order' &&
                                    !($transaction->invoice_id && $transaction->type === 'payment')
                                )

                                 <!-- Transaction delete button -->
                                    <form action="{{ route('admin.crm.customers.customer-account.transactiondelete', $transaction->id) }}"
                                        method="POST"
                                        class="flex delete-transaction-form"
                                        data-transaction-type="{{ $transaction->type }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-800"
                                                title="Delete">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </form>
                               

                                @endif
                            {{-- @endif --}}

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
   <div id="templatesModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
       <div class="modal-scrollable w-full mx-auto">
           <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
               <div class="flex justify-between items-center px-6 pt-4">
                   <div class="flex items-center gap-2">
                       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 text-green-600">
                           <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                           <line x1="2" x2="22" y1="10" y2="10"></line>
                       </svg>
                       <h2 class="text-lg font-medium text-gray-900">Record Payment</h2>
                   </div>
                   <button id="closeModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
               </div>
               <div class=" px-6 overflow-y-auto">
                   <!-- <form> -->


                   {{ html()->form('POST', route('admin.crm.customers.customer-account.paymentstore'))->id('recordpayment')->attributes([
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
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Payment Amount </label>
                       <div class="relative">
                           <span class="absolute inset-y-0 left-0 h-[36px] pl-3 flex items-center text-gray-500">$</span>


                           <!-- <input type="number" placeholder="0.00"
                                class="pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm"/> -->

                           {!! html()->text('amount', old('amount'))->attributes([
                           'placeholder' => '0',
                           'autocomplete' => 'off',
                           'data-parsley-min' => '0.01',
                           'min' => '0.01',
                           'data-digit-input' => 'true',
                           'data-parsley-maxlength' => 8,
                           'maxlength' => 8,

                           ])->class('pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm')
                           ->placeholder('0.00')->required()
                           !!}



                       </div>
                   </div>
                   <!-- Payment Method -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Payment Method </label>
                       {!! html()->select(
                       'payment_type',
                       \App\Enums\Customers\PaymentMethod::options(),

                       )
                       ->id('payment_type')
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                       ->required() !!}


                   </div>

                   <!-- Cheque Number (hidden by default) -->
                   <div id="chequeNumberField" class="mb-4 hidden">
                       <label for="cheque_number" class="block text-sm font-medium text-gray-700 mb-1 ">
                           Check Number
                       </label>
                       <input
                           type="text"
                           id="cheque_number"
                           name="cheque_number"
                           class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700"
                           placeholder="Enter Check number" />
                   </div>


                   <!-- Card Options -->
                   <div id="creditCardOptions" class="mb-4 hidden">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Card Options </label>
                       <select id="cardOption" name="card_option"
                           class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                           <option value="NewCard" selected>New Card</option>

                           @if ($customer->cards && $customer->cards->count() > 0)
                           <option value="CardOnFile">Card on File</option>
                           @endif

                       </select>
                   </div>

                   <!-- New Card Fields -->
                   <div id="newCardFields" class="mb-4 hidden">
                       <div class="grid md:grid-cols-2 gap-4">
                           <div class="md:col-span-1">
                               <input type="text" placeholder="First name" id="firstName" name="firstName"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                           <div class="md:col-span-1">
                               <input type="text" placeholder="Last name" id="lastName" name="lastName"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                           <div class="md:col-span-2">
                               <input type="text" placeholder="Card number" maxlength="19" id="cardNumber" name="cardNumber"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                           <div class="md:col-span-1">
                               <input type="text" placeholder="MM/YY" maxlength="5" id="expiry" name="expiry"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                           <div class="md:col-span-1">
                               <input type="text" placeholder="CVC" maxlength="4" id="cvc" name="cvc"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                       </div>
                       <input type="hidden" name="opaqueDataValue" id="opaqueDataValue" />
                       <input type="hidden" name="opaqueDataDescriptor" id="opaqueDataDescriptor" />
                   </div>

                   <!-- Card on File Dropdown -->
                   @if ($customer->cards && $customer->cards->count() > 0)
                   <div id="cardOnFileDropdown" class="mb-4 hidden">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Select Existing Card </label>
                       <select name="existing_card_id"
                           class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                           <option value="">-- Select a saved card --</option>
                           @foreach ($customer->cards as $card)
                           <option value="{{ $card->unique_id }}">{{ $card->card_number }}</option>
                           @endforeach
                       </select>
                   </div>
                   @endif

                   <!-- Person Responsible -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible </label>



                       {!! html()
                       ->select('responsible_person',
                       $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                       old('responsible_person')
                       )
                       ->id('responsible_person')
                       ->class([
                       'w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700',
                       'border-red-500' => $errors->has('responsible_person'),
                       ])
                       ->required()
                       !!}


                   </div>

                   <!-- Notes -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                       <!-- <textarea rows="3" placeholder="Enter any additional notes..."
                            class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700"></textarea> -->

                       {!! html()->textarea('notes', old('notes'))
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                       ->rows(3)
                       ->placeholder('Enter any additional notes...') !!}


                   </div>

                   <div class="flex justify-end gap-2 pb-4">
                       <button type="button" id="canceltempBtn" class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">
                           Cancel
                       </button>
                       <button type="submit" id="submitTemplatesBtn" class="relative px-6 py-3 text-md rounded-lg bg-teal-600 text-white flex items-center justify-center gap-2 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
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
           <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
               <div class="flex justify-between items-center px-6 pt-4">
                   <div class="flex items-center gap-2">
                       <div class="text-blue-600 rounded-md">
                           <!-- Trending Up Icon -->
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4">
                               <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                               <polyline points="16 7 22 7 22 13"></polyline>
                           </svg>
                       </div>
                       <h2 class="text-lg font-medium text-gray-900">Process Refund</h2>
                   </div>
                   <button id="closeRefundModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
               </div>
               <div class=" px-6 overflow-y-auto">
                   <!-- <form> -->

                   {{ html()->form('POST', route('admin.crm.customers.customer-account.refundstore'))->id('processRefund')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ])->open() }}


                   {!! html()->hidden('customer_id', $customer->id ?? '') !!}
                   <!-- Payment Amount -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Refund Amount </label>
                       <div class="relative">
                           <span class="absolute h-[36px] inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                           <!-- <input type="number" placeholder="0.00" class="pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm"/> -->

                           {!! html()->text('amount', old('amount'))->attributes([
                           'placeholder' => '0',
                           'autocomplete' => 'off',
                           'data-digit-input' => 'true',
                           'data-parsley-maxlength' => 8,
                           'data-parsley-min' => '0.01',
                           'min' => '0.01',
                           'maxlength' => 8,
                           ])
                           ->class('pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm')
                           ->placeholder('0.00')->required() !!}

                       </div>
                   </div>
                   <!-- Payment Method -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Refund Reason </label>


                       {!! html()->select('reason', [
                       '' => 'Select refund reason',
                       'Billing Overcharge' => 'Billing Overcharge',
                       'Damage Waiver Protection' => 'Damage Waiver Protection',
                       'Customer Cancellation' => 'Customer Cancellation',
                       'Damaged Item' => 'Damaged Item',
                       'Duplicate Charge' => 'Duplicate Charge',
                       'Other' => 'Other',
                       'Wrong Item Shipped' => 'Wrong Item Shipped',
                       ], old('reason'))
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')->required() !!}

                   </div>
                   <!-- Person Responsible -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible </label>


                       {!! html()
                       ->select('responsible_person',
                       $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                       old('responsible_person')
                       )
                       ->id('responsible_person')
                       ->class([
                       'w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700',
                       'border-red-500' => $errors->has('responsible_person'),
                       ])
                       ->required()
                       !!}
                   </div>

                   <!-- Notes -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                       <!-- <textarea rows="3" placeholder="Describe the reason for this refund..."
                            class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700"></textarea> -->

                       {!! html()->textarea('notes', old('notes'))
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                       ->rows(3)
                       ->placeholder('Describe the reason for this refund...') !!}
                   </div>

                   <div class="flex justify-end gap-2 pb-4">
                       <button type="button" id="cancelRefundBtn" class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">
                           Cancel
                       </button>
                       <button type="submit" id="submitRefundBtn" class="relative px-6 py-3 text-md rounded-lg bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 flex items-center justify-center gap-2">
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
           <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
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

                   {{ html()->form('POST', route('admin.crm.customers.customer-account.discountstore'))->id('applyDiscount')->attributes([
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
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Discount Amount </label>
                       <div class="relative">
                           <span class="absolute inset-y-0 h-[36px] left-0 pl-3 flex items-center text-gray-500">$</span>
                           <!-- <input type="number" placeholder="0.00" class="pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm"/> -->

                           <!-- {!! html()->number('amount', old('amount'))
                                ->class('pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm')
                                ->placeholder('0.00')->required() !!} -->

                           {!! html()->text('amount' , old('amount') )->attributes([
                           'placeholder' => '0',
                           'autocomplete' => 'off',
                           'data-parsley-min' => '0.01',
                           'min' => '0.01',
                           'data-digit-input' => 'true',
                           'data-parsley-maxlength' => 8,
                           'maxlength' => 8,

                           ])->class('pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm')
                           ->placeholder('0.00')->required()
                           !!}


                       </div>
                   </div>
                   <!-- Discount Reason -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Discount Reason </label>



                       {!! html()->select('reason', [
                       '' => 'Select discount reason',
                       'Damage Waiver Protection' => 'Damage Waiver Protection',
                       'Misc. Management Discount' => 'Misc. Management Discount',
                       'Other' => 'Other',
                       'Repeat Customer Discount' => 'Repeat Customer Discount',
                       'Volume Discount' => 'Volume Discount',
                       ], old('reason'))
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')->required() !!}


                   </div>
                   <!-- Person Responsible -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible </label>

                       {!! html()
                       ->select('responsible_person',
                       $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                       old('responsible_person')
                       )
                       ->id('responsible_person')
                       ->class([
                       'w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700',
                       'border-red-500' => $errors->has('responsible_person'),
                       ])
                       ->required()
                       !!}

                   </div>

                   <!-- Notes -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                       <!-- <textarea rows="3" placeholder="Enter any additional notes about this discount..."
                            class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700"></textarea> -->


                       {!! html()->textarea('notes', old('notes'))
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                       ->rows(3)
                       ->placeholder('Enter any additional notes about this discount...') !!}


                   </div>

                   <div class="flex justify-end gap-2 pb-4">
                       <button type="button" id="cancelDiscountBtn" class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">
                           Cancel
                       </button>
                       <button type="submit" id="submitDiscountBtn" class="relative px-6 py-3 text-md rounded-lg bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 flex items-center justify-center gap-2">
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
           <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
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

                   {{ html()->form('POST', route('admin.crm.customers.customer-account.chargestore'))->id('applyCharge')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ])->open() }}


                   {!! html()->text('customer_id', $customer->id ?? '')->class('hidden')->attributes([
                   'id' => 'customer_id',
                   'autocomplete' => 'off'
                   ]) !!}



                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Charge Amount </label>
                       <div class="relative">
                           <span class="absolute h-[36px] inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                           <!-- <input type="number" placeholder="0.00" class="pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm"/> -->

                           {!! html()->text('amount', old('amount'))->attributes([
                           'placeholder' => '0',
                           'autocomplete' => 'off',
                           'data-parsley-min' => '0.01',
                           'min' => '0.01',
                           'data-digit-input' => 'true',
                           'data-parsley-maxlength' => 8,
                           'maxlength' => 8,

                           ])
                           ->class('pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm')
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
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Charge Reason </label>

                       {!! html()->select('reason', [
                       '' => 'Select charge reason',
                            'Cleaning Charge' => 'Cleaning Charge',
                            'Damages' => 'Damages',
                            'Fuel Charge' => 'Fuel Charge',
                            'Labor' => 'Labor' ,
                            'Missing Items' => 'Missing Items',
                            'Miscellaneous' => 'Miscellaneous',
                            'New Rental' => 'New Rental',
                            'Product Purchase' => 'Product Purchase',
                            'Rental Extension' => 'Rental Extension',
                       ], old('reason'))
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500')
                       ->required() !!}

                   </div>

                   <!-- Person Responsible -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible </label>

                       {!! html()
                       ->select('responsible_person',
                       $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                       old('responsible_person')
                       )
                       ->id('responsible_person')
                       ->class([
                       'w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700',
                       'border-red-500' => $errors->has('responsible_person'),
                       ])
                       ->required()
                       !!}

                   </div>

                   <!-- Notes -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>

                       {!! html()->textarea('notes', old('notes'))
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                       ->rows(3)
                       ->placeholder('Enter any additional notes about this charge...') !!}


                   </div>

                   <div class="flex justify-end gap-2 pb-4">
                       <button type="button" id="cancelChargeBtn" class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">
                           Cancel
                       </button>
                       <button type="submit" id="submitChargeBtn" class="relative px-6 py-3 text-md rounded-lg bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 flex items-center justify-center gap-2">
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
           <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
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
                       <button onclick="closeModal()" class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Close</button>
                       <button onclick="enableEdit()" class="px-6 py-3 text-md rounded-lg bg-brand-500 font-medium text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">Edit Note</button>
                   </div>

               </div>
           </div>
       </div>
   </div>


   @push('js')

    <!-- delete transaction script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-transaction-form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // stop auto submit

                    const transactionType = form.getAttribute('data-transaction-type') || 'this transaction';

                    window.showConfirm(
                        `Delete "${transactionType}"? This action cannot be undone!`,
                        'Delete transaction'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
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
                   const txdate = button.getAttribute('data-date') || '';
                   modalBody.innerHTML = '';

                   if (!tx || !tx.amount) {
                       modalBody.innerHTML = `<p class="text-gray-500 text-sm">Transaction not found.</p>`;
                       return;
                   }

                   let netAmount = parseFloat(tx.amount) || 0;
                   let taxAmount = 0;

                   // If tax exists
                   if (tx.sales_tax > 0) {
                       // Tax included in amount (payment or reverse charge)
                       if (tx.type === 'payment' || (tx.type === 'charge' && tx.sales_tax_type === 'reverse')) {
                           netAmount = netAmount / (1 + parseFloat(tx.sales_tax));
                           taxAmount = parseFloat(tx.amount) - netAmount;
                       } else {
                           // Tax added on top
                           taxAmount = netAmount * parseFloat(tx.sales_tax);
                       }
                   }


                   const total = netAmount + taxAmount;



                   let extraFields = '';

                    // If transaction is payment
                    if (tx.type === 'payment') {

                        extraFields += `
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Payment Type:</span>
                            <span class="text-gray-700">${tx.payment_type || '-'}</span>
                        </div>
                        `;

                        // If payment type = Check
                        if (tx.payment_type === 'Cheque' && tx.payment_number_id) {
                            extraFields += `
                            <div class="flex justify-between">
                                <span class="text-gray-500 font-medium">Cheque Number:</span>
                                <span class="text-gray-700">${tx.payment_number_id}</span>
                            </div>
                            `;
                        }
                    }

                    // If order exists
                    if (tx.order_id && tx.order?.view_link) {
                        extraFields += `
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">Order Id:</span>
                            <span class="text-gray-700">${tx.order.view_link}</span>
                        </div>
                        `;
                    }



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
                        <span class="text-gray-700">
                            ${tx.type ? tx.type.charAt(0).toUpperCase() + tx.type.slice(1) : '-'}
                        </span>
                    </div>

                    ${extraFields}

                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Amount:</span>
                     <span class="text-gray-700">$${netAmount.toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Sales Tax (${(tx.sales_tax * 100).toFixed(2)}%) :</span>
                        <span class="text-gray-700">$${taxAmount.toFixed(2)} </span>
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


           // modalWrapper.addEventListener('click', e => {
           //     if (e.target === modalWrapper) {
           //         modalWrapper.style.display = 'none';
           //     }
           // });
       });
   </script>


   <script>
       function attachValidatedSubmit(formId, btnId, btnTextId, spinnerId, loadingText) {
           const form = document.getElementById(formId);
           if (!form) return; // Safety in case the form doesn't exist

           form.addEventListener('submit', function(e) {
               e.preventDefault(); // Stop immediate submit

               // If using Parsley validation
               if ($(form).parsley().isValid()) {
                   const btn = document.getElementById(btnId);
                   const btnText = document.getElementById(btnTextId);
                   const spinner = document.getElementById(spinnerId);

                   btn.disabled = true;
                   btnText.textContent = loadingText;
                   spinner.classList.remove('hidden');

                   form.submit(); // Submit after showing loader
               }
           });
       }

       // Attach to all forms
       // attachValidatedSubmit('recordpayment', 'submitTemplatesBtn', 'btnText', 'btnSpinner', 'Processing...');
       attachValidatedSubmit('processRefund', 'submitRefundBtn', 'refundBtnText', 'refundBtnSpinner', 'Processing...');
       attachValidatedSubmit('applyDiscount', 'submitDiscountBtn', 'discountBtnText', 'discountBtnSpinner', 'Applying...');
       attachValidatedSubmit('applyCharge', 'submitChargeBtn', 'chargeBtnText', 'chargeBtnSpinner', 'Adding...');
   </script>


   @if ($paymentSetting['payment_test_mode'] ?? false)
   <script src="https://jstest.authorize.net/v1/Accept.js"></script>
   @else
   <script src="https://js.authorize.net/v1/Accept.js"></script>
   @endif

   <script>
       document.addEventListener('DOMContentLoaded', function() {
           // console.log(" DOM fully loaded");

           const form = document.getElementById('recordpayment');
           const paymentType = document.getElementById('payment_type');
           const creditCardOptions = document.getElementById('creditCardOptions');
           const cardOption = document.getElementById('cardOption');
           const newCardFields = document.getElementById('newCardFields');
           const cardOnFileDropdown = document.getElementById('cardOnFileDropdown');
           const cardNumberInput = document.getElementById('cardNumber');
           const expiryInput = document.getElementById('expiry');
           const cvcInput = document.getElementById('cvc');
           const submitBtn = document.getElementById('submitTemplatesBtn');
           const btnText = document.getElementById('btnText');
           const btnSpinner = document.getElementById('btnSpinner');
           const chequeNumberField = document.getElementById('chequeNumberField');


           // ===== Show/hide card sections =====
           paymentType.addEventListener('change', function() {
               console.log(" Payment type changed:", this.value);
               if (this.value === 'CreditCard') {
                   creditCardOptions.classList.remove('hidden');
                   cardOption.dispatchEvent(new Event('change'));
                   chequeNumberField.classList.add('hidden');

               } // Handle Cheque section
               else if (this.value === 'Cheque') {
                   chequeNumberField.classList.remove('hidden');
                   creditCardOptions.classList.add('hidden');
               } else {
                   creditCardOptions.classList.add('hidden');
                   chequeNumberField.classList.add('hidden');

                   newCardFields.classList.add('hidden');
                   cardOnFileDropdown.classList.add('hidden');
               }
           });

           cardOption.addEventListener('change', function() {
               console.log("Card option changed:", this.value);
               if (this.value === 'NewCard') {
                   newCardFields.classList.remove('hidden');
                   cardOnFileDropdown.classList.add('hidden');
               } else {
                   newCardFields.classList.add('hidden');
                   cardOnFileDropdown.classList.remove('hidden');
               }
           });

           // ===== Input formatting =====
           cardNumberInput.addEventListener('input', function() {
               this.value = this.value.replace(/\D/g, '').substring(0, 16).replace(/(.{4})/g, '$1 ').trim();
           });

           expiryInput.addEventListener('input', function() {
               let val = this.value.replace(/[^0-9]/g, '').substring(0, 4);
               if (val.length >= 3) val = val.substring(0, 2) + '/' + val.substring(2);
               this.value = val;
           });

           cvcInput.addEventListener('input', function() {
               this.value = this.value.replace(/\D/g, '').substring(0, 4);
           });

           // ===== Submit handler =====
           form.addEventListener('submit', function(e) {
               e.preventDefault();
               console.log(" Form submit triggered");

               const payType = paymentType.value;
               const cardOpt = cardOption.value;

               if (payType !== 'CreditCard' || cardOpt === 'CardOnFile') {
                   console.log("ℹ Non-credit card or card on file — submitting normally");
                   if ($(form).parsley().isValid()) {

                       // Show loader immediately
                       submitBtn.disabled = true;
                       btnText.textContent = 'Processing...';
                       btnSpinner.classList.remove('hidden');

                       form.submit();
                   } else {
                       notyf.error("Please fix the form errors before submitting.");
                   }
                   return;
               }

               // Show loader immediately
               submitBtn.disabled = true;
               btnText.textContent = 'Processing...';
               btnSpinner.classList.remove('hidden');
               console.log(" Loader shown, starting tokenization");

               try {
                   // Parse expiry
                   let [expMonth, expYearShort] = expiryInput.value.split('/');
                   expMonth = expMonth?.trim();
                   expYearShort = expYearShort?.trim();
                   let expYear = '';
                   if (expYearShort?.length === 2) expYear = '20' + expYearShort;
                   else if (expYearShort?.length === 4) expYear = expYearShort;

                   console.log(" Expiry parsed:", expMonth, expYear);

                   // Tokenize
                   Accept.dispatchData({
                       authData: {
                           clientKey: "{{ Crypt::decryptString($paymentSetting['payment_api_public_key']) ?? '' }}",
                           apiLoginID: "{{ Crypt::decryptString($paymentSetting['payment_api_key']) ?? '' }}"
                       },
                       cardData: {
                           cardNumber: cardNumberInput.value.replace(/\s/g, ''),
                           month: expMonth,
                           year: expYear,
                           cardCode: cvcInput.value,
                       }
                   }, function(response) {
                       console.log(" Tokenization response:", response);

                       if (response.messages.resultCode === 'Error') {
                           let errorMsg = response.messages.message?.[0]?.text || "Tokenization failed.";
                           console.error(" Tokenization error:", errorMsg);
                           notyf.error(errorMsg);

                           // Reset UI
                           submitBtn.disabled = false;
                           btnText.textContent = 'Record Payment';
                           btnSpinner.classList.add('hidden');
                           return; // Stop submit
                       }

                       console.log(" Tokenization success — Opaque Data:", response.opaqueData);
                       notyf.success("Payment details validated successfully!");

                       document.getElementById('opaqueDataValue').value = response.opaqueData.dataValue;
                       document.getElementById('opaqueDataDescriptor').value = response.opaqueData.dataDescriptor;

                       //  Now check Parsley validation before final submit
                       if ($(form).parsley().isValid()) {
                           console.log(" Form validation passed — submitting now");
                           form.submit();
                       } else {
                           console.warn(" Form validation failed after tokenization");
                           notyf.error("Please fix the form errors before submitting.");

                           submitBtn.disabled = false;
                           btnText.textContent = 'Record Payment';
                           btnSpinner.classList.add('hidden');
                       }

                   });

               } catch (error) {
                   console.error(" Tokenization JS error:", error);
                   notyf.error("Something went wrong during payment processing.");

                   // Reset UI
                   submitBtn.disabled = false;
                   btnText.textContent = 'Record Payment';
                   btnSpinner.classList.add('hidden');

                   return; // Stop submit
               }

           });

       });
   </script>

   @endpush