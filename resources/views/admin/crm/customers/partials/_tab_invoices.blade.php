 <div class="bg-white p-6 rounded shadow-sm space-y-6 mt-6">
     <!-- Header -->
     <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
         <div>
             <h2 class="text-2xl font-bold text-gray-900">Customer Invoices Management</h2>
             <p class="text-sm text-gray-600">Manage customer invoices, payments, and billing administration</p>
         </div>

         <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
             <a href="{{ route('admin.crm.customers.invoice.create',$customer->unique_id ) }}" target="_blank" class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-md font-medium">
                 + Create Invoice
             </a>
             <div class="text-left sm:text-right">
                 <p class="text-sm text-gray-600">Current Balance</p>
                 <p class="text-lg font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance) }}</p>
             </div>
         </div>
     </div>

 </div>

 <!-- Invoice Status Cards -->
 <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
     <!-- Paid -->
     <div class="bg-white border border-gray-200 rounded-md p-4 flex items-center justify-between shadow-sm">
         <div>
             <p class="text-sm text-gray-500">Paid Invoices</p>
             <p class="text-xl font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customer->total_paid_invoices) }} </p>
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
             <p class="text-xl font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customer->total_pending_invoices) }} </p>
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
             <p class="text-xl font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customer->total_overdue_invoices) }}</p>
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

 <div class="bg-white rounded-md shadow-sm mt-6 mb-6">
     <!-- Header with Filter -->
     <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 p-4">
         <h2 class="text-base font-semibold text-gray-800">Invoices</h2>
         <div>
             <label for="invoiceFilter" class="text-sm font-medium text-gray-700 mr-2">Filter by Status:</label>
             <select id="invoiceFilter" class="border border-gray-300 rounded-md px-3 py-1 text-sm">
                 <option value="all">All Orders</option>
                 <option value="paid">paid</option>
                 <option value="overdue">overdue</option>
                 <option value="pending">pending</option>
             </select>
         </div>
     </div>

     <!-- Table -->
     <div class="overflow-x-auto">
         <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
             <thead class="border-b bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                 <tr>
                     <th class="px-4 py-3">Invoice</th>
                     <th class="px-4 py-3">Customer</th>
                     <th class="px-4 w-32 py-3">Created</th>
                     <th class="px-4 w-32 py-3">Due Date</th>
                     <th class="px-4 w-32 py-3 text-right">Amount</th>
                     <th class="px-4 w-32 py-3 text-right">Mail Status</th>
                     <th class="px-4 w-32 py-3 text-right">Status</th>
                     <th class="px-4 py-3 w-24 text-right">Action</th>
                 </tr>
             </thead>
             <tbody id="invoiceTable" class="divide-y divide-gray-200">
             <tbody id="invoiceTable" class="divide-y divide-gray-200">
                 @forelse($customer->invoices as $invoice)
                 <tr class="invoice-row" data-status="{{ $invoice->invoice_status }}">
                     <td class="px-4 py-3 font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                     <td class="px-4 py-3 whitespace-nowrap  truncate min-w-3xs max-w-3xs">
                         <div class="text-sm">
                             <div class="font-medium text-gray-900">{{ $customer->company_name }}</div>
                             <div class="text-gray-500">{{ $customer->full_name }}</div>
                         </div>
                     </td>
                     <td class="px-4 py-3">

                         {{ App\Helpers\CustomHelper::formatDate($invoice->invoice_date) ?? '-' }}

                     </td>
                     <td class="px-4 py-3">
                         {{ App\Helpers\CustomHelper::formatDate($invoice->due_date) ?? '-' }}
                     </td>
                     <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">${{ number_format($invoice->total, 2) }}</td>

                     <td class="px-4 py-3 text-right">
                         @php
                         $mailstatusColors = [
                         'send' => ['color' => 'green', 'label' => 'Sent'],
                         'unsend' => ['color' => 'yellow', 'label' => 'Pending'],
                         ];

                         $statusKey = strtolower($invoice->is_email_send ?? '');
                         $statusData = $mailstatusColors[$statusKey] ?? ['color' => 'gray', 'label' => ucfirst($statusKey) ?: 'N/A'];
                         @endphp

                         <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-{{ $statusData['color'] }}-100 text-{{ $statusData['color'] }}-800">
                             {{ $statusData['label'] }}
                         </span>
                     </td>


                     <td class="px-4 py-3 text-right">
                         @php
                         $statusColors = [
                         'paid' => 'green',
                         'overdue' => 'red',
                         'pending' => 'yellow',
                         ];
                         $color = $statusColors[$invoice->invoice_status] ?? 'gray';
                         @endphp
                         <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                             {{ $invoice->invoice_status }}
                         </span>
                     </td>
                     <td class="px-4 py-3 whitespace-nowrap">
                         <div class="flex gap-2 items-center justify-end">
                             <a href="{{ route('admin.crm.customers.invoice.show', $invoice->unique_id) }}" target="_blank" class="text-blue-600 inline-flex items-center">
                                 <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                             </a>
                             <a href="{{ route('admin.crm.customers.invoice.download',$invoice->unique_id ) }}" class=" text-green-600 inline-flex items-center">
                                 <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />
                             </a>
                             <a href="{{ route('admin.crm.customers.invoice.edit', $invoice->unique_id) }}" target="_blank" class="text-green-600 inline-flex items-center">
                                 <x-heroicon-o-pencil-square class="w-4 h-4 text-green-600 mr-1" />
                             </a>
                             <a href="{{ route('admin.crm.customers.invoice.sendemail', $invoice->unique_id) }}" class="text-purple-600 inline-flex items-center">
                                 <x-heroicon-o-envelope class="w-4 h-4 text-gray-500 mr-1" />
                             </a>
                         </div>
                     </td>
                 </tr>
                 @empty
                 <tr>
                     <td colspan="7" class="text-center px-4 py-3">No invoices found</td>
                 </tr>
                 @endforelse
             </tbody>

             </tbody>
         </table>
     </div>
 </div>
