@extends('front.layouts.app')

@section('title', $title)

@section('content')

<!-- Page Title Section -->
<section class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-[#f9fafc]">
    <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
        <div id="breadcrumbs" class="pt-[100px] pb-[20px] ">
            <div class="w-full">
                <div class="flex justify-between items-center ">
                    <h1 class="text-[28px] md:text-[34px] lg:text-[40px] tracking-[-2px] leading-[110%] font-bold">Delivery</h1>
                    <ul class="bg-yellow-400 px-[20px] py-2 lg:py-3 max-w-full text-[14px] font-medium items-center inline-flex gap-3 relative border-2 border-[#fff]">
                        <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                            <a href="index.php" class="opacity-25">Home</a>
                        </li>
                        <li>
                            <a href="delivery.php">Delivery</a>
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
            <div class="w-2/3 border-r-[1px] pr-6">
                <h4 class="text-[18px] md:text-[30px] font-medium  mb-3 mt-10 ">Billing information</h4>
                <form action="#" class="mt-3">
                    <div class="mb-6">
                        <div>
                            <label for="selAddress" class="text-black text-sm ">
                                Select available addresses:
                            </label>
                            <select id="selAddress" name="selAddress" class="block w-full mt-2 bg-white border border-gray-300 py-2.5 px-2 text-sm text-gray-900 appearance-none">
                                <option>7080 MCADOO BRANCH ROAD, LYLES, Tennessee, 37098</option>
                            </select>
                        </div>
                        <div class="border-2 border-dashed border-[#009900] relative p-4 text-[14px] mt-3">
                            <h4 class="text-lg font-bold">BEN LAMPLEY</h4>
                            <p>7080 MCADOO BRANCH ROAD, LYLES, Tennessee, 37098</p>
                            <p>Phone: (931) 996-9192</p>
                            <p class="mt-4">Email: BENLAMPLEY30@GMAIL.COM</p>
                            <p class="text-[#009900] absolute top-2 right-3">Default</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <p class="text-gray-700 text-sm text-base leading-relaxed">
                            By providing your phone number and/or email, you agree to receive order information from us via text and/or email as well as other information pertaining to renting or buying equipment.
                            <span id="dots" class="inline">...</span>
                            <span id="more" class="hidden">
                                Standard text messaging rates may apply. Reply STOP to opt-out of receiving future text messages related to your order and/or rental equipment. You may unsubscribe from any email sent to you.
                            </span>
                            <a href="javascript:void(0)" onclick="readMore()" id="read_more" class="text-blue-600 text-sm hover:text-blue-800 ml-1 cursor-pointer font-semibold  transform  duration-300 ">
                                Learn More
                            </a>
                        </p>
                    </div>
                    <h4 class="text-[18px] font-medium">Delivery information</h4>
                    <div class="relative z-0 w-full mb-4 group">
                        <div class="flex items-center">
                            <input id="toggleCheckbox" type="checkbox" onclick="toggleDiv()" value="delinfo" name="deliveryinfo" class="w-4 h-4 mt-3 text-blue-600 bg-gray-100 border-gray-300 rounded-sm ">
                            <label for="deliveryinfo" class="w-full pt-3 ms-2 text-sm">Same as billing information</label>
                        </div>
                    </div>
                    <div id="deliveryDiv" class="mt-4">
                        <div class="flex gap-x-10">
                            <div class="relative z-0 w-1/2 mb-6 group">
                                <input type="text" name="name" id="name" class="block bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0  peer" placeholder=" " />
                                <label for="name" class="pointer-events-none z-10 px-2 absolute text-sm text-gray-500 duration-300 bg-white transform -translate-y-[1.3rem] translate-x-2 scale-75 top-3 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale[0.85] peer-focus:-translate-y-[1.3rem] peer-focus:translate-x-2 peer-focus:text-black ">
                                    First Name
                                </label>
                            </div>
                            <div class="relative z-0 w-1/2 mb-6 group">
                                <input type="text" name="name" id="name" class="block bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0  peer" placeholder=" " />
                                <label for="name" class="pointer-events-none z-10 px-2 absolute text-sm text-gray-500 duration-300 bg-white transform -translate-y-[1.3rem] translate-x-2 scale-75 top-3 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale[0.85] peer-focus:-translate-y-[1.3rem] peer-focus:translate-x-2 peer-focus:text-black ">
                                    Last Name
                                </label>
                            </div>
                        </div>
                        <div class="flex gap-x-10">
                            <div class="relative z-0 w-2/3 mb-6 group">
                                <input type="email" name="email" id="email" class="block bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0  peer" placeholder=" " />
                                <label for="email" class="pointer-events-none z-10 px-2 absolute text-sm text-gray-500 duration-300 bg-white transform -translate-y-[1.3rem] translate-x-2 scale-75 top-3 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale[0.85] peer-focus:-translate-y-[1.3rem] peer-focus:translate-x-2 peer-focus:text-black ">
                                    Email
                                </label>
                            </div>
                            <div class="relative z-0 w-1/3 mb-6 group">
                                <input type="text" name="phone" id="phone"
                                    class="block bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0 peer"
                                    placeholder=""
                                    oninput="formatPhone(this)"
                                    onblur="validatePhone(this)" />
                                <label for="phone"
                                    class="pointer-events-none z-10 px-2 absolute text-sm text-gray-500 duration-300 bg-white transform -translate-y-[1.3rem] translate-x-2 scale-75 top-3 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-[0.85] peer-focus:-translate-y-[1.3rem] peer-focus:translate-x-2 peer-focus:text-black">
                                    Phone
                                </label>
                            </div>

                        </div>
                        <div class="relative z-0 w-full mb-6 group">
                            <textarea name="address" id="address" class="peer h-[50px] block w-full bg-transparent border border-gray-300 py-2.5 px-2 text-sm text-gray-900 appearance-none focus:outline-none focus:ring-0 peer" placeholder=" "></textarea>
                            <label for="address" class="pointer-events-none absolute px-2 text-sm text-gray-500 bg-white duration-300 transform -translate-y-[1.3rem] translate-x-2 scale-75 top-3 origin-[0] z-10
                                        peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0
                                        peer-focus:scale-[0.85] peer-focus:-translate-y-[1.3rem] peer-focus:translate-x-2 peer-focus:text-black">
                                Address
                            </label>
                        </div>
                        <div class="flex gap-x-10">
                            <div class="relative z-0 w-1/3 mb-6 group">
                                <select id="category" name="category" class="peer block w-full bg-transparent border border-gray-300 py-2.5 px-2 text-sm text-gray-900 appearance-none">
                                    <option disabled selected hidden>Select Delivery Option</option>
                                    <option>10296 High 46, Bon Aqua, TN, 37025</option>
                                    <option>10296 High 46, Bon Aqua, TN, 37025</option>
                                    <option>4385 SR-48, Charlotte, TN, 37036</option>
                                </select>
                                <label for="category" class="pointer-events-none absolute px-2 text-gray-500 text-sm bg-white duration-300 transform top-3 origin-[0] z-10 scale-[0.85] -translate-y-[1.3rem] translate-x-2 text-black">
                                    State
                                </label>
                            </div>
                            <div class="relative z-0 w-1/3 mb-6 group">
                                <input type="text" name="city" id="city" class="block bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0  peer" placeholder=" " />
                                <label for="name" class="pointer-events-none z-10 px-2 absolute text-sm text-gray-500 duration-300 bg-white transform -translate-y-[1.3rem] translate-x-2 scale-75 top-3 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale[0.85] peer-focus:-translate-y-[1.3rem] peer-focus:translate-x-2 peer-focus:text-black ">
                                    City
                                </label>
                            </div>
                            <div class="relative z-0 w-1/3 mb-6 group">
                                <input type="text" name="zipcode" id="zipcode" class="block bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0  peer" placeholder=" " />
                                <label for="name" class="pointer-events-none z-10 px-2 absolute text-sm text-gray-500 duration-300 bg-white transform -translate-y-[1.3rem] translate-x-2 scale-75 top-3 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale[0.85] peer-focus:-translate-y-[1.3rem] peer-focus:translate-x-2 peer-focus:text-black ">
                                    Zip code
                                </label>
                            </div>
                        </div>
                    </div>
                    <h4 class="text-[18px] font-medium">Order notes</h4>
                    <div class="relative z-0 w-full mb-2 group">
                        <textarea name="address" id="address" class="peer block w-full bg-transparent h-[50px] border border-gray-300 py-2.5 px-2 text-sm text-gray-900 appearance-none focus:outline-none focus:ring-0" placeholder="Notes about your order, e.g. special notes for delivery. "></textarea>
                    </div>
                    <div class="relative z-0 w-full mb-4 group">
                        <div class="flex items-center">
                        <input id="taxCheckbox" type="checkbox"
                                class="w-4 h-4 mt-3 text-blue-600 bg-gray-100 border-gray-300 rounded-sm"
                                onclick="handleCheckboxChange(event)">
                        <label for="taxCheckbox" class="w-full pt-3 ms-2 text-sm">Tax Exempt</label>
                        </div>
                    </div>
                    <h4 class="text-[18px] font-medium">Payment method</h4>
                    <div class="border-[1px]">
                        <div class="border-b px-3 py-2">
                            <div class="space-y-2 max-w-[350px]">
                                <label class="inline-flex items-center space-x-2">
                                    <input type="radio" name="option" value="credit" class="form-radio" onchange="toggleCodOption(this)" checked/>
                                    <span>Pay Using Credit or Debit </span>
                                </label>
                                <div id="creditSection" class="mt-4">
                                    <div class="relative h-48 rounded-xl transition-transform duration-700 transform-style preserve-3d shadow-lg">
                                        <!-- Front of Card -->
                                        <div class="absolute w-full h-full backface-hidden rounded-xl p-5 bg-[#ddd] text-white card-front">
                                            <div class="w-10 h-7 relative bg-[#ccc] rounded mt-5 before:w-[70%] before:content-[''] before:h-[60%] before:bg-[#d9d9d9] before:top-[20%] before:rounded-r before:absolute"></div>
                                            <div id="cardNumberDisplay" class="mt-5 text-lg tracking-widest font-mono">•••• •••• •••• ••••</div>
                                            <div class="flex justify-between mt-5 text-xs">
                                                <div>
                                                    <span id="cardHolderDisplay" class="block mt-1 text-lg uppercase font-mono">FULL NAME</span>
                                                </div>
                                                <div>
                                                    <span id="cardExpiryDisplay" class="block mt-1 text-lg font-mono">MM/YY</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Back of Card -->
                                        <div class="absolute w-full h-full backface-hidden rotate-y-180 rounded-xl p-5 bg-gradient-to-r from-indigo-600 to-purple-500 text-white card-back">
                                            <div class="bg-black h-10 my-5"></div>
                                            <div class="flex justify-end mt-2">
                                                <div id="cvcDisplay" class="bg-white text-gray-800 px-3 py-1 rounded text-sm font-mono">CVC</div>
                                            </div>
                                            <div class="mt-5 text-xs text-center">
                                                Keep your CVC code confidential
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-6">
                                        <div class="flex gap-4 mb-4">
                                            <input type="text" id="firstName" placeholder="First name" class="flex-1 input-style bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0">
                                            <input type="text" id="lastName" placeholder="Last name" class="flex-1 input-style bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0">
                                        </div>
                                        <input type="text" id="cardNumber" placeholder="Card number" maxlength="19" class="w-full input-style mb-4 bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0">

                                        <div class="flex gap-4 mb-4">
                                            <input type="text" id="expiryDate" placeholder="MM/YY" maxlength="5" class="flex-1 input-style bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0">
                                            <input type="text" id="cvc" placeholder="CVC" maxlength="3" class="flex-1 input-style bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="px-3 py-2 border-b">
                            <label class="inline-flex items-center space-x-2">
                                <input type="radio" name="option" value="cod" class="form-radio" onchange="toggleCodOption(this)" />
                                <span>Cash on delivery (COD)</span>
                            </label>
                            <div id="codSection" class="hidden">

                            </div>
                        </div>
                        <div class="px-3 py-2">
                            <label class="inline-flex items-center space-x-2">
                                <input type="radio" name="option" value="account" class="form-radio" onchange="toggleCodOption(this)" />
                                <span>Add Account</span>
                            </label>
                            <div id="codSection" class="hidden">

                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <a href="javascript:history.back()" class="text-sm text-blue-600">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </a>
                       <!-- <button type="button" class="toggleCart border-0 bg-yellow-400 text-[14px] px-6 h-[55px] font-medium mt-4 hover:bg-yellow-300  transition-all duration-500 ease-in-out"><a href="terms-condition.php">CHECKOUT</a></button>-->
                        <button type="button" class=" border-0 bg-yellow-400 text-[14px] px-6 h-[55px] font-medium mt-4 hover:bg-yellow-300  transition-all duration-500 ease-in-out"><a href="terms-condition.php">CHECKOUT</a></button>
                    </div>
                </form>
            </div>

            <div class="w-1/3 mt-10 pl-6">
                <div class="border-b pb-4 mb-6">
                    <div class="flex">
                        <div class="w-1/3">
                            <div class="border rounded relative ">
                                <img src="{{ asset('storage/front/images/product-detail/skid-steer-daily.png') }}" alt="image" class="w-24 p-1">
                                <span class="bg-gray-400 w-[22px] h-[22px] text-white rounded-full absolute -top-2 -right-[8px] text-[14px] text-center font-bold">1</span>
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
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden ">
    <div class="taxModal-box bg-white rounded-lg shadow-lg w-full md:max-w-lg relative max-w-[90%]">
        <div class="flex justify-between bg-black p-4 py-2 items-center rounded-t-lg">
            <h5 class="text-lg text-white">Enter Admin Code</h5>
            <button onclick="cancelModal()" class="px-4 py-2 text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-6 py-4">
            <span class="font-medium">Note: </span>
            <p class="text-gray-700 mb-4 inline text-sm italic">Tax Exempt sales must be pre-approved with documentation on file prior to placing the order. Call if you need assistance with placing an order with Tax Exempt status.</p>
            <form action="#">
                <input type="password" name="name" id="name" class="mt-2 block bg-transparent border-[1px] py-2.5 px-2 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0 " placeholder=" Admin Code" />
            </form>
        </div>

        <div class="border-t p-6 py-4">
            <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-end gap-3">
                <button onclick="cancelModal()" class="border-0 bg-yellow-400 text-[14px] px-6 font-bold hover:bg-yellow-300  transition-all duration-500 ease-in-out order-2 md:order-1">Cancel</button>
                <button onclick="confirmModal()" class="border-0 bg-yellow-400 text-[14px] px-6 py-3 font-bold hover:bg-yellow-300  transition-all duration-500 ease-in-out order-1 md:order-2">Submit</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>

    document.addEventListener('DOMContentLoaded', function () {
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

    cardNumberInput.addEventListener('input', function () {
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

    expiryDateInput.addEventListener('input', function () {
        let value = this.value.replace(/\D/g, '');

        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }

        this.value = value;
        expiryDateDisplay.textContent = value || 'MM/YY';
    });

    cvcInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
        cvcDisplay.textContent = this.value || 'CVC';
    });

    cvcInput.addEventListener('focus', function () {
        card.classList.add('flipped');
    });

    cvcInput.addEventListener('blur', function () {
        if (!cvcInput.matches(':hover')) {
            card.classList.remove('flipped');
        }
    });

    cvcInput.addEventListener('mouseenter', function () {
        card.classList.add('flipped');
    });

    cvcInput.addEventListener('mouseleave', function () {
        if (document.activeElement !== cvcInput) {
            card.classList.remove('flipped');
        }
    });

    [cardNumberInput, firstNameInput, lastNameInput, expiryDateInput].forEach(input => {
        input.addEventListener('focus', function () {
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
    document.addEventListener('mousedown', function (event) {
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
