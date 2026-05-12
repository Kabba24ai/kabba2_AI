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
                 <p class="text-lg font-bold text-gray-900">
                     {{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance) }}</p>
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
             <p class="text-xl font-semibold text-gray-900">
                 {{ \App\Helpers\CustomHelper::formatCurrency($customer->total_paid_invoices) }}</p>
         </div>
         <div class="bg-green-100 p-2 rounded-md">
             <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
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
             <p class="text-xl font-semibold text-gray-900">
                 {{ \App\Helpers\CustomHelper::formatCurrency($customer->total_pending_invoices) }}</p>
         </div>
         <div class="bg-yellow-100 p-2 rounded-md">
             <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                     d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
             </svg>
         </div>
     </div>

     <!-- Overdue -->
     <div class="bg-white border border-gray-200 rounded-md p-4 flex items-center justify-between shadow-sm ">
         <div>
             <p class="text-sm text-gray-500">Overdue Invoices</p>
             <p class="text-xl font-semibold text-gray-900">
                 {{ \App\Helpers\CustomHelper::formatCurrency($customer->total_overdue_invoices) }}</p>
         </div>
         <div class="bg-red-100 p-2 rounded-md  text-red-500">
             <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
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
         <table class="min-w-full text-sm text-left text-gray-700 whitespace-nowrap">
             <thead class="bg-gray-50 text-gray-500 text-xs border-b border-gray-200">
                 <tr>
                     <th class="px-4 py-3 font-medium uppercase">Invoice</th>
                     <th class="px-4 py-3 font-medium uppercase">Date</th>
                     <th class="px-4 py-3 font-medium uppercase">Due Date</th>
                     <th class="px-4 py-3 font-medium uppercase">Amount</th>
                      <th class="px-4 py-3 font-medium uppercase">Mail Date</th>

                     <th class="px-4 py-3 font-medium uppercase">Email Status</th>

                     {{-- <th class="px-4 py-3 font-medium uppercase">Payment Status</th> --}}
                     <th class="px-4 py-3 font-medium uppercase">Action</th>
                 </tr>
             </thead>
             <tbody id="invoiceTable" class="divide-y divide-gray-200">

                 @forelse($customer->invoices as $invoice)
                     <tr>
                         <td class="px-4 py-3 font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                         <td class="py-4 px-6">

                         {{ App\Helpers\CustomHelper::formatDate($invoice->invoice_date) ?? '-' }}

                     </td>
                     <td class="py-4 px-6">
                         {{ App\Helpers\CustomHelper::formatDate($invoice->due_date) ?? '-' }}
                     </td>
                     <td class="py-4 px-6 whitespace-nowrap text-sm font-semibold text-gray-900 ">${{ number_format($invoice->total, 2) }}</td>

                   
                    

               
                        <td class="py-4 px-6  text-red-600 font-semibold"
                             >
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

                     <td class="py-4 px-6 ">
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

                     
                         <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                             <a href="{{ route('front.customer.dashboard.invoice.view', $invoice->unique_id) }}"
                                 class="text-blue-600 inline-flex items-center">
                                 <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                             </a>


                             <a href="{{ route('front.customer.dashboard.invoice.download', $invoice->unique_id) }}"
                                 class="text-green-600 inline-flex items-center cursor-pointer">
                                 <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />
                             </a>

                             {{-- <a href="javascript:void(0)"
                                 class="{{ $invoice->invoice_status !== 'paid' ? 'text-purple-600' : 'text-gray-600' }} inline-flex items-center cursor-pointer @if ($invoice->invoice_status !== 'paid') openDiscountModal @endif "
                                 data-invoice="{{ $invoice->invoice_number }}"
                                 data-due="{{ App\Helpers\CustomHelper::formatDate($invoice->due_date) }}"
                                 >
                                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round"
                                     class="lucide lucide-credit-card w-4 h-4 mr-2">
                                     <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                     <line x1="2" x2="22" y1="10" y2="10"></line>
                                 </svg>
                             </a> --}}

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
 <div id="discountModalWrapper" style="display: none;"
     class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
     <div class="modal-scrollable w-full mx-auto">
         <div
             class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full overflow-y-auto">
             <div class="flex justify-between items-center px-6 pt-4">
                 <div class="flex items-center gap-2">
                     <h2 class="text-lg font-medium text-gray-900">Pay Invoice</h2>
                 </div>
                 <button id="closeDiscountModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
             </div>
             {{ html()->form('POST', route('front.customer.dashboard.invoice.paymentstore'))->id('recordpayment')->attributes([
                     'autocomplete' => 'off',
                     'data-parsley-validate' => true,
                     'class' => 'space-y-8',
                 ])->open() }}


             {!! html()->text('customer_id', $customer->id ?? '')->class('hidden')->attributes([
                     'id' => 'customer_id',
                     'autocomplete' => 'off',
                 ]) !!}

             {!! html()->hidden('amount')->attributes([
                     'id' => 'amount',
                     'autocomplete' => 'off',
                 ]) !!}

             {!! html()->hidden('invoice_id')->attributes([
                     'id' => 'invoice_id',
                     'autocomplete' => 'off',
                 ]) !!}



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

                 <!-- Card Options -->
                 <div id="creditCardOptions" class="mb-4 ">
                     <label class="block text-sm font-medium text-gray-700 mb-1 required">Card Options </label>
                     <select id="cardOption" name="card_option"
                         class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
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
                             <input type="text" placeholder="Card number" maxlength="19" id="cardNumber"
                                 name="cardNumber"
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
                         <label class="block text-sm font-medium text-gray-700 mb-1 required">Select Existing Card
                         </label>
                         <select name="existing_card_id"
                             class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                             <option value="">-- Select a saved card --</option>
                             @foreach ($customer->cards as $card)
                                 <option value="{{ $card->unique_id }}">{{ $card->card_number }}</option>
                             @endforeach
                         </select>
                     </div>
                 @endif

                 <!-- Addresh Info -->
                 <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                     <div class="flex items-center">

                         <div>
                             <p class="text-sm font-medium text-gray-900">Billing Address</p>
                             <p class="text-xs text-gray-700 mt-1">
                                 @if ($customer->billingAddress)
                                     @if ($customer->billingAddress->full_name)
                                         {{ $customer->billingAddress->full_name }} <br>
                                     @endif

                                     @if ($customer->billingAddress->address || $customer->billingAddress->city)
                                         {{ $customer->billingAddress->address ?? '' }}
                                         {{ $customer->billingAddress->city ? ', ' . $customer->billingAddress->city : '' }}<br>
                                     @endif

                                     @if (optional($customer->billingAddress->state)->name || $customer->billingAddress->zip_code)
                                         {{ optional($customer->billingAddress->state)->name ?? '' }}
                                         {{ $customer->billingAddress->zip_code ? ' ' . $customer->billingAddress->zip_code : '' }}<br>
                                     @endif

                                     @if ($customer->billingAddress->phone)
                                         {{ App\Helpers\CustomHelper::formatPhone($customer->billingAddress->phone) }}
                                     @endif
                                 @else
                                     <span class="text-gray-500 italic">No billing address on file.</span>
                                 @endif
                             </p>

                             <a id="updateAddressLink" x-on:click="activeTab = 'account'" href="javascript:void(0)"
                                 class="text-blue-600 text-xs hover:text-blue-800 flex items-center gap-1">
                                 Update / New Address
                             </a>
                         </div>

                     </div>
                 </div>

                 <!-- Secure Payment Info -->
                 <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                     <div class="flex items-center">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 text-blue-600 mr-3">
                             <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                             <line x1="2" x2="22" y1="10" y2="10"></line>
                         </svg>
                         <div>
                             <p class="text-sm font-medium text-blue-900">Secure Payment Processing</p>
                             <p class="text-xs text-blue-700 mt-1">Payments are processed securely through our payment
                                 gateway. You'll enter your payment details on the next step.</p>
                         </div>
                     </div>
                 </div>

             </div>

             <!-- Footer -->
             <div class="flex space-x-3 gap-3 px-6 py-4">
                 <button id="cancelDiscountBtn"
                     class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                     Cancel
                 </button>
                 <button type="submit" id="submitTemplatesBtn"
                     class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                     <span id="btnText"> Pay </span> <span data-field="Payamount"></span>

                     <svg id="btnSpinner" xmlns="http://www.w3.org/2000/svg"
                         class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                         <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                             stroke-width="4"></circle>
                         <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                     </svg>
                 </button>
             </div>

             {{ html()->form()->close() }}

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

             //  Always default to New Card when opening modal
             const cardOption = document.getElementById('cardOption');
             const newCardFields = document.getElementById('newCardFields');
             const cardOnFileDropdown = document.getElementById('cardOnFileDropdown');

             cardOption.value = 'NewCard';
             newCardFields.classList.remove('hidden');
             if (cardOnFileDropdown) cardOnFileDropdown.classList.add('hidden');

             // Loop through all open buttons
             openBtns.forEach(btn => {
                 btn.addEventListener('click', () => {
                     document.querySelector('#discountModalWrapper [data-field="invoice"]')
                         .textContent = btn.dataset.invoice;
                     document.querySelector('#discountModalWrapper [data-field="due"]').textContent =
                         btn.dataset.due;
                     document.querySelector('#discountModalWrapper [data-field="amount"]')
                         .textContent = btn.dataset.amount;
                     document.querySelector('#discountModalWrapper [data-field="Payamount"]')
                         .textContent = btn.dataset.amount;

                     document.querySelector('#amount').value = btn.dataset.amount;
                     document.querySelector('#invoice_id').value = btn.dataset.invoice;

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

     <!-- payment getway seeting  -->

     @if ($paymentSetting['payment_test_mode'] ?? false)
         <script src="https://jstest.authorize.net/v1/Accept.js"></script>
     @else
         <script src="https://js.authorize.net/v1/Accept.js"></script>
     @endif

     <script>
         document.addEventListener('DOMContentLoaded', function() {

             const form = document.getElementById('recordpayment');
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

             // ===== Show/hide card sections =====

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
                 this.value = this.value.replace(/\D/g, '').substring(0, 16).replace(/(.{4})/g, '$1 ')
             .trim();
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


                 // If using existing card, skip tokenization

                 if (cardOption.value === 'CardOnFile') {
                     console.log(" Using existing card, skipping tokenization");

                     //  Now check Parsley validation before final submit
                     if ($(form).parsley().isValid()) {
                         console.log(" Form validation passed — submitting now");
                         form.submit();
                     } else {
                         console.warn(" Form validation failed for existing card");
                         notyf.error("Please fix the form errors before submitting.");
                     }
                     return; // Stop further processing
                 }


                 const cardOpt = cardOption.value;

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
                             let errorMsg = response.messages.message?.[0]?.text ||
                                 "Tokenization failed.";

                             console.error(" Tokenization error:", errorMsg);

                             notyf.error(errorMsg);

                             // Reset UI
                             submitBtn.disabled = false;
                             btnText.textContent = 'pay';
                             btnSpinner.classList.add('hidden');
                             return; // Stop submit
                         }

                         console.log(" Tokenization success — Opaque Data:", response.opaqueData);

                         notyf.success("Payment details validated successfully!");

                         //  Now check Parsley validation before final submit
                         if ($(form).parsley().isValid()) {
                             console.log(" Form validation passed — submitting now");

                             document.getElementById('opaqueDataValue').value = response.opaqueData
                                 .dataValue;
                             document.getElementById('opaqueDataDescriptor').value = response
                                 .opaqueData.dataDescriptor;

                             form.submit();

                         } else {
                             console.warn(" Form validation failed after tokenization");
                             notyf.error("Please fix the form errors before submitting.");

                             submitBtn.disabled = false;
                             btnText.textContent = 'pay';
                             btnSpinner.classList.add('hidden');
                         }

                     });

                 } catch (error) {
                     console.error(" Tokenization JS error:", error);
                     notyf.error("Something went wrong during payment processing.");

                     // Reset UI
                     submitBtn.disabled = false;
                     btnText.textContent = 'pay';
                     btnSpinner.classList.add('hidden');

                     return; // Stop submit
                 }

             });

         });
     </script>

     <!-- payment getway seeting  -->

 @endpush

 @push('js')
     <script>
         document.addEventListener('DOMContentLoaded', () => {
             const updateLink = document.getElementById('updateAddressLink');
             updateLink?.addEventListener('click', () => {

                 setTimeout(() => document.getElementById('editBtn')?.click(), 300);

             });
         });
     </script>
 @endpush
