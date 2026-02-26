 <div class="bg-white p-6 rounded-xl shadow-sm space-y-6 mt-6">
     <!-- Header -->
     <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
         <div>
             <h2 class="text-2xl font-bold text-gray-900">Customer Invoices Management</h2>
             <p class="text-sm text-gray-600">Manage customer invoices, payments, and billing administration</p>
         </div>

         <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
             <!-- Create Account Invoice Button -->
            <a href="{{ route('admin.crm.customers.invoice.create-from-account',$customer->unique_id ) }}" 
               target="_blank"
               class="bg-green-600 hover:bg-green-700 text-white text-md px-6 py-3 rounded-md font-medium">
                + Create From Account 
            </a>

             <a href="{{ route('admin.crm.customers.invoice.create',$customer->unique_id ) }}" target="_blank" class="bg-green-600 hover:bg-green-700 text-white text-md px-6 py-3 rounded-md font-medium">
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
     <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
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
     <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
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
     <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center justify-between shadow-sm ">
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

 <div class="bg-white rounded-2xl shadow-sm mt-6 mb-6">
     <!-- Header with Filter -->
     <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 p-4">
         <h2 class="text-base font-semibold text-gray-800">Invoices</h2>
         <div>
             <label for="invoiceFilter" class="text-sm font-medium text-gray-700 mr-2">Filter by Status:</label>
             <select id="invoiceFilter" class="border border-gray-300 rounded-md px-3 py-3 text-sm">
                 <option value="all">All Orders</option>
                 <option value="paid">Paid</option>
                  <option value="partial_paid">Partial Paid</option>
                 <option value="overdue">Overdue</option>
                 <option value="pending">Pending</option>
             </select>
         </div>
     </div>

     <!-- Table -->
     <div class="overflow-x-auto relative" id="invoiceTableWrapper">


         <!-- Loader (only covers table area) -->
         <div id="tableLoader"
             class="hidden absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-black/60 z-50">
             <div
                 class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent">
             </div>
         </div>

         <!-- Table -->
         <table id="invoiceMainTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
             <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
                 <tr>
                     <th class="py-4 px-6">Invoice</th>
                     <th class="py-4 px-6">Customer</th>
                     <th class="py-4 px-6 w-32">Created</th>
                     <th class="py-4 px-6 w-32">Due Date</th>
                     <th class="py-4 px-6 w-32 text-right">Amount</th>
                          <th class="py-4 px-6 w-32 text-right">Paid Amount</th>
                               <th class="py-4 px-6 w-32 text-right">Open Amount</th>
                     <th class="py-4 px-6 w-32 text-right">Payment Status</th>
                     <th class="py-4 px-6 w-32 text-right">Mail Status</th>

                     <th class="py-4 px-6 w-24 text-right">Action</th>
                 </tr>
             </thead>

             <tbody id="invoiceTable" class="divide-y divide-gray-200">
                 @forelse($customer->invoices as $invoice)
                 <tr class="invoice-row" data-status="{{ $invoice->invoice_status }}">
                     <td class="py-4 px-6 font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                     <td class="py-4 px-6 whitespace-nowrap  truncate min-w-3xs max-w-3xs">
                         <div class="text-sm">
                             <div class="font-medium text-gray-900">{{ $customer->company_name }}</div>
                             <div class="text-gray-500">{{ $customer->full_name }}</div>
                         </div>
                     </td>
                     <td class="py-4 px-6">

                         {{ App\Helpers\CustomHelper::formatDate($invoice->invoice_date) ?? '-' }}

                     </td>
                     <td class="py-4 px-6">
                         {{ App\Helpers\CustomHelper::formatDate($invoice->due_date) ?? '-' }}
                     </td>
                     <td class="py-4 px-6 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">${{ number_format($invoice->total, 2) }}</td>

                       {{-- Paid Amount --}}
                    <td class="py-4 px-6 text-right text-green-600 font-semibold">
                        ${{ number_format($invoice->paid_amount ?? 0, 2) }}
                    </td>

                    {{-- Open Amount --}}
                    <td class="py-4 px-6 text-right text-red-600 font-semibold">
                        ${{ number_format($invoice->open_amount ?? 0, 2) }}
                    </td>


                     <td class="py-4 px-6 text-right">
                        @php
                            $statusColors = [
                                'paid' => 'green',
                                'partial_paid' => 'blue',
                                'overdue' => 'red',
                                'pending' => 'yellow',
                            ];

                            $color = $statusColors[$invoice->invoice_status] ?? 'gray';

                            // Format label nicely
                            $statusLabel = match ($invoice->invoice_status) {
                                'partial_paid' => 'Partial Paid',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'pending' => 'Pending',
                                default => ucfirst(str_replace('_', ' ', $invoice->invoice_status)),
                            };
                        @endphp

                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                            {{ $statusLabel }}
                        </span>
                    </td>

                     <td class="py-4 px-6 text-right">
                         @php
                         $mailstatusColors = [
                         'send' => ['color' => 'green', 'label' => 'Sent'],
                         'unsend' => ['color' => 'yellow', 'label' => 'Pending'],
                         ];

                         $statusKey = strtolower($invoice->is_email_send ?? '');
                         $statusData = $mailstatusColors[$statusKey] ?? ['color' => 'gray', 'label' => ucfirst($statusKey) ?: 'N/A'];
                         @endphp
                         @if($invoice->is_email_send === 'send' && $invoice->mail_send_at)
                         {{-- Show the date next to "Sent" --}}
                         <span class="ml-2 text-gray-600 text-[11px]">
                             {{ App\Helpers\CustomHelper::formatDate($invoice->mail_send_at) ?? '-' }}
                         </span>
                         @else


                         <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-{{ $statusData['color'] }}-100 text-{{ $statusData['color'] }}-800">
                             {{ $statusData['label'] }}
                         </span>
                         @endif
                     </td>


                     <td class="py-4 px-6 whitespace-nowrap">
                         <div class="flex gap-2 items-center justify-end">

                            {{-- @if($invoice->invoice_status !== 'paid')
                            <button id="openPaymentModal" class="text-green-600 inline-flex items-center">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4 mr-2">
                                    <line x1="12" x2="12" y1="2" y2="22"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </button>
                            @endif --}}

                             <a href="{{ route('admin.crm.customers.invoice.show', $invoice->unique_id) }}" target="_blank" class="text-blue-600 inline-flex items-center">
                                 <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                             </a>
                             <a href="{{ route('admin.crm.customers.invoice.download',$invoice->unique_id ) }}" class=" text-green-600 inline-flex items-center">
                                 <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />
                             </a>
                             <a href="{{ route('admin.crm.customers.invoice.edit', $invoice->unique_id) }}" target="_blank" class="text-green-600 inline-flex items-center">
                                 <x-heroicon-o-pencil-square class="w-4 h-4 text-green-600 mr-1" />
                             </a>
                             {{-- <a href="{{ route('admin.crm.customers.invoice.sendemail', $invoice->unique_id) }}" class="text-purple-600 inline-flex items-center send-invoice-email">
                                 <x-heroicon-o-envelope class="w-4 h-4 text-gray-500 mr-1" />
                             </a> --}}

                             <a href="javascript:void(0);"
                                class="text-purple-600 inline-flex items-center send-invoice-email"
                                data-invoice-id="{{ $invoice->unique_id }}"
                                data-billing-email="{{ optional($invoice->customer->billingAddress)->email }}"
                                data-customer-email="{{ optional($invoice->customer)->email }}">
                                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-500 mr-1" />
                                </a>


                         </div>
                     </td>
                 </tr>
                 @empty
                 <tr>
                     <td colspan="7" class="text-center py-4 px-6">No invoices found</td>
                 </tr>
                 @endforelse
             </tbody>


         </table>
     </div>
 </div>
