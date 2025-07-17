   <div class="bg-white rounded-md shadow-sm p-6 md:flex-row md:items-center md:justify-between gap-4">
                <!-- Left: Name and Account -->
                <div class="text-left">
                    <h2 class="text-lg font-semibold text-gray-900">Customer Credit Account Management</h2>
                    <p class="text-sm text-gray-600">Manage customer credit account transactions and balance</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mx-auto mt-6 mb-6">
                <!-- Current Balance -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                        <!-- Dollar Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Current Balance</p>
                    <p class="text-lg font-semibold text-gray-900">{{ config('app.currency.code') }}{{ $customer->credit_limit ?? 0 }}</p>
                    </div>
                </div>

                <!-- Available Credit -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 rounded-md p-2">
                        <!-- Trending Up Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Available Credit</p>
                    <p class="text-lg font-semibold text-gray-900">{{ config('app.currency.code') }}{{ $customer->credit_limit ?? 0 }}</p>
                    </div>
                </div>

                <!-- Credit Limit -->
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
                    <p class="text-sm text-gray-500">Credit Limit</p>
                    <p class="text-lg font-semibold text-gray-900"> {{ config('app.currency.code') }}{{ $customer->credit_limit ?? 0 }} </p>
                    </div>
                </div>

                <!-- Last Payment -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-purple-100 text-purple-600 rounded-md p-2">
                        <!-- Calendar Icon -->
                        <x-heroicon-o-calendar class="w-5 h-5" />
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Last Payment Date</p>
                    <p class="text-lg font-semibold text-gray-900">Not Applicable </p>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white rounded-md shadow-sm mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-base font-semibold text-gray-800">Credit Utilization</h2>
                </div>
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600 mb-2">
                        Available: <span id="availableAmount">$0.00</span>
                    </div>
                    <div class="text-sm text-gray-600 mb-2">
                        Used: <span id="usedAmount">$0.00</span>
                    </div>
                </div>
                <!-- Progress Bar Container -->
                <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                    <div id="progressBar" class="h-full bg-blue-500 transition-all duration-500" style="width: 0%"></div>
                </div>

                <p class="text-sm text-center text-gray-500 mt-2"> Credit Limit: <span id="limitAmount">$0.00</span> </p>
            </div>

            <div class=" mx-auto bg-white shadow rounded-md p-6">
                <!-- Header and Filter -->
                <div class="border-b border-gray-200">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        
                        <!-- Title -->
                        <h2 class="text-lg font-semibold text-gray-800">Account Transactions</h2>

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
                                    Credit / Debit
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
                                <option value="charge">Charge</option>
                                <option value="payment">Payment</option>
                                <option value="discount">Discount</option>
                                <option value="refund">Refund</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="bg-gray-50 text-gray-500 text-sm border-b uppercase">
                            <tr>
                                <th class="px-3 py-2 font-medium">Date</th>
                                <th class="px-3 py-2 font-medium">Type</th>
                                <th class="px-3 py-2 font-medium">Description</th>
                                <th class="px-3 py-3 text-right font-medium">Amount</th>
                                <th class="px-3 py-3 text-right font-medium">Sales Tax</th>
                                <th class="px-3 py-3 text-right font-medium">Balance Change</th>
                                <th class="px-3 py-3 text-right font-medium">Running Balance</th>
                                <th class="px-3 py-3 text-center font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transactionTable">

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
            'amount' => 'text-red-600',
            'sign' => '+',
            'icon' => 'trending-up',
        ],
    ];
@endphp

                            @foreach ($customer->accounts as $transaction)
                                @php
                                $style = $typeStyles[$transaction->type] ?? $typeStyles['charge'];
                            @endphp

                                                    
                            <tr data-status="charge" class="border-b status-row">
                                <td class="px-3 py-3"> {{ App\Helpers\CustomHelper::formatDate($transaction->date) ?? 'N/A' }} </td>
                                <td class="px-3 py-3 text-red-600 ">
                                    

                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $style['bg'] }} {{ $style['text'] }}">
        @if ($style['icon'] === 'plus')
            <svg class="lucide w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        @elseif ($style['icon'] === 'credit-card')
           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-credit-card w-4 h-4 mr-2">
                                        <rect width="20" height="14" x="2" y="5" rx="2" />
                                        <line x1="2" x2="22" y1="10" y2="10" />
                                    </svg>
     @elseif ($style['icon'] === 'award')
            <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
        @elseif ($style['icon'] === 'arrow-down-left')
            <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 7L7 17"/><path d="M17 17H7V7"/></svg>
        @elseif ($style['icon'] === 'arrow-up-right')
                       <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>

        @elseif ($style['icon'] === 'trending-up')

            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-trending-up w-4 h-4 mr-2">
                                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                        <polyline points="16 7 22 7 22 13" />
                                    </svg>
        @endif
        <span class="ml-1 capitalize">{{ $transaction->type }}</span>
    </span>

                                        <div class="text-xs text-gray-500 mt-1">{{   $transaction->responsibleUser->full_name  }}
