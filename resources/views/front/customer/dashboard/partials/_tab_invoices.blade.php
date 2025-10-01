 <div class="bg-white mt-6 p-6 rounded shadow-sm space-y-6 border border-gray-200">
     <!-- Header -->
     <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
         <div>
             <h2 class="text-2xl font-bold text-gray-900">Invoices</h2>
             <p class="text-sm text-gray-600">View and manage your invoices and payment methods</p>
         </div>

         <div class="flex items-center gap-4">

             <div class="text-left md:text-right">
                 <p class="text-sm text-gray-600">Current Balance</p>
                 <p class="text-lg font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance) }}</p>
             </div>
         </div>
     </div>
 </div>

 <!-- Invoice Status Cards -->
 <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
     <!-- Paid -->
     <div class="bg-white border border-gray-200 rounded-md p-4 flex items-center justify-between shadow-sm ">
         <div>
             <p class="text-sm text-gray-500">Paid Invoices</p>
             <p class="text-xl font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customer->total_paid_invoices) }}</p>
         </div>
         <div class="bg-green-100 p-2 rounded-md">
             <svg xmlns="http://www.w3.org/2000/svg"
                 width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 class="lucide lucide-check-circle w-6 h-6 text-green-500">
                 <path d="M9 12l2 2l4 -4"></path>
                 <circle cx="12" cy="12" r="10"></circle>
             </svg>
         </div>
     </div>

     <!-- Pending -->
     <div class="bg-white border border-gray-200 rounded-md p-4 flex items-center justify-between shadow-sm">
         <div>
             <p class="text-sm text-gray-500">Pending Invoices</p>
             <p class="text-xl font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customer->total_pending_invoices) }}</p>
         </div>
         <div class="bg-yellow-100 p-2 rounded-md">
             <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
             </svg>
         </div>
     </div>

     <!-- Overdue -->
     <div class="bg-white border border-gray-200 rounded-md p-4 flex items-center justify-between shadow-sm ">
         <div>
             <p class="text-sm text-gray-500">Overdue Invoices</p>
             <p class="text-xl font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customer->total_overdue_invoices) }}</p>
         </div>
         <div class="bg-red-100 p-2 rounded-md  text-red-500">
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
     </div>
 </div>

 <div class="bg-white rounded-md shadow-sm border border-gray-200 mt-6 mb-6">
     <!-- Header with Filter -->
     <div class="flex flex-col p-4 md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200">
         <h2 class="text-base font-semibold text-gray-800">Invoices</h2>
     </div>

     <!-- Table -->
     <div class="overflow-x-auto">
         <table class="min-w-full text-sm text-left text-gray-700">
             <thead class="bg-gray-50 text-gray-500 text-xs border-b border-gray-200">
                 <tr>
                     <th class="px-4 py-3 font-medium uppercase">Invoice</th>
                     <th class="px-4 py-3 font-medium uppercase">Date</th>
                     <th class="px-4 py-3 font-medium uppercase">Due Date</th>
                     <th class="px-4 py-3 font-medium uppercase">Amount</th>
                     <th class="px-4 py-3 font-medium uppercase">Status</th>
                     <th class="px-4 py-3 font-medium uppercase">Action</th>
                 </tr>
             </thead>
             <tbody id="invoiceTable" class="divide-y divide-gray-200">

                 @forelse($customer->invoices as $invoice)
                 <tr>
                     <td class="px-4 py-3 font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                     <td class="px-4 py-3"> {{ App\Helpers\CustomHelper::formatDate($invoice->invoice_date) ?? '-' }}</td>
                     <td class="px-4 py-3"> {{ App\Helpers\CustomHelper::formatDate($invoice->due_date) ?? '-' }}</td>
                     <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-500">{{ App\Helpers\CustomHelper::formatCurrency($invoice->total) ?? '-' }} </td>
                     <td class="px-4 py-3">

                         @php
                         $statusColors = [
                         'paid' => 'green',
                         'overdue' => 'red',
                         'pending' => 'yellow',
                         ];
                         $color = $statusColors[$invoice->invoice_status] ?? 'gray';
                         @endphp

                         <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-{{ $color }}-100 text-{{ $color }}-800"> {{ $invoice->invoice_status }}</span>

                     </td>
                     <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                         <a href="{{ route('front.customer.dashboard.invoice.view',$invoice->unique_id ) }}" target="_blank" class="text-blue-600 inline-flex items-center">
                             <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                         </a>


                         <a href="{{ route('front.customer.dashboard.invoice.download',$invoice->unique_id ) }}" class="text-green-600 inline-flex items-center cursor-pointer">
                             <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />
                         </a>

                         <a href="javascript:void(0)" class="text-purple-600 inline-flex items-center cursor-pointer openDiscountModal"
                             data-invoice="{{ $invoice->invoice_number }}"
                             data-due="{{ App\Helpers\CustomHelper::formatDate($invoice->due_date) }}"
                             data-amount="{{ App\Helpers\CustomHelper::formatCurrency($invoice->total) }}">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4 mr-2">
                                 <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                 <line x1="2" x2="22" y1="10" y2="10"></line>
                             </svg>
                         </a>

                     </td>
                 </tr>
                 @empty
                 <tr>
                     <td colspan="6" class="text-center px-4 py-3">No invoices found</td>
                 </tr>
                 @endforelse

             </tbody>
         </table>
     </div>
 </div>


 <!-- New Discount Wrapper -->
 <div id="discountModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
     <div class="modal-scrollable w-full mx-auto">
         <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
             <div class="flex justify-between items-center px-6 pt-4">
                 <div class="flex items-center gap-2">
                     <h2 class="text-lg font-medium text-gray-900">Pay Invoice</h2>
                 </div>
                 <button id="closeDiscountModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
             </div>

             <!-- Body -->
             <div class="px-6 space-y-5">
                 <!-- Invoice Info -->
                 <div class="rounded-md space-y-2">
                     <div class="flex justify-between text-sm">
                         <span class="text-sm font-medium text-gray-500">Invoice Number:</span>
                         <span class="text-sm font-semibold text-gray-900" data-field="invoice"></span>
                     </div>
                     <div class="flex justify-between text-sm">
                         <span class="text-sm font-medium text-gray-500">Due Date:</span>
                         <span class="text-sm text-gray-900" data-field="due"></span>
                     </div>
                     <div class="flex justify-between text-sm">
                         <span class="text-sm font-medium text-gray-500">Amount Due:</span>
                         <span class="text-lg font-bold text-gray-900" data-field="amount"></span>
                     </div>
                 </div>

                 <!-- Secure Payment Info -->
                 <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                     <div class="flex items-center">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 text-blue-600 mr-3">
                             <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                             <line x1="2" x2="22" y1="10" y2="10"></line>
                         </svg>
                         <div>
                             <p class="text-sm font-medium text-blue-900">Secure Payment Processing</p>
                             <p class="text-xs text-blue-700 mt-1">Payments are processed securely through our payment gateway. You'll enter your payment details on the next step.</p>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- Footer -->
             <div class="flex space-x-3 gap-3 px-6 py-4">
                 <button id="cancelDiscountBtn" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                     Cancel
                 </button>
                 <button class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                     Pay <span data-field="Payamount"></span>
                 </button>
             </div>
         </div>
     </div>
 </div>


 @push('js')
 <script>
     document.addEventListener('DOMContentLoaded', () => {
         const modalWrapper = document.getElementById('discountModalWrapper');
         const openBtns = document.querySelectorAll('.openDiscountModal');
         const closeBtn = document.getElementById('closeDiscountModalBtn');
         const cancelBtn = document.getElementById('cancelDiscountBtn');

         // Loop through all open buttons
         openBtns.forEach(btn => {
             btn.addEventListener('click', () => {
                 document.querySelector('#discountModalWrapper [data-field="invoice"]').textContent = btn.dataset.invoice;
                 document.querySelector('#discountModalWrapper [data-field="due"]').textContent = btn.dataset.due;
                 document.querySelector('#discountModalWrapper [data-field="amount"]').textContent = btn.dataset.amount;
                 document.querySelector('#discountModalWrapper [data-field="Payamount"]').textContent = btn.dataset.amount;

                 modalWrapper.style.display = 'flex';
             });
         });

         const closeModal = () => {
             modalWrapper.style.display = 'none';
         };

         if (closeBtn) closeBtn.addEventListener('click', closeModal);
         if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
     });
 </script>
 @endpush
