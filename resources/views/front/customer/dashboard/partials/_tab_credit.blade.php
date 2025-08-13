
                    
                    <div class="bg-white rounded-md shadow-sm p-6 md:flex-row md:items-center md:justify-between gap-4 border border-gray-200  mt-6">
                        <!-- Left: Name and Account -->
                        <div class="text-left">
                            <h2 class="text-2xl font-bold text-gray-900">Credit Account</h2>
                            <p class="text-sm text-gray-600">View your credit account balance and transaction history</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mx-auto mt-6 ">
                        <!-- Current Balance -->
                        <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4  border border-gray-200">
                            <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                                <!-- Dollar Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            </div>
                            <div>
                            <p class="text-sm text-gray-500">Current Balance</p>
                            <p class="text-xl font-semibold text-gray-900">                       
                                  {{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance) }}
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
                            <p class="text-xl font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency(\App\Helpers\CustomHelper::getAvailableCredit($customer)) }}</p>
                            </div>
                        </div>

                        <!-- Credit Limit -->
                        <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4 border border-gray-200">
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
                        <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4 border border-gray-200">
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

                    <div class="p-6 bg-white rounded-md shadow-sm mb-6 border border-gray-200  mt-6">
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

                    <div class=" mx-auto bg-white shadow rounded-md border border-gray-200  mt-6 mb-6">
                        <!-- Header and Filter -->
                        <div class="border-b border-gray-200">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 p-4">
                                
                                <!-- Title -->
                                <h2 class="text-base font-semibold text-gray-800">Transaction History</h2>

                                <!-- Buttons and Filter -->
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full lg:w-auto">
                                    <!-- Filter -->
                                    <div class="flex items-center space-x-2">
                                        <label for="typeFilters" class="text-sm text-gray-600">Filter by Type:</label>
                                        <select id="typeFilters" class="border border-gray-300 rounded px-3 py-1 text-sm">
                                            <option value="all">All Transactions</option>
                                             <option value="order">Order</option>
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
                                <thead class="bg-gray-50 text-gray-500 text-xs divide-y divide-gray-200 uppercase border-b border-gray-200">
                                    <tr>
                                        <th class="px-3 py-3 font-medium">Date</th>
                                        <th class="px-3 py-3 font-medium">Type</th>
                                        <th class="px-3 py-3 font-medium">Description</th>
                                        <th class="px-3 py-3 text-right font-medium">Amount</th>
                                        <th class="px-3 py-3 text-right font-medium">Sales Tax</th>
                                        <th class="px-3 py-3 text-right font-medium">Balance Change</th>
                                        <th class="px-3 py-3 text-right font-medium">Running Balance</th>
                                        <th class="px-3 py-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="transactionTable" class="divide-y divide-gray-200">

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

                                    <tr data-status="{{ $transaction->type }}" class=" status-row">
                                        <td class="px-3 py-3">{{ App\Helpers\CustomHelper::formatDate($transaction->date) ?? 'N/A' }} </td>
                                        <td class="px-3 py-3 text-red-600 ">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $style['bg'] }} {{ $style['text'] }}">
                                            
                                                               @if ($style['icon'] === 'plus')
                                                                       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                                            class="lucide lucide-trending-up w-4 h-4">
                                                                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                                                            <polyline points="16 7 22 7 22 13" />
                                                                        </svg>
                                                            @elseif ($style['icon'] === 'credit-card')
                                                                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4">
                                                    <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                                    <line x1="2" x2="22" y1="10" y2="10"></line>
                                                </svg>
                                                                            @elseif ($style['icon'] === 'award')
                                                                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-4 h-4"><circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path></svg>      
                                                                            @elseif ($style['icon'] === 'arrow-down-left')
                                                                                <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 7L7 17"/><path d="M17 17H7V7"/></svg>
                                                                            @elseif ($style['icon'] === 'arrow-up-right')
                                                                                        <svg class="lucide w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                                                                            @elseif ($style['icon'] === 'cart')
                                                                        <x-heroicon-o-shopping-cart class="h-4 w-4" /> 
                                                                            @elseif ($style['icon'] === 'trending-up')

                                                                                

                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>

                                                                                @endif

                                            <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-3 h-3"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg> -->

                                                <span class="ml-1 capitalize">{{ $transaction->type }}</span>

                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-900">
                                            <div class="max-w-xs truncate text-gray-700">{{ $transaction->notes ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">Ref: {{ $transaction->unique_id }}</div>
                                        </td>
                                         <!-- Amount Without Tax -->
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

                                        <td class="px-3 py-3 text-right">{{ \App\Helpers\CustomHelper::formatCurrency($transaction->balance ?? 0) }}</td>
                                        <td class="px-3 py-3 text-blue-600 text-left">
                                            <div class="flex justify-end items-center text-left space-x-2">
                                                
                                            
                                            <!-- View -->
                                                <button class="openTransactionViewModalBtn" title="View" data-transaction='@json($transaction)'    data-date="{{ App\Helpers\CustomHelper::formatDate($transaction->date) }}" >
                                                                            <x-heroicon-o-eye class="w-4 h-4 text-blue-600" />
                                                                        </button>
                                               <!-- Download -->
                                                                            <form method="GET" action="{{ route('front.customer.dashboard.download', $transaction->id) }}" target="_blank" style="display:flex;">
                                                                                <button title="Download" type="submit">
                                                                                    <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600" />
                                                                                </button>
                                                                            </form>
                                                <!-- Note -->
<a href="javascript:void(0)" 
   class="openNoteModalBtn" 
   data-id="{{ $transaction->unique_id }}"
   data-date="{{ App\Helpers\CustomHelper::formatDate($transaction->date) }}"
   data-amount="{{ \App\Helpers\CustomHelper::formatCurrency($transaction->amount + ($transaction->sales_tax > 0 ? $transaction->amount * $transaction->sales_tax : 0)) }}"
   data-note="{{ $transaction->notes ?? 'N/A' }}">
    <x-heroicon-o-document-text class="w-4 h-4 text-purple-600 cursor-pointer" />
</a>


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
            <div class=" px-6 overflow-y-auto">
                <div class="bg-gray-50 rounded-lg mb-4 text-sm space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium" >Transaction:</span>
                        <span class="text-gray-700" id="Transaction-id">ORD-2025-001</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Date:</span>
                        <span class="text-gray-700" id="Transaction-date">Jan 15, 2025</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Amount:</span>
                        <span class="text-gray-700" id="Transaction-amount">$1,097.44</span>
                    </div>
                </div>

                 <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700 block mb-1">Note</label>
                    <div id="TransactionnoteContainer" class="border border-gray-300 rounded-md p-3 text-sm text-gray-700 bg-white">
                        Customer requested expedited processing due to urgent project deadline. Approved by management for priority handling.
                    </div>
                </div>

                 <!-- Footer -->
                <div id="actionButtons" class="flex justify-end gap-2 py-4">
                    <button id="cancelNoteBtn" class="px-4 py-2 border border-gray-300 rounded text-sm">Close</button>
                </div>

            </div>
        </div>
    </div>
</div>


   @push('js')
                    
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
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('noteModalWrapper');
        const closeBtn = document.getElementById('closeNoteModalBtn');
        const cancelBtn = document.getElementById('cancelNoteBtn');
        const openNoteBtns = document.querySelectorAll('.openNoteModalBtn');

        const transactionSpan = modalWrapper.querySelector('#Transaction-id');
        const dateSpan = modalWrapper.querySelector('#Transaction-date');
        const amountSpan = modalWrapper.querySelector('#Transaction-amount');
        const noteContainer = document.getElementById('TransactionnoteContainer');

        openNoteBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Get data from button
                const id = btn.getAttribute('data-id');
                const date = btn.getAttribute('data-date');
                const amount = btn.getAttribute('data-amount');
                const note = btn.getAttribute('data-note');

                // Set data into modal
                transactionSpan.textContent = id;
                dateSpan.textContent = date;
                amountSpan.textContent = amount;
                noteContainer.textContent = note;

                modalWrapper.style.display = 'flex';
            });
        });

        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        modalWrapper.addEventListener('click', (e) => {
            if (e.target === modalWrapper) {
                closeModal();
            }
        });
    });
</script>


                        
   @endpush