<div id="sendInvoiceModalWrapper"
     style="display: none;"
     class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- Header -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-purple-600">
                        <x-heroicon-o-envelope class="w-5 h-5"/>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">
                        Send Invoice
                    </h2>
                </div>
                <button id="closeSendInvoiceModalBtn"
                        class="text-gray-400 hover:text-gray-700 text-xl">
                    &times;
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 overflow-y-auto">

         <form id="sendInvoiceForm"
                    method="POST"
                    action="{{ route('admin.crm.customers.invoice.sendemail') }}"
                    class="space-y-6">
                    @csrf

               

                    <input type="hidden" id="invoice_id" name="invoice_id">

                    <!-- Billing Email -->
                    <div class="flex items-center gap-3">
                        <input type="checkbox"
                               id="billing_email_checkbox"
                             name="send_billing"
                             value="1"
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                               checked>

                        <label for="billing_email_checkbox"
                               class="text-sm text-gray-700">
                            Billing Email:
                            <span id="billing_email_text"
                                  class="font-medium text-gray-900"></span>
                        </label>
                    </div>

                    <!-- Customer Email -->
                    <div class="flex items-center gap-3">
                        <input type="checkbox"
                               id="customer_email_checkbox"
                              name="send_customer"
                                 value="1"
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                               checked>

                        <label for="customer_email_checkbox"
                               class="text-sm text-gray-700">
                            Customer Email:
                            <span id="customer_email_text"
                                  class="font-medium text-gray-900"></span>
                        </label>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-2 pb-4">
                        <button type="button"
                                id="cancelSendInvoiceBtn"
                                class="px-4 py-2 flex-1 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>

                        <button type="submit"
                                id="submitSendInvoiceBtn"
                                class="relative flex-1 px-4 py-2 text-sm rounded bg-purple-600 text-white flex items-center justify-center gap-2">
                            <span id="sendInvoiceBtnText">Send Invoice</span>

                            <svg id="sendInvoiceBtnSpinner"
                                 xmlns="http://www.w3.org/2000/svg"
                                 class="hidden animate-spin h-5 w-5 text-white"
                                 fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25"
                                        cx="12" cy="12" r="10"
                                        stroke="currentColor"
                                        stroke-width="4"></circle>
                                <path class="opacity-75"
                                      fill="currentColor"
                                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<!-- New Refund Wrapper -->
