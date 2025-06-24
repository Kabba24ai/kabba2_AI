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
                    <form action="#" class="space-y-6">

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
                                <input type="text" placeholder="First Name"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                                <input type="text" placeholder="Last Name"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                            </div>
                            <input type="text" placeholder="Company Name"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="email" placeholder="Email"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                                <input type="text" placeholder="Phone"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                            </div>
                            <input type="text" placeholder="Address"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <select class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full text-gray-500">
                                    <option>Select State...</option>
                                    <!-- Add options here -->
                                </select>
                                <input type="text" placeholder="City"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full" />
                                <input type="text" placeholder="Zip code"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full bg-blue-50" />
                            </div>

                            <!-- Password section -->
                            <div class="flex items-center gap-2 mt-2">
                                <input id="showPassword" type="checkbox" class="accent-blue-600 h-4 w-4" checked />
                                <label for="showPassword" class="text-sm">Enter Your Custom Password</label>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                            <input id="sameAsBilling" type="checkbox" class="accent-blue-500 h-4 w-4" />
                            <label for="sameAsBilling" class="text-sm">Same as billing information</label>
                        </div>
                        <div id="deliveryDiv" class="space-y-4">
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex-1">
                                    <label for="deliveryFirstName" class="block text-sm text-gray-600 mb-1">First
                                        Name</label>
                                    <input type="text" id="deliveryFirstName" name="deliveryFirstName"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm" />
                                </div>
                                <div class="flex-1">
                                    <label for="deliveryLastName" class="block text-sm text-gray-600 mb-1">Last
                                        Name</label>
                                    <input type="text" id="deliveryLastName" name="deliveryLastName"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm" />
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex-[2]">
                                    <label for="deliveryEmail" class="block text-sm text-gray-600 mb-1">Email</label>
                                    <input type="email" id="deliveryEmail" name="deliveryEmail"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm" />
                                </div>
                                <div class="flex-1">
                                    <label for="deliveryPhone" class="block text-sm text-gray-600 mb-1">Phone</label>
                                    <input type="text" id="deliveryPhone" name="deliveryPhone"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm" />
                                </div>
                            </div>
                            <div>
                                <label for="deliveryAddress" class="block text-sm text-gray-600 mb-1">Address</label>
                                <textarea id="deliveryAddress" name="deliveryAddress"
                                    class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm" rows="2"></textarea>
                            </div>
                            <div class="flex flex-col md:flex-row gap-4">
                                <div class="flex-1">
                                    <label for="deliveryOption" class="block text-sm text-gray-600 mb-1">Delivery
                                        Option</label>
                                    <select id="deliveryOption" name="deliveryOption"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm">
                                        <option disabled selected hidden>Select Delivery Option</option>
                                        <option>10296 High 46, Bon Aqua, TN, 37025</option>
                                        <option>4385 SR-48, Charlotte, TN, 37036</option>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label for="deliveryCity" class="block text-sm text-gray-600 mb-1">City</label>
                                    <input type="text" id="deliveryCity" name="deliveryCity"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm" />
                                </div>
                                <div class="flex-1">
                                    <label for="deliveryZip" class="block text-sm text-gray-600 mb-1">Zip code</label>
                                    <input type="text" id="deliveryZip" name="deliveryZip"
                                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm" />
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
                        <div>
                            <h4 class="text-lg font-semibold mb-3">Payment method</h4>
                            <div class="space-y-4">

                                <!-- Credit/Debit -->
                                <div class="border rounded-lg p-4">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="payment" value="credit" checked
                                            class="accent-blue-600" />
                                        <span>Pay Using Credit or Debit</span>
                                    </label>

                                    <div
                                        class="relative h-48 rounded-xl transition-transform duration-700 transform-style preserve-3d shadow-lg">
                                        <!-- Front of Card -->
                                        <div
                                            class="absolute w-full h-full backface-hidden rounded-xl p-5 bg-[#ddd] text-white card-front">
                                            <div
                                                class="w-10 h-7 relative bg-[#ccc] rounded mt-5 before:w-[70%] before:content-[''] before:h-[60%] before:bg-[#d9d9d9] before:top-[20%] before:rounded-r before:absolute">
                                            </div>
                                            <div id="cardNumberDisplay"
                                                class="mt-5 text-lg tracking-widest font-mono">•••• •••• •••• ••••
                                            </div>
                                            <div class="flex justify-between mt-5 text-xs">
                                                <div>
                                                    <span id="cardHolderDisplay"
                                                        class="block mt-1 text-lg uppercase font-mono">FULL NAME</span>
                                                </div>
                                                <div>
                                                    <span id="cardExpiryDisplay"
                                                        class="block mt-1 text-lg font-mono">MM/YY</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Back of Card -->
                                        <div
                                            class="absolute w-full h-full backface-hidden rotate-y-180 rounded-xl p-5 bg-gradient-to-r from-indigo-600 to-purple-500 text-white card-back">
                                            <div class="bg-black h-10 my-5"></div>
                                            <div class="flex justify-end mt-2">
                                                <div id="cvcDisplay"
                                                    class="bg-white text-gray-800 px-3 py-1 rounded text-sm font-mono">
                                                    CVC</div>
                                            </div>
                                            <div class="mt-5 text-xs text-center">
                                                Keep your CVC code confidential
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Card Details -->
                                    <div class="grid md:grid-cols-2 gap-4 mt-4">
                                        <input type="text" placeholder="First name"
                                            class="border border-gray-300 rounded-md py-2 px-3 text-sm" />
                                        <input type="text" placeholder="Last name"
                                            class="border border-gray-300 rounded-md py-2 px-3 text-sm" />
                                        <input type="text" placeholder="Card number" maxlength="19"
                                            class="border border-gray-300 rounded-md py-2 px-3 text-sm md:col-span-2" />
                                        <input type="text" placeholder="MM/YY" maxlength="5"
                                            class="border border-gray-300 rounded-md py-2 px-3 text-sm" />
                                        <input type="text" placeholder="CVC" maxlength="3"
                                            class="border border-gray-300 rounded-md py-2 px-3 text-sm" />
                                    </div>
                                </div>
                                <!-- Cash On Delivery -->
                                <div class="border rounded-lg p-4">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="payment" value="cod" class="accent-blue-600" />
                                        <span>Cash on delivery (COD)</span>
                                    </label>
                                </div>
                                <!-- Add Account -->
                                <div class="border rounded-lg p-4">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="payment" value="account" class="accent-blue-600" />
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
                    </form>
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
    <div id="taxModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden ">
        <div class="taxModal-box bg-white rounded-lg shadow-lg w-full md:max-w-lg relative max-w-[90%]">
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
                        class="mt-2 block bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0 "
                        placeholder=" Admin Code" />
                </form>
            </div>

            <div class="border-t p-6 py-4">
                <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-end gap-3">
                    <button onclick="cancelModal()"
                        class="border-0 bg-yellow-400 text-[14px] px-6 font-bold hover:bg-yellow-300  transition-all duration-500 ease-in-out order-2 md:order-1">Cancel</button>
                    <button onclick="confirmModal()"
                        class="border-0 bg-yellow-400 text-[14px] px-6 py-3 font-bold hover:bg-yellow-300  transition-all duration-500 ease-in-out order-1 md:order-2">Submit</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.getElementById('card');
            const cardNumberInput = document.getElementById('cardNumber');
            const cardNumberDisplay = document.getElementById('cardNumberDisplay');
            const firstNameInput = document.getElementById('firstName');
            const lastNameInput = document.getElementById('lastName');
            const cardHolderDisplay = document.getElementById('cardHolderDisplay');
            const expiryDateInput = document.getElementById('expiryDate');
            const expiryDateDisplay = document.getElementById('cardExpiryDisplay');
            const cvcInput = document.getElementById('cvc');
            const cvcDisplay = document.getElementById('cvcDisplay');

            cardNumberInput.addEventListener('input', function() {
                let value = this.value.replace(/\s/g, '').replace(/\D/g, '');
                let formattedValue = '';

                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }

                this.value = formattedValue;

                if (value.length > 0) {
                    const lastFourDigits = value.slice(-4);
                    const maskedPart = '•••• •••• •••• ';
                    cardNumberDisplay.textContent = maskedPart + lastFourDigits.padStart(4, '•');
                } else {
                    cardNumberDisplay.textContent = '•••• •••• •••• ••••';
                }
            });

            function updateCardHolder() {
                const firstName = firstNameInput.value.trim();
                const lastName = lastNameInput.value.trim();
                const fullName = [firstName, lastName].filter(Boolean).join(' ').toUpperCase();
                cardHolderDisplay.textContent = fullName || 'FULL NAME';
            }

            firstNameInput.addEventListener('input', updateCardHolder);
            lastNameInput.addEventListener('input', updateCardHolder);

            expiryDateInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');

                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }

                this.value = value;
                expiryDateDisplay.textContent = value || 'MM/YY';
            });

            cvcInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
                cvcDisplay.textContent = this.value || 'CVC';
            });

            cvcInput.addEventListener('focus', function() {
                card.classList.add('flipped');
            });

            cvcInput.addEventListener('blur', function() {
                if (!cvcInput.matches(':hover')) {
                    card.classList.remove('flipped');
                }
            });

            cvcInput.addEventListener('mouseenter', function() {
                card.classList.add('flipped');
            });

            cvcInput.addEventListener('mouseleave', function() {
                if (document.activeElement !== cvcInput) {
                    card.classList.remove('flipped');
                }
            });

            [cardNumberInput, firstNameInput, lastNameInput, expiryDateInput].forEach(input => {
                input.addEventListener('focus', function() {
                    card.classList.remove('flipped');
                });
            });
        });
    </script>
    <script>
        let taxCheckbox = document.getElementById('taxCheckbox');
        let shouldCheck = false;

        function handleCheckboxChange(event) {
            if (event.target.checked) {
                event.target.checked = false;
                shouldCheck = true;
                showModal();
            } else {
                shouldCheck = false;
            }
        }

        function showModal() {
            document.getElementById('taxModal').classList.remove('hidden');
            document.getElementById('taxModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('taxModal').classList.add('hidden');
            document.getElementById('taxModal').classList.remove('flex');
        }

        function confirmModal() {
            if (shouldCheck) {
                taxCheckbox.checked = true;
            }
            closeModal();
        }

        function cancelModal() {
            taxCheckbox.checked = false; // ensure it's still unchecked
            closeModal();
        }
        // Proper outside click detection that doesn't interfere with modal toggle
        document.addEventListener('mousedown', function(event) {
            const modal = document.getElementById('taxModal');
            const modalBox = document.querySelector('.taxModal-box');

            // Check if modal is visible
            const isModalVisible = !modal.classList.contains('hidden');

            // If modal is open and click is outside modal box
            if (isModalVisible && !modalBox.contains(event.target)) {
                cancelModal();
            }
        });
    </script>
@endpush