</div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <div class="max-w-xs truncate text-gray-700">{{ $transaction->notes ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">Ref: {{ $transaction->unique_id }}</div>
                                </td>
                                <td class="px-3 py-3 text-right">  {{ config('app.currency.code') }}{{ number_format($transaction->amount, 2) }}</td>
                                <td class="px-3 py-3 text-right">$0</td>
                                <td class="px-3 py-3 text-right {{ $style['amount'] }}">
                                    {{ $style['sign'] }}${{ number_format($transaction->amount, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right">$10000</td>
                                <td class="px-3 py-3 text-blue-600 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- View -->
                                        <button title="View">
                                            <x-heroicon-o-eye class="w-4 h-4 text-blue-600" />
                                        </button>

                                        <!-- Download -->
                                        <button title="Download">
                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600" />
                                        </button>

                                        <!-- Note -->
                                        <a href="javascript:void(0)" id="openNoteModal">
                                            <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                           
                        </tbody>
                    </table>
                </div>
            </div>



<!-- Record Payment Wrapper -->
<div id="templatesModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 mr-2 text-green-600">
                        <rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line>
                    </svg>
                    <h2 class="text-lg font-medium text-gray-900">Record Payment</h2>
                </div>
                <button id="closeModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" p-6 overflow-y-auto">
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
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>


                        <!-- <input type="number" placeholder="0.00"
                                class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/> -->

                                  {!! html()->number('amount', old('amount'))
            ->class('pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm')
            ->placeholder('0.00')->required() !!}


    
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
                        <button type="submit" id="submitTemplatesBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                            Record Payment
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
                    <div class="text-blue-600 rounded-md p-2">
                        <!-- Trending Up Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Process Refund</h2>
                </div>
                <button id="closeRefundModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" p-6 overflow-y-auto">
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
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <!-- <input type="number" placeholder="0.00" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/> -->

                            {!! html()->number('amount', old('amount'))
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
                        <button type="submit" id="submitRefundBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                            Process Refund
                        </button>
                    </div>
             {{ html()->form()->close() }}
            
            </div>
            <!-- <div class="flex justify-end gap-2 px-6 pb-4">
                <button type="button" id="cancelBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">Cancel</button>
                <button type="button" id="submitTemplatesBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-blue-700"> Record Payment</button>
            </div> -->
        </div>
    </div>
</div>



<!-- Apply Discount Wrapper -->
<div id="discountModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-purple-600 rounded-md p-2">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-award w-4 h-4 mr-2">
                                <circle cx="12" cy="8" r="6" />
                                <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                            </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Apply Discount</h2>
                </div>
                <button id="closeDiscountModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" p-6 overflow-y-auto">
                <form>
                    <!-- Discount Amount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input type="number" placeholder="0.00" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/>
                        </div>
                    </div>
                    <!-- Discount Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Reason</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700  ">
                            <option value="">Select discount reason</option>
                            <option value="volume-discount">Volume Discount</option>
                            <option value="repeat-customer">Repeat Customer Discount</option>
                            <option value="damage-waiver">Damage Waiver Protection</option>
                            <option value="misc-management">Misc. Management Discount</option>
                        </select>
                    </div>
                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 ">
                        <option>Select person responsible</option>
                        <option>John Doe</option>
                        <option>Jane Smith</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea rows="3" placeholder="Enter any additional notes about this discount..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pb-4">
                        <button type="button" id="cancelDiscountBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitDiscountBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                            Apply Discount
                        </button>
                    </div>
                </form>
            
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
                    <div class="text-red-600 rounded-md p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-plus w-4 h-4 mr-2">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">New Charge</h2>
                </div>
                <button id="closeChargeModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" p-6 overflow-y-auto">
                <form>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Amount</label>
                         <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input type="number" placeholder="0.00" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/>
                        </div>
                    </div>
                    <!-- Sales Tax Treatment -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sales Tax Treatment</label>
                        <div class="space-y-2 text-sm text-gray-700">
                            <label class="flex items-start gap-2">
                                <input type="radio" name="tax" value="add" class="mt-1.5 text-blue-600 focus:ring-blue-500" checked>
                                <div>
                                    <p class="font-medium">Add Sales Tax</p>
                                    <p class="text-gray-500">Add 9.75% sales tax to the entered amount</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2">
                                <input type="radio" name="tax" value="free" class="mt-1.5 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <p class="font-medium">Tax Free</p>
                                    <p class="text-gray-500">No sales tax applied to this charge</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2">
                                <input type="radio" name="tax" value="reverse" class="mt-1.5 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <p class="font-medium">Reverse Sales Tax</p>
                                    <p class="text-gray-500">Split entered amount proportionally between base amount and tax</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Charge Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Reason</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select charge reason</option>
                            <option value="new-rental">New Rental</option>
                            <option value="rental-extension">Rental Extension</option>
                            <option value="damages">Damages</option>
                            <option value="fuel-charge">Fuel Charge</option>
                            <option value="cleaning-charge">Cleaning Charge</option>
                            <option value="missing-items">Missing Items</option>
                            <option value="product-purchase">Product Purchase</option>
                        </select>
                    </div>

                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 ">
                        <option>Select person responsible</option>
                        <option>John Doe</option>
                        <option>Jane Smith</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea rows="3" placeholder="Enter any additional notes about this charge..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pb-4">
                        <button type="button" id="cancelChargeBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitChargeBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                            Add Charge
                        </button>
                    </div>
                </form>
            
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
            <div class="p-6 overflow-y-auto">
                <div class="bg-gray-50 rounded-lg mb-4 text-sm space-y-1">
                    <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Transaction:</span>
                    <span class="text-gray-700">ORD-2025-001</span>
                    </div>
                    <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Date:</span>
                    <span class="text-gray-700">Jan 15, 2025</span>
                    </div>
                    <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Amount:</span>
                    <span class="text-gray-700">$1,097.44</span>
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