@include('admin.crm.customers.partials._invoice_payment')


@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('sendInvoiceModalWrapper');
    const closeBtn = document.getElementById('closeSendInvoiceModalBtn');
    const cancelBtn = document.getElementById('cancelSendInvoiceBtn');
    const form = document.getElementById('sendInvoiceForm');

    const billingCheckbox = document.getElementById('billing_email_checkbox');
    const customerCheckbox = document.getElementById('customer_email_checkbox');
    const billingText = document.getElementById('billing_email_text');
    const customerText = document.getElementById('customer_email_text');

    document.querySelectorAll('.send-invoice-email').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const invoiceId = this.dataset.invoiceId;
            const billingEmail = this.dataset.billingEmail;
            const customerEmail = this.dataset.customerEmail;

            document.getElementById('invoice_id').value = invoiceId;

            // Set email text
            billingText.innerText = billingEmail ?? 'Not available';
            customerText.innerText = customerEmail ?? 'Not available';

            // Billing email handling
            if (billingEmail) {
                billingCheckbox.checked = true;
                billingCheckbox.disabled = false;
            } else {
                billingCheckbox.checked = false;
                billingCheckbox.disabled = true;
            }

            // Customer email handling
            if (customerEmail) {
                customerCheckbox.checked = true;
                customerCheckbox.disabled = false;
            } else {
                customerCheckbox.checked = false;
                customerCheckbox.disabled = true;
            }

            modal.style.display = 'flex';
        });
    });

    function closeModal() {
        modal.style.display = 'none';
        form.reset();
    }

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    form.addEventListener('submit', function (e) {

        const billingChecked = billingCheckbox.checked;
        const customerChecked = customerCheckbox.checked;

        if (!billingChecked && !customerChecked) {
            e.preventDefault();
            
            notyf.error("Please select at least one email.");
            return;
        }

        const spinner = document.getElementById('sendInvoiceBtnSpinner');
        const text = document.getElementById('sendInvoiceBtnText');

        spinner.classList.remove('hidden');
        text.innerText = 'Sending...';
    });

});
</script>
@endpush

