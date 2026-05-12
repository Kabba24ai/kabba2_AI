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
               
               class="bg-green-600 hover:bg-green-700 text-white text-md px-6 py-3 rounded-md font-medium">
                + Create From Account 
            </a>

             {{-- <a href="{{ route('admin.crm.customers.invoice.create',$customer->unique_id ) }}"  class="bg-green-600 hover:bg-green-700 text-white text-md px-6 py-3 rounded-md font-medium">
                 + Create Invoice
             </a> --}}
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
                     <th class="py-4 px-6 w-32 text-right">Mail Date</th>

                     <th class="py-4 px-6 w-32 text-right">Email Status</th>

                     <th class="py-4 px-6 w-24 text-center">Action</th>
                 </tr>
             </thead>

             <tbody id="invoiceTable" class="divide-y divide-gray-200">
                 @forelse($customer->invoices as $invoice)
                 <tr class="invoice-row" data-status="{{ $invoice->invoice_status }}" data-invoice-id="{{ $invoice->open_amount }}">
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

                        <td class="py-4 px-6 text-right text-red-600 font-semibold"
                            data-open-amount="{{
                                $invoice->invoice_status === 'paid'
                                    ? 0
                                    : ($invoice->open_amount > 0
                                        ? $invoice->open_amount
                                        : ($invoice->total > 0 ? $invoice->total : 0))
                            }}">
                            $
                            {{
                                number_format(
                                    $invoice->invoice_status === 'paid'
                                        ? 0
                                        : ($invoice->open_amount > 0
                                            ? $invoice->open_amount
                                            : ($invoice->total > 0 ? $invoice->total : 0)),
                                    2
                                )
                            }}
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

                        

                
                        @if($invoice->is_mail === 'yes' && $invoice->is_mail_date)
                            <span class="ml-2 text-gray-600 text-[11px]">
                                {{ App\Helpers\CustomHelper::formatDate($invoice->is_mail_date) ?? '-' }}
                            </span>

                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                            Pending
                        </span>
                        @endif

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
                             {{ App\Helpers\CustomHelper::formatDateTime($invoice->mail_send_at) ?? '-' }}
                         </span>
                         @else

                         <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-{{ $statusData['color'] }}-100 text-{{ $statusData['color'] }}-800">
                             {{ $statusData['label'] }}
                         </span>
                         @endif
                     </td>

                     <td class="py-4 px-6 whitespace-nowrap">
                         <div class="flex gap-2 items-center justify-end">

                            @if($invoice->invoice_status !== 'paid')
                                <button  class="text-green-600 inline-flex items-center i_openPaymentModal" data-invoice-id="{{ $invoice->id }}"  data-remaing-amount="{{ $invoice->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4 mr-2">
                                        <line x1="12" x2="12" y1="2" y2="22"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                </button>
                            @endif

                             <a href="{{ route('admin.crm.customers.invoice.show', $invoice->unique_id) }}"  class="text-blue-600 inline-flex items-center">
                                 <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                             </a>
                             <a href="{{ route('admin.crm.customers.invoice.download',$invoice->unique_id ) }}" class=" text-green-600 inline-flex items-center">
                                 <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />
                             </a>
                             <a href="{{ route('admin.crm.customers.invoice.edit', $invoice->unique_id) }}"  class="text-green-600 inline-flex items-center">
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


                             <!-- Transaction delete button -->
                                    <form action="{{ route('admin.crm.customers.invoice.delete-invoice', $invoice->unique_id) }}"
                                        method="POST"
                                        class="flex delete-invoice-form"
                                        data-InvoiceNumber="{{ $invoice->invoice_number }}">
                                        @csrf
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-800"
                                                title="Delete">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </form>


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

{{-- invoice payemnt  --}}

