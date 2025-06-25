@extends('front.layouts.app')

@section('title', $title)

@section('content')

    <!-- Page Title Section -->
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-[#f9fafc]">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div id="breadcrumbs" class="pt-[100px] pb-[20px] ">
                <div class="w-full">
                    <div class="flex justify-between items-center ">
                        <h1 class="text-[28px] md:text-[34px] lg:text-[40px] tracking-[-2px] leading-[110%] font-bold">
                            {{ $title }}</h1>
                        <ul
                            class="border-yellow-400 px-[20px] py-2 lg:py-3 max-w-full text-[14px] font-medium items-center inline-flex gap-3 relative border-2 border-[#fff]">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                <a href="{{ route('front.home.index') }}" class="opacity-75">Home</a>
                            </li>
                            <li>
                                <a href="javascript:void(0)">{{ $title }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="lg:pb-[50px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="flex gap-x-2">
                <div class="w-full md:w-2/3 border-r px-4 md:pr-8 pb-12">
                    <!-- Billing Info -->
                    <h2 class="text-2xl font-bold mb-1">Billing information</h2>
                    {{ html()->form()->attributes([
                        'autocomplete' => 'off',
                        'data-parsley-validate' => true,
                        'class' => 'space-y-8',
                    ])->open() }}
                        @csrf
                        <!-- Address Selection -->
                        @auth
                            <div>
                                <label for="selAddress" class="block text-sm font-medium text-gray-800 mb-1">
                                    Select available addresses:
                                </label>
                                <select id="selAddress" name="selAddress"
                                    class="block w-full border border-gray-300 rounded-md py-2 px-3 text-gray-700 bg-white">
                                    <option>7080 MCADOO BRANCH ROAD, LYLES, Tennessee, 37098</option>
                                </select>
                                <div
                                    class="border-2 border-dashed border-green-600 rounded-md p-4 mt-4 relative bg-green-50/10">
                                    <h5 class="text-base font-bold">BEN LAMPLEY</h5>
                                    <p>7080 MCADOO BRANCH ROAD, LYLES, Tennessee, 37098</p>
                                    <p class="text-sm">Phone: (931) 996-9192</p>
                                    <p class="mt-2 text-sm">Email: BENLAMPLEY30@GMAIL.COM</p>
                                    <span class="text-green-600 font-medium text-xs absolute top-2 right-3">Default</span>
                                </div>
                            </div>
                        @else
                            <div class="mb-6 text-sm text-gray-600">
                                Already have an account?
                                <a href="#" class="text-blue-600 hover:underline ml-1">Login</a>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="text" id="billingFirstName" placeholder="First Name"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                                <input type="text" id="billingLastName" placeholder="Last Name"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                            </div>
                            <input type="text" id="billingCompany" placeholder="Company Name"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="email" id="billingEmail" placeholder="Email"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                                <input type="text" id="billingPhone" placeholder="Phone"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                            </div>
                            <textarea id="billingAddress" name="billingAddress" class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full"
                                rows="2" placeholder="Address"></textarea>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <select id="billingState" class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full ">
                                    <option disabled value="">Select State...</option>
                                    @foreach ($states as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" id="billingCity" placeholder="City"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                                <input type="text" id="billingZip"
                                    placeholder="Zip code"class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full " />
                            </div>

                            <!-- Password section -->
                            <div class="flex items-center gap-2 mt-2">
                                <input id="showPassword" type="checkbox" class="accent-blue-600 h-4 w-4" checked />
                                <label for="showPassword" class="text-sm">Enter Your Custom Password</label>
                            </div>
                            <div id="passwordFields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="password" placeholder="Password"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                                <input type="password" placeholder="Password confirmation"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                            </div>

                            <div class="text-xs text-gray-600 leading-relaxed mt-2">
                                By providing your phone number and/or email, you agree to receive order information from
                                us via text and/or email as well as other information pertaining to renting or buying
                                equipment.
                                <a href="#" class="text-blue-600 hover:underline ml-1">Learn More</a>
                            </div>
                        @endauth


                        <!-- Delivery Info -->
                        <h4 class="text-lg font-semibold">Delivery information</h4>
                        <div class="flex items-center gap-2 mb-4">
                            <input id="sameAsBilling" type="checkbox" class="accent-blue-500 h-4 w-4" value="Yes"  />
                            <label for="sameAsBilling" class="text-sm">Same as billing information</label>
                        </div>
                        <div id="deliveryDiv" class="space-y-4">
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex-1">
                                    <label for="deliveryFirstName" class="block text-sm text-gray-600 mb-1">First
                                        Name</label>
                                    <input type="text" id="deliveryFirstName" name="deliveryFirstName"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm"
                                        placeholder="First Name" />
                                </div>
                                <div class="flex-1">
                                    <label for="deliveryLastName" class="block text-sm text-gray-600 mb-1">Last
                                        Name</label>
                                    <input type="text" id="deliveryLastName" name="deliveryLastName"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm"
                                        placeholder="Last Name" />
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex-[2]">
                                    <label for="deliveryEmail" class="block text-sm text-gray-600 mb-1">Email</label>
                                    <input type="email" id="deliveryEmail" name="deliveryEmail"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm"
                                        placeholder="Email" />
                                </div>
                                <div class="flex-1">
                                    <label for="deliveryPhone" class="block text-sm text-gray-600 mb-1">Phone</label>
                                    <input type="text" id="deliveryPhone" name="deliveryPhone"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm"
                                        placeholder="Phone" />
                                </div>
                            </div>
                            <div>
                                <label for="deliveryAddress" class="block text-sm text-gray-600 mb-1">Address</label>
                                <textarea id="deliveryAddress" name="deliveryAddress"
                                    class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm" rows="2" placeholder="Address"></textarea>
                            </div>
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex-1">
                                    <label for="deliveryState" class="block text-sm text-gray-600 mb-1">State</label>
                                    <select id="deliveryState" name="deliveryState"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm">
                                        <option disabled value="">Select State...</option>
                                        @foreach ($states as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label for="deliveryCity" class="block text-sm text-gray-600 mb-1">City</label>
                                    <input type="text" id="deliveryCity" name="deliveryCity"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm"
                                        placeholder="City" />
                                </div>
                                <div class="flex-1">
                                    <label for="deliveryZip" class="block text-sm text-gray-600 mb-1">Zip code</label>
                                    <input type="text" id="deliveryZip" name="deliveryZip"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm"
                                        placeholder="Zip Code" />
                                </div>
                            </div>
                        </div>

                        <!-- Order Notes -->
                        <div>
                            <h4 class="text-lg font-semibold mb-1">Order notes</h4>
                            <textarea name="orderNotes" id="orderNotes" class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm"
                                placeholder="Notes about your order, e.g. special notes for delivery." rows="2"></textarea>
                        </div>

                        <!-- Tax Exempt -->
                        <div class="flex items-center gap-2">
                            <input id="taxExempt" type="checkbox" class="accent-blue-500 h-4 w-4" />
                            <label for="taxExempt" class="text-sm">Tax Exempt</label>
                        </div>

                        <!-- Payment Method -->
                        <div class="mx-auto" id="paymentForm">
                            <h4 class="text-lg font-semibold mb-3">Payment method</h4>
                            <div class="space-y-4">

                                <!-- Credit/Debit -->
                                <div class="border-2 rounded-lg p-4 payment-option" data-value="Card">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="payment" value="Card"  />
                                        <span>Pay Using Credit or Debit</span>
                                    </label>

                                    <div id="cardSection" class="block">
                                        <!-- Card Visual -->
                                        <div class="relative h-52 w-full max-w-md  mt-4 card-container">
                                            <!-- Card Front -->
                                            <div id="cardFront"
                                                class="absolute w-full h-full rounded-xl p-5 bg-gradient-to-r from-gray-300 to-gray-300 text-white shadow-lg transition-all duration-300 card-face card-front">
                                                <div
                                                    class="w-10 h-7 relative bg-gray-400 rounded mt-5 before:w-[70%] before:content-[''] before:h-[60%] before:bg-gray-300 before:top-[20%] before:rounded-r before:absolute">
                                                </div>
                                                <div id="displayCardNumber"
                                                    class="mt-8 text-2xl tracking-widest font-mono">•••• •••• •••• ••••
                                                </div>
                                                <div class="flex justify-between mt-8 text-base">
                                                    <span id="displayFullName" class="uppercase font-mono">FULL
                                                        NAME</span>
                                                    <span id="displayExpiry" class="font-mono">MM/YY</span>
                                                </div>
                                            </div>
                                            <!-- Card Back -->
                                            <div id="cardBack"
                                                class="absolute w-full h-full rounded-xl p-5 bg-gradient-to-r from-gray-300 to-gray-300 text-white shadow-lg transition-all duration-300 card-face card-back"
                                                style="display:none">
                                                <div class="bg-gray-400 h-10 my-6 rounded"></div>
                                                <div class="flex justify-end mt-4">
                                                    <div id="displayCVC"
                                                        class="bg-gray-200 text-black px-4 py-2 rounded font-mono text-lg">
                                                        CVC</div>
                                                </div>
                                                <div class="mt-8 text-xs text-center text-gray-500">
                                                    Keep your CVC code confidential
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Card Inputs -->
                                        <div class="grid md:grid-cols-2 gap-4 mt-4">
                                            <input type="text" placeholder="First name" id="firstName"
                                                class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500" />
                                            <input type="text" placeholder="Last name" id="lastName"
                                                class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500" />
                                            <input type="text" placeholder="Card number" maxlength="19"
                                                id="cardNumber"
                                                class="border border-gray-300 rounded-md py-2 px-3 text-sm md:col-span-2 focus:border-blue-500" />
                                            <input type="text" placeholder="MM/YY" maxlength="5" id="expiry"
                                                class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500" />
                                            <input type="text" placeholder="CVC" maxlength="4" id="cvc"
                                                class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500" />
                                        </div>
                                    </div>
                                </div>
                                <!-- Cash On Delivery -->
                                <div class="border-2 rounded-lg p-4 payment-option" data-value="COD">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="payment" value="COD" checked />
                                        <span>Cash on delivery (COD)</span>
                                    </label>
                                </div>
                                <!-- Add Account -->
                                <div class="border-2 rounded-lg p-4 payment-option" data-value="Account">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="payment" value="Account" />
                                        <span>Add Account</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-between items-center mt-8">
                            <a href="javascript:history.back()"
                                class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                                <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Back
                            </a>
                            <button type="submit"
                                class="bg-yellow-400 hover:bg-yellow-300 text-black text-base font-medium rounded px-8 py-3 transition">
                                Checkout
                            </button>
                        </div>
                    {{ html()->form()->close() }}
                </div>


                <div class="w-1/3 mt-10 pl-6">
                    <div class="border-b pb-4 mb-6">
                        <div class="flex">
                            <div class="w-1/3">
                                <div class="border rounded relative ">
                                    <img src="{{ asset('storage/front/images/product-detail/skid-steer-daily.png') }}"
                                        alt="image" class="w-24 p-1">
                                    <span
                                        class="bg-gray-400 w-[22px] h-[22px] text-white rounded-full absolute -top-2 -right-[8px] text-[14px] text-center font-bold">1</span>
                                </div>
                            </div>
                            <div class=" ml-4 w-2/3">
                                <h4 class="text-[14px] flex">
                                    Skid Steer Open Cab - Daily
                                    <span class="font-bold">$334.00</span>
                                </h4>
                                <h5 class="text-[13px]">Options Total:</h5>
                                <ul class="flex flex-col">
                                    <li class="flex justify-between leading-[16px]">
                                        <span class="text-[12px] before:content-['-'] before:pr-1">Prepaid Fuel</span>
                                        <span class="text-[12px] font-bold">+ $118.00</span>
                                    </li>
                                    <li class="flex justify-between leading-[16px]">
                                        <span class="text-[12px] before:content-['-'] before:pr-1">Toothed Bucket</span>
                                    </li>
                                    <li class="flex justify-between leading-[16px]">
                                        <span class="text-[12px] before:content-['-'] before:pr-1">Damage Waiver</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <h4 class="mt-3">Schedule Date: 05/14/2025 - 05/15/2025</h4>
                    </div>
                    <div class="border-b pb-6">
                        <ul class="flex flex-col">
                            <li class="flex justify-between mb-1">
                                <span class="text-[14px]">Subtotal:</span>
                                <span class="text-[14px] font-bold">+ $452.00</span>
                            </li>
                            <li class="flex justify-between mb-1">
                                <span class="text-[14px]">Tax</span>
                                <span class="text-[14px] font-bold">+ $0.00</span>
                            </li>
                            <li class="flex justify-between mb-1">
                                <span class="text-[14px] font-bold">Total</span>
                                <span class="text-[16px] font-bold">+ $452.00</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tax Exempt Modal -->
    <div id="taxModal"
        class="fixed inset-0 z-[9999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="taxModal-box bg-white rounded-lg shadow-lg w-full md:max-w-lg relative max-w-[90%] opacity-100">
            <div class="flex justify-between bg-black p-4 py-2 items-center rounded-t-lg">
                <h5 class="text-lg text-white">Enter Admin Code</h5>
                <button onclick="cancelModal()" class="px-4 py-2 text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-6 py-4">
                <span class="font-medium">Note: </span>
                <p class="text-gray-700 mb-4 inline text-sm italic">Tax Exempt sales must be pre-approved with
                    documentation on file prior to placing the order. Call if you need assistance with placing an order with
                    Tax Exempt status.</p>
                <form action="#">
                    <input type="password" name="name" id="name"
                        class="form-input mt-1 block w-full px-4 py-2 rounded-md border border-gray-300 shadow-sm "
                        placeholder=" Admin Code" />
                </form>
            </div>

            <div class="border-t p-6 py-4">
                <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-end gap-3">
                    <button onclick="cancelModal()"
                        class="bg-gray-600 hover:bg-gray-700 text-white text-base font-medium rounded px-8 py-3 ">Cancel</button>
                    <button onclick="confirmModal()"
                        class="bg-yellow-400 hover:bg-yellow-300 text-black text-base font-medium rounded px-8 py-3 ">Submit</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ================================
            // Payment Method Highlight & Toggle
            // ================================

            const paymentOptions = document.querySelectorAll('.payment-option');
            const radioButtons = document.querySelectorAll('input[name="payment"]');
            const cardSection = document.getElementById('cardSection');

            function updateHighlight() {
                paymentOptions.forEach(opt => {
                    opt.classList.remove('border-blue-500');
                    opt.classList.add('border-gray-200');
                });
                const checkedRadio = document.querySelector('input[name="payment"]:checked');
                const selected = checkedRadio.value;
                document.querySelector(`.payment-option[data-value="${selected}"]`).classList.add(
                    'border-blue-500');
                // Show/hide card input section
                cardSection.style.display = selected === 'Card' ? 'block' : 'none';
            }
            radioButtons.forEach(r => r.addEventListener('change', updateHighlight));
            updateHighlight();

            // Card info live update
            const cardNumberInput = document.getElementById('cardNumber');
            const firstNameInput = document.getElementById('firstName');
            const lastNameInput = document.getElementById('lastName');
            const expiryInput = document.getElementById('expiry');
            const cvcInput = document.getElementById('cvc');

            const displayCardNumber = document.getElementById('displayCardNumber');
            const displayFullName = document.getElementById('displayFullName');
            const displayExpiry = document.getElementById('displayExpiry');
            const displayCVC = document.getElementById('displayCVC');

            function formatCardNumber(value) {
                return value.replace(/\D/g, '').substring(0, 16).replace(/(.{4})/g, '$1 ').trim();
            }

            function formatExpiry(value) {
                value = value.replace(/[^0-9/]/g, '').substring(0, 5);
                if (value.length === 2 && !value.includes('/')) value = value + '/';
                return value;
            }

            cardNumberInput.addEventListener('input', function() {
                this.value = formatCardNumber(this.value);
                displayCardNumber.textContent = this.value || '•••• •••• •••• ••••';
            });
            firstNameInput.addEventListener('input', function() {
                updateName();
            });
            lastNameInput.addEventListener('input', function() {
                updateName();
            });

            function updateName() {
                const name = (firstNameInput.value + ' ' + lastNameInput.value).trim();
                displayFullName.textContent = name || 'FULL NAME';
            }
            expiryInput.addEventListener('input', function() {
                this.value = formatExpiry(this.value);
                displayExpiry.textContent = this.value || 'MM/YY';
            });
            cvcInput.addEventListener('input', function() {
                displayCVC.textContent = this.value || 'CVC';
            });

            // Card flip logic for CVC
            const cardFront = document.getElementById('cardFront');
            const cardBack = document.getElementById('cardBack');

            cvcInput.addEventListener('focus', function() {
                cardFront.style.display = 'none';
                cardBack.style.display = 'block';
            });
            cvcInput.addEventListener('blur', function() {
                cardFront.style.display = 'block';
                cardBack.style.display = 'none';
            });

            // ========================================
            // Sync "Same as Billing" for Delivery Info
            // ========================================
            const sameAsBilling = document.getElementById('sameAsBilling');
            const deliveryDiv = document.getElementById('deliveryDiv');

            // Map billing fields to delivery fields
            const fields = [
                ['billingFirstName', 'deliveryFirstName'],
                ['billingLastName', 'deliveryLastName'],
                ['billingEmail', 'deliveryEmail'],
                ['billingPhone', 'deliveryPhone'],
                ['billingAddress', 'deliveryAddress'],
                ['billingState', 'deliveryState'],
                ['billingCity', 'deliveryCity'],
                ['billingZip', 'deliveryZip'],
            ];

            function syncDeliveryFields() {
                fields.forEach(([billingId, deliveryId]) => {
                    const billing = document.getElementById(billingId);
                    const delivery = document.getElementById(deliveryId);
                    if (billing && delivery) {
                        if (billing.tagName === 'SELECT') {
                            delivery.value = billing.value;
                        } else if (billing.tagName === 'TEXTAREA') {
                            delivery.value = billing.value;
                        } else {
                            delivery.value = billing.value;
                        }
                    }
                });
            }

            // ============================
            // Custom Password Show/Hide
            // ============================
            sameAsBilling.addEventListener('change', function() {
                if (this.checked) {
                    syncDeliveryFields();
                    deliveryDiv.style.display = 'none';
                    // Listen for changes in billing to update delivery fields
                    fields.forEach(([billingId, deliveryId]) => {
                        const billing = document.getElementById(billingId);
                        if (billing) {
                            billing.addEventListener('input', syncDeliveryFields);
                            billing.addEventListener('change', syncDeliveryFields);
                        }
                    });
                } else {
                    deliveryDiv.style.display = '';
                    // Remove listeners if needed (not strictly necessary here for most forms)
                }
            });

            const showPassword = document.getElementById('showPassword');
            const passwordFields = document.getElementById('passwordFields');

            function togglePasswordFields() {
                passwordFields.style.display = showPassword.checked ? '' : 'none';
            }

            showPassword.addEventListener('change', togglePasswordFields);
            // Set initial state
            togglePasswordFields();

            // ============================
            // Tax Exempt Modal Show/Hide
            // ============================
            const taxExempt = document.getElementById('taxExempt');
            const taxModal = document.getElementById('taxModal');

            function handleTaxExemptChange() {
                if (taxExempt.checked) {
                    taxModal.classList.remove('hidden');
                } else {
                    taxModal.classList.add('hidden');
                }
            }

            // Show/hide modal when checkbox changes
            taxExempt.addEventListener('change', handleTaxExemptChange);

            // Optional: When modal is closed, uncheck the box
            window.cancelModal = function() {
                taxModal.classList.add('hidden');
                taxExempt.checked = false;
            };

            // Optional: When submitted, also hide modal (customize as needed)
            window.confirmModal = function() {
                taxModal.classList.add('hidden');
                // Optionally: keep checkbox checked
                // Optionally: Add your validation or AJAX here
            };
        });
    </script>
@endpush
