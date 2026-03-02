
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


                   {{ html()->form('POST', route('admin.crm.customers.invoice.paymentstore'))->id('recordpayment')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->open() }}



                   {!! html()->text('customer_id', $customer->id ?? '')->class('hidden')->attributes([
                   'id' => 'customer_id',
                   'autocomplete' => 'off'
                   ]) !!}

                  {!! html()->text('invoice_id', $invoice->id ?? '')->class('hidden')->attributes([
                   'id' => 'invoice_id',
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
                            collect(\App\Enums\Customers\PaymentMethod::options())
                                ->except(\App\Enums\Customers\PaymentMethod::Other->value)
                                ->toArray()
                        )
                        ->id('payment_type')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                        ->required()
                        !!}

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

   @push('js')

   
<!-- JS Section -->
<script>
    document.addEventListener('DOMContentLoaded', () => {

        
        //  reset and re-sync form fields inside a modal
        function resetForm(modalWrapper) {
            const form = modalWrapper.querySelector('form');

            const amountInput2 = document.querySelector('input[name="amount"]');

            const paymentTypeSelect2 = document.querySelector('select[name="payment_type"]');
            const cardOptionSelect2 = document.querySelector('#cardOption');
            const cardOnFileDropdown2 = document.querySelector('#cardOnFileDropdown');
            const existingCardSelect2 = document.querySelector('select[name="existing_card_id"]');

            const chequeNumberInput = document.querySelector('#chequeNumberField');

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
            const creditCardOptions = modalWrapper.querySelector('#creditCardOptions');
            const newCardFields = modalWrapper.querySelector('#newCardFields');
            const cardOnFileDropdown = modalWrapper.querySelector('#cardOnFileDropdown');
            [creditCardOptions, newCardFields, cardOnFileDropdown].forEach(el => {
                if (el) el.classList.add('hidden');
            });

            // Reset payment type and card option
            const paymentType = modalWrapper.querySelector('#payment_type');
            if (paymentType) paymentType.value = '';
            const cardOption = modalWrapper.querySelector('#cardOption');
            if (cardOption) cardOption.value = 'NewCard';
        }

       function fillPaymentWithOpenAmount(modalWrapper) {

            const openElem = document.querySelector('#invoice-open');
            const amountInput = modalWrapper.querySelector('input[name="amount"]');

            if (!openElem || !amountInput) return;

            let openAmount = openElem.textContent.replace(/[^0-9.-]+/g, "");
            openAmount = parseFloat(openAmount) || 0;

            if (openAmount > 0) {

                const formatted = openAmount.toFixed(2);

                //  Set visible value
                amountInput.value = formatted;

                //  Sync your custom digit handler
                if (amountInput.hasOwnProperty('digits')) {
                    amountInput.digits = formatted.replace('.', '');
                }

                //  Trigger input event
                amountInput.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            }
        }

          function setupModal(openBtnId, modalWrapperId, closeBtnId, cancelBtnId) {
            const modalWrapper = document.getElementById(modalWrapperId);
            const openBtn = document.getElementById(openBtnId);
            const closeBtn = document.getElementById(closeBtnId);
            const cancelBtn = document.getElementById(cancelBtnId);
            if (!modalWrapper || !openBtn || !closeBtn || !cancelBtn) return;

            // Open
            openBtn.addEventListener('click', () => {
                resetForm(modalWrapper); // Reset everything on open
                modalWrapper.style.display = 'flex';


    // Auto-fill with open amount
    fillPaymentWithOpenAmount(modalWrapper);
            });

            // Close
            // const closeModal = () => modalWrapper.style.display = 'none';

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

           setupModal('openTemplatesModal', 'templatesModalWrapper', 'closeModalBtn', 'canceltempBtn');


        
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