<!-- Record Payment Wrapper -->
   <div id="i_InvoicePaymentModal" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
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
                   <button id="close_i_ModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
               </div>
               <div class=" px-6 overflow-y-auto">
                   <!-- <form> -->

                   {{ html()->form('POST', route('admin.crm.customers.invoice.paymentstore'))->id('InvoicePaymentForm')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->open() }}

                   {!! html()->text('customer_id', $customer->id ?? '')->class('hidden')->attributes([
                   'id' => 'customer_id',
                   'autocomplete' => 'off'
                   ]) !!}

                  {!! html()->text('invoice_id')->class('hidden')->attributes([
                   'id' => 'invoice_id',
                   'autocomplete' => 'off',
                   ]) !!}


                   <!-- Payment Amount -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Payment Amount </label>
                       <div class="relative">
                           <span class="absolute inset-y-0 left-0 h-[36px] pl-3 flex items-center text-gray-500">$</span>

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
                            collect(\App\Enums\Customers\PaymentMethod::options())
                                ->except(\App\Enums\Customers\PaymentMethod::Other->value)
                                ->toArray()
                        )
                        ->id('i_payment_type')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                        ->required()
                        !!}

                   </div>

                   <!-- Cheque Number (hidden by default) -->
                   <div id="i_chequeNumberField" class="mb-4 hidden">
                       <label for="cheque_number" class="block text-sm font-medium text-gray-700 mb-1 ">
                           Check Number
                       </label>
                       <input
                           type="text"
                           id="i_cheque_number"
                           name="cheque_number"
                           class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700"
                           placeholder="Enter Check number" />
                   </div>


                   <!-- Card Options -->
                   <div id="i_creditCardOptions" class="mb-4 hidden">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Card Options </label>
                       <select id="i_cardOption" name="card_option"
                           class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                           <option value="NewCard" selected>New Card</option>

                           @if ($customer->cards && $customer->cards->count() > 0)
                           <option value="CardOnFile">Card on File</option>
                           @endif

                       </select>
                   </div>

                   <!-- New Card Fields -->
                   <div id="i_newCardFields" class="mb-4 hidden">
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
                               <input type="text" placeholder="Card number" maxlength="19" id="i_cardNumber" name="cardNumber"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                           <div class="md:col-span-1">
                               <input type="text" placeholder="MM/YY" maxlength="5" id="i_expiry" name="expiry"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                           <div class="md:col-span-1">
                               <input type="text" placeholder="CVC" maxlength="4" id="i_cvc" name="cvc"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                       </div>
                       <input type="hidden" name="opaqueDataValue" id="i_opaqueDataValue" />
                       <input type="hidden" name="opaqueDataDescriptor" id="i_opaqueDataDescriptor" />
                   </div>

                   <!-- Card on File Dropdown -->
                   @if ($customer->cards && $customer->cards->count() > 0)
                   <div id="i_cardOnFileDropdown" class="mb-4 hidden">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Select Existing Card </label>
                       <select name="existing_card_id"
                           class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                           <option value="">-- Select a saved card --</option>
                           @foreach ($customer->cards as $card)
                           <option value="{{ $card->unique_id }}">{{ $card->card_number }}</option>
                           @endforeach
                       </select>
                   </div>

                   @else

                   <div id="i_cardOnFileDropdown" class="mb-4 hidden"> </div>

                   @endif

                   <!-- Person Responsible -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible </label>



                       {!! html()
                       ->select('responsible_person',
                       $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                       old('responsible_person')
                       )
                       ->id('i_responsible_person')
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
                       <button type="button" id="cancelInvoicePaymentBtn" class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">
                           Cancel
                       </button>
                       <button type="submit" id="i_submitTemplatesBtn" class="relative px-6 py-3 text-md rounded-lg bg-teal-600 text-white flex items-center justify-center gap-2 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                           <span id="i_btnText">Record Payment</span>
                           <svg id="i_btnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
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

{{-- invoice payment   --}}

@push('js')


    <!-- delete invoice script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-invoice-form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // stop auto submit

                    const InvoiceNumber = form.getAttribute('data-InvoiceNumber') || 'this Invoice';

                    window.showConfirm(
                        `Delete "${InvoiceNumber}"? This action cannot be undone!`,
                        'Delete Invoice'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
    <!-- delete invoice script -->

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


{{-- -----invoice payment -------- --}}


<!-- JS Section -->
<script>
    document.addEventListener('DOMContentLoaded', () => {

          //  reset and re-sync form fields inside a modal
        function resetForm(modalWrapper) {
            const form = modalWrapper.querySelector('form');

            const amountInput2 = modalWrapper.querySelector('input[name="amount"]');

            const paymentTypeSelect2 = modalWrapper.querySelector('select[name="payment_type"]');
            const cardOptionSelect2 = modalWrapper.querySelector('#i_cardOption');
            const cardOnFileDropdown2 = modalWrapper.querySelector('#i_cardOnFileDropdown');
            const existingCardSelect2 = modalWrapper.querySelector('select[name="existing_card_id"]');

            const chequeNumberInput = modalWrapper.querySelector('#i_chequeNumberField');

            // Re-enable CreditCard option if it exists
            const paymentType2 = modalWrapper.querySelector('select[name="payment_type"]');
            if (paymentType2) {
                const creditCardOption = paymentType2.querySelector('option[value="CreditCard"]');
                if (creditCardOption) {
                    creditCardOption.hidden = false;
                }
            }


            // At the start of modal open (before filling data)
            if (amountInput2) amountInput2.readOnly = false;
            if (paymentTypeSelect2) paymentTypeSelect2.disabled = false;

            if (cardOptionSelect2) {
                cardOptionSelect2.disabled = false;
                cardOptionSelect2.value = ""; // reset to default
            }

            if (cardOnFileDropdown2) cardOnFileDropdown2.classList.add('hidden');
            chequeNumberInput.classList.add('hidden');

            if (existingCardSelect2) {
                existingCardSelect2.disabled = false;
                existingCardSelect2.style.display = "";

                // Remove masked <p> if previously added
                const nextSibling2 = existingCardSelect2.nextElementSibling;
                if (nextSibling2 && nextSibling2.tagName === "P" && nextSibling2.textContent.includes("****")) {
                    nextSibling2.remove();
                }
            }

            if (form) form.reset(); // clear all normal inputs

            // Reset custom digit inputs properly
            const digitInputs = modalWrapper.querySelectorAll("input[data-digit-input='true']");
            digitInputs.forEach(input => {
                // Force display to 0.00
                input.value = "0.00";

                // Also reset internal digits variable if exists
                if (input.hasOwnProperty('digits')) input.digits = "";

                // Trigger input event so Parsley updates validation
                const event = new Event('input', {
                    bubbles: true
                });
                input.dispatchEvent(event);
            });

            // hide optional sections back to defaults
            const creditCardOptions = modalWrapper.querySelector('#i_creditCardOptions');
            const newCardFields = modalWrapper.querySelector('#i_newCardFields');
            const cardOnFileDropdown = modalWrapper.querySelector('#i_cardOnFileDropdown');
            [creditCardOptions, newCardFields, cardOnFileDropdown].forEach(el => {
                if (el) el.classList.add('hidden');
            });

            // Reset payment type and card option
            const paymentType = modalWrapper.querySelector('#i_payment_type');
            if (paymentType) paymentType.value = '';
            const cardOption = modalWrapper.querySelector('#i_cardOption');
            if (cardOption) cardOption.value = 'NewCard';
        }

        

            function setupModal(openBtnclass, modalWrapperId, closeBtnId, cancelBtnId) {

                const modalWrapper = document.getElementById(modalWrapperId);
                const openBtns = document.querySelectorAll(openBtnclass);
                const closeBtn = document.getElementById(closeBtnId);
                const cancelBtn = document.getElementById(cancelBtnId);
            
                if (!modalWrapper || !openBtns || !closeBtn || !cancelBtn) return;
    
                

                openBtns.forEach(btn => {
                    btn.addEventListener('click', function () {

                        const invoiceId = this.dataset.invoiceId;
                        const row = this.closest('tr');

                        const openAmount = parseFloat(
                            row.querySelector('[data-open-amount]')?.dataset.openAmount || 0
                        );

                        const amountInputtext = modalWrapper.querySelector('input[name="amount"]');

                        if (amountInputtext) {
                            amountInputtext.dataset.max = openAmount;   
                        }

                        // Reset modal first
                        resetForm(modalWrapper);

                        // Set hidden invoice_id
                        const invoiceInput = modalWrapper.querySelector('#invoice_id');
                        if (invoiceInput) {
                            invoiceInput.value = invoiceId;
                        }

                        // Show modal
                        modalWrapper.style.display = 'flex';

                        // Set amount input
                        const amountInput = modalWrapper.querySelector('input[name="amount"]');

                        if (amountInput) {

                            const formatted = openAmount.toFixed(2);
                            amountInput.value = formatted;

                            if (amountInput.hasOwnProperty('digits')) {
                                amountInput.digits = formatted.replace('.', '');
                            }

                            amountInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                });

            
                // Close
                const closeModal = () => {
                    modalWrapper.style.display = 'none';
                    resetForm(modalWrapper); //  Clear fields and restore defaults

                    //  Clear Parsley validation if exists
                    const form = modalWrapper.querySelector('form');
                    if (form && $(form).parsley) {
                        $(form).parsley().reset();
                    }

                };

                closeBtn.addEventListener('click', closeModal);
                cancelBtn.addEventListener('click', closeModal);

            }

           setupModal('.i_openPaymentModal', 'i_InvoicePaymentModal', 'close_i_ModalBtn', 'cancelInvoicePaymentBtn');


          const paymentForm = document.getElementById('InvoicePaymentForm');

            paymentForm.addEventListener('input', function (e) {

                if (e.target.name === 'amount') {

                    const input = e.target;
                    const max = parseFloat(input.dataset.max || 0);
                    let value = parseFloat(input.value || 0);

                    if (value > max) {
                        input.value = max.toFixed(2);

                        notyf.error("Amount cannot exceed remaining balance");
                    }
                }
            });

    });
</script>



   @if ($paymentSetting['payment_test_mode'] ?? false)
   <script src="https://jstest.authorize.net/v1/Accept.js"></script>
   @else
   <script src="https://js.authorize.net/v1/Accept.js"></script>
   @endif

   <script>
       document.addEventListener('DOMContentLoaded', function() {
           // console.log(" DOM fully loaded");

           const form = document.getElementById('InvoicePaymentForm');
           const paymentType = document.getElementById('i_payment_type');
           const creditCardOptions = document.getElementById('i_creditCardOptions');
           const cardOption = document.getElementById('i_cardOption');
           const newCardFields = document.getElementById('i_newCardFields');
           const cardOnFileDropdown = document.getElementById('i_cardOnFileDropdown');
           const cardNumberInput = document.getElementById('i_cardNumber');
           const expiryInput = document.getElementById('i_expiry');
           const cvcInput = document.getElementById('i_cvc');
           const submitBtn = document.getElementById('i_submitTemplatesBtn');
           const btnText = document.getElementById('i_btnText');
           const btnSpinner = document.getElementById('i_btnSpinner');
           const chequeNumberField = document.getElementById('i_chequeNumberField');


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

                       document.getElementById('i_opaqueDataValue').value = response.opaqueData.dataValue;
                       document.getElementById('i_opaqueDataDescriptor').value = response.opaqueData.dataDescriptor;

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



{{-- -----invoice payment -------- --}}




@endpush

