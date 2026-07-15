@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Page Title Section -->
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-[#f9fafc]">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div id="breadcrumbs" class="pt-[100px] pb-[20px]">
                <div class="w-full">
                    <div class="flex justify-between items-center">
                        <h1 class="text-[28px] md:text-[34px] lg:text-[40px] tracking-[-2px] leading-[110%] font-bold">
                            {{ $title }}</h1>
                        <ul
                            class="px-[20px] py-2 lg:py-3 max-w-full text-[14px] font-medium items-center inline-flex gap-3 relative">
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

    @if ($isExpired)
        <section class="lg:pb-[50px]">
            <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
                <div class="py-24 flex flex-col items-center text-center">
                    <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-3">Payment Link Expired</h2>
                    <p class="text-gray-500 max-w-md text-base leading-relaxed">
                        This payment link has expired. Links are valid for 30 days after the delivery date.
                        Please contact us if you still need to complete your payment.
                    </p>
                    <div class="mt-8">
                        <a href="{{ route('front.home.index') }}"
                            class="inline-block bg-gray-800 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-700 transition">
                            Go to Home
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @else
    <section class="lg:pb-[50px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="flex flex-col md:flex-row gap-2">

                <!-- Left: Order Info + Card Payment Form -->
                <div class="w-full md:w-1/2 md:border-r px-0 lg:px-4 md:pr-6 pb-12">

                    <!-- Order reference -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mt-2 mb-4 gap-3">
                        <h2 class="text-2xl font-bold m-0">Order Information</h2>
                        <span class="text-sm font-medium text-gray-500">{{ $order->order_number }}</span>
                    </div>

                    <!-- Customer / billing info (read-only) -->
                    @php
                        $addr = $order->billingAddress ?? $order->shippingAddress;
                    @endphp
                    @if ($addr || $customer)
                        <div class="border-2 border-dashed border-green-600 rounded-md p-4 mb-6 relative capitalize bg-green-50/10">
                            <h5 class="text-base font-bold">
                                {{ $addr ? $addr->full_name : ($customer->first_name . ' ' . $customer->last_name) }}
                            </h5>
                            @if ($customer->company_name)
                                <p class="text-sm">Company: {{ $customer->company_name }}</p>
                            @endif
                            @if ($addr)
                                <p class="text-sm">{{ $addr->full_address }}</p>
                                @if ($addr->phone)
                                    <p class="text-sm">Phone: {{ $addr->phone }}</p>
                                @endif
                            @endif
                            <p class="mt-1 text-sm">Email: {{ $customer->email }}</p>
                            <span class="text-green-600 font-medium text-xs absolute top-2 right-3">Verified</span>
                        </div>
                    @endif

                    <!-- Card Payment Form -->
                    <form id="payment-form" autocomplete="off">
                        @csrf

                        <h4 class="text-lg font-semibold mb-3">Payment method</h4>

                        <div id="cardPaymentOption" class="border-2 border-blue-500 rounded-lg p-4">
                            <div class="inline-flex items-center gap-2 mb-3">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <span class="font-medium">Credit or Debit Card</span>
                            </div>

                            <!-- Card Visual -->
                            <div class="relative h-card-height lg:h-52 w-full max-w-sm mt-2 card-container">
                                <!-- Card Front -->
                                <div id="cardFront"
                                    class="absolute w-full h-full rounded-xl p-5 bg-gradient-to-r from-gray-300 to-gray-300 text-white shadow-lg transition-all duration-300 card-face card-front">
                                    <div
                                        class="w-10 h-7 relative bg-gray-400 rounded mt-5 before:w-[70%] before:content-[''] before:h-[60%] before:bg-gray-300 before:top-[20%] before:rounded-r before:absolute">
                                    </div>
                                    <div id="displayCardNumber" class="mt-8 text-2xl tracking-widest font-mono">
                                        •••• •••• •••• ••••
                                    </div>
                                    <div class="flex justify-between mt-8 text-base">
                                        <span id="displayFullName" class="uppercase font-mono">FULL NAME</span>
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
                            <div class="grid md:grid-cols-2 gap-4 mt-4 max-w-sm">
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="First name" id="firstName" name="firstName"
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500 w-full" />
                                </div>
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="Last name" id="lastName" name="lastName"
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500 w-full" />
                                </div>
                                <div class="md:col-span-2">
                                    <input type="text" placeholder="Card number" maxlength="19" id="cardNumber"
                                        name="cardNumber"
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500 w-full" />
                                </div>
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="MM/YY" maxlength="5" id="expiry" name="expiry"
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500 w-full" />
                                </div>
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="CVC" maxlength="4" id="cvc" name="cvc"
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500 w-full" />
                                </div>
                                <input type="hidden" name="opaqueDataValue" id="opaqueDataValue" />
                                <input type="hidden" name="opaqueDataDescriptor" id="opaqueDataDescriptor" />
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="mt-6 flex justify-between items-center">
                            <a href="javascript:history.back()"
                                class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                                <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Back
                            </a>
                            <button type="submit" id="payBtn"
                                class="bg-yellow-400 hover:bg-yellow-300 text-black text-base font-medium rounded px-8 py-3 transition flex items-center gap-2">
                                <span id="payBtnText">Pay ${{ number_format($order->balance_due, 2) }}</span>
                                <span id="payBtnLoader" class="hidden">
                                    <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin text-yellow-600" />
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right: Order Summary -->
                <div class="w-full md:w-1/2 mt-0 lg:mt-10 pl-0 lg:pl-6">
                    <div class="border-b pb-4 mb-4">
                        <h2 class="text-2xl font-bold mb-1">Order Summary</h2>
                        <p class="text-sm text-gray-500">Order {{ $order->order_number }} &mdash; {{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('M d, Y') : '' }}</p>
                    </div>

                    <!-- Items (detailed) -->
                    @foreach ($order->products as $item)
                        @php $d = $item->product_data ?? []; @endphp
                        <div class="border-b pb-4 mb-6">
                            <div class="flex flex-col sm:flex-row items-start gap-3 py-2">

                                {{-- Product image with quantity badge --}}
                                <div class="relative flex-shrink-0" style="min-width:80px;max-width:80px;">
                                    <img src="{{ $d['product_image_url'] ?? asset('storage/admin/images/error/No_Image_Available.jpg') }}"
                                         alt="{{ $item->product_name }}"
                                         class="w-20 h-20 rounded border object-cover" />
                                    <span class="bg-yellow-400 w-[22px] h-[22px] text-black rounded-full absolute -top-2 -right-[8px] text-[14px] text-center font-bold leading-[22px]">
                                        {{ $item->quantity }}
                                    </span>
                                </div>

                                {{-- Details --}}
                                <div class="flex-1 min-w-0 flex flex-col justify-center w-full">

                                    {{-- Name + price --}}
                                    <h4 class="text-sm flex items-center gap-2 font-medium">
                                        <span>{{ $item->product_name }}
                                            @if (!empty($d['product_variant']))
                                                <span class="text-xs font-normal">- {{ ucfirst($d['product_variant']) }}</span>
                                            @endif
                                        </span>
                                        <span class="font-bold ml-auto">${{ number_format($d['product_price'] ?? $item->price, 2) }}</span>
                                    </h4>

                                    {{-- Store --}}
                                    @if (!empty($d['store_name']))
                                        <div class="flex flex-col gap-y-1 mt-1">
                                            <div class="text-xs underline">Store:</div>
                                            <ul>
                                                <li class="text-xs before:content-['-'] before:pr-1">{{ ucfirst($d['store_name']) }}</li>
                                            </ul>
                                        </div>
                                    @endif

                                    {{-- Distance range --}}
                                    @if (!empty($d['distance_range']))
                                        <div class="flex flex-col gap-y-1 mt-1">
                                            <div class="text-xs underline">Distance Range:</div>
                                            <ul>
                                                <li class="text-xs before:content-['-'] before:pr-1">{{ ucfirst($d['distance_range']) }}</li>
                                            </ul>
                                        </div>
                                    @endif

                                    {{-- Delivery / service option --}}
                                    @if (!empty($d['service_option']))
                                        <div class="flex flex-col gap-y-1 mt-1">
                                            <div class="text-xs flex justify-between items-center">
                                                <span class="underline">Delivery:</span>
                                                <span class="text-black font-bold">+${{ number_format($d['service_option_price'] ?? 0, 2) }}</span>
                                            </div>
                                            <ul>
                                                <li class="text-xs before:content-['-'] before:pr-1">{{ \App\Helpers\CustomHelper::serviceOptionLabel($d['service_option']) }}</li>
                                            </ul>
                                        </div>
                                    @endif

                                    {{-- Options: rental items + product option items --}}
                                    @php
                                        $optionRows = [];
                                        foreach ($d['product_rental_items_prices'] ?? [] as $rentalKey => $optPrice) {
                                            $case = collect(\App\Enums\Products\ProductCustomStaticLabel::cases())->firstWhere('name', $rentalKey);
                                            $name = $case?->label() ?? ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey)));
                                            $qty  = $case ? ($item->quantity ?? 1) : 1;
                                            $optionRows[] = [
                                                'label' => $name . ' ' . \App\Helpers\CustomHelper::formatCurrency($optPrice) . " (x{$qty})",
                                                'price' => \App\Helpers\CustomHelper::formatCurrency($optPrice * $qty),
                                            ];
                                        }
                                        foreach ($d['product_option_items'] ?? [] as $opt) {
                                            if (empty($opt['name'])) continue;
                                            $qty    = (!empty($opt['charged']) && $opt['charged'] === 'Unlimited') ? ($item->quantity ?? 1) : 1;
                                            $total  = ($opt['price'] ?? 0) * $qty;
                                            $optionRows[] = [
                                                'label' => $opt['name'] . ' ' . \App\Helpers\CustomHelper::formatCurrency($opt['price'] ?? 0) . " (x{$qty})",
                                                'price' => \App\Helpers\CustomHelper::formatCurrency($total),
                                            ];
                                        }
                                    @endphp
                                    @if (!empty($optionRows))
                                        <div class="mt-1">
                                            <div class="text-xs underline mb-1">Options:</div>
                                            <ul class="flex flex-col">
                                                @foreach ($optionRows as $row)
                                                    <li class="flex justify-between leading-[16px]">
                                                        <span class="text-xs before:content-['-'] before:pr-1">{{ $row['label'] }}</span>
                                                        <span class="text-xs font-bold">+ {{ $row['price'] }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Schedule date --}}
                            @php $schedDate = $d['delivery_date'] ?? ($item->delivery_date ? \Carbon\Carbon::parse($item->delivery_date)->format('m/d/Y') : null); @endphp
                            @if ($schedDate)
                                <p class="mt-2 text-[15px]">Schedule Date: {{ $schedDate }}</p>
                            @endif
                        </div>
                    @endforeach

                    <!-- Totals -->
                    <ul class="flex flex-col gap-2">
                        <li class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-bold">${{ number_format($order->subtotal, 2) }}</span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-gray-600">Tax</span>
                            <span class="font-bold">${{ number_format($order->tax_amount, 2) }}</span>
                        </li>
                        @if ($order->discount_amount > 0)
                            <li class="flex justify-between">
                                <span class="text-gray-600">Discount</span>
                                <span class="font-bold text-green-600">-${{ number_format($order->discount_amount, 2) }}</span>
                            </li>
                        @endif
                        <li class="flex justify-between border-t pt-2 mt-1">
                            <span class="font-bold">Total</span>
                            <span class="font-bold">${{ number_format($order->grand_total, 2) }}</span>
                        </li>
                        @if ($order->total_paid > 0)
                            <li class="flex justify-between">
                                <span class="text-gray-600">Already Paid</span>
                                <span class="font-bold text-green-600">-${{ number_format($order->total_paid, 2) }}</span>
                            </li>
                            <li class="flex justify-between border-t pt-2 mt-1 text-blue-700">
                                <span class="font-bold text-base">Balance Due</span>
                                <span class="font-bold text-base">${{ number_format($order->balance_due, 2) }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </section>
    @endif
@endsection

@if (!$isExpired)
@push('js')
    @if ($paymentSetting['payment_test_mode'] ?? false)
        <script type="text/javascript" src="https://jstest.authorize.net/v1/Accept.js"></script>
    @else
        <script type="text/javascript" src="https://js.authorize.net/v1/Accept.js"></script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form         = document.getElementById('payment-form');
            const payBtn       = document.getElementById('payBtn');
            const payBtnText   = document.getElementById('payBtnText');
            const payBtnLoader = document.getElementById('payBtnLoader');

            // One token per page load, reused across retries of the same
            // submission (a double-click, or a resubmit after the button
            // is re-enabled on failure) — lets the server recognize a
            // duplicate rather than charging the card twice.
            const idempotencyToken = (window.crypto && window.crypto.randomUUID)
                ? window.crypto.randomUUID()
                : `${Date.now()}-${Math.random().toString(36).slice(2)}`;

            const cardNumberInput  = document.getElementById('cardNumber');
            const firstNameInput   = document.getElementById('firstName');
            const lastNameInput    = document.getElementById('lastName');
            const expiryInput      = document.getElementById('expiry');
            const cvcInput         = document.getElementById('cvc');

            const displayCardNumber = document.getElementById('displayCardNumber');
            const displayFullName   = document.getElementById('displayFullName');
            const displayExpiry     = document.getElementById('displayExpiry');
            const displayCVC        = document.getElementById('displayCVC');
            const cardFront         = document.getElementById('cardFront');
            const cardBack          = document.getElementById('cardBack');

            // --- Card visual live updates ---
            function formatCardNumber(v) {
                return v.replace(/\D/g, '').substring(0, 16).replace(/(.{4})/g, '$1 ').trim();
            }

            function formatExpiry(v) {
                v = v.replace(/[^0-9/]/g, '').substring(0, 5);
                if (v.length === 2 && !v.includes('/')) v = v + '/';
                return v;
            }

            cardNumberInput.addEventListener('input', function () {
                this.value = formatCardNumber(this.value);
                displayCardNumber.textContent = this.value || '•••• •••• •••• ••••';
            });

            function updateName() {
                const name = (firstNameInput.value + ' ' + lastNameInput.value).trim();
                displayFullName.textContent = name || 'FULL NAME';
            }

            firstNameInput.addEventListener('input', updateName);
            lastNameInput.addEventListener('input', updateName);

            expiryInput.addEventListener('input', function () {
                this.value = formatExpiry(this.value);
                displayExpiry.textContent = this.value || 'MM/YY';
            });

            cvcInput.addEventListener('input', function () {
                displayCVC.textContent = this.value || 'CVC';
            });

            cvcInput.addEventListener('focus', function () {
                cardFront.style.display = 'none';
                cardBack.style.display  = 'block';
            });

            cvcInput.addEventListener('blur', function () {
                cardFront.style.display = 'block';
                cardBack.style.display  = 'none';
            });

            // --- Submit ---
            function disableBtn() {
                payBtn.disabled = true;
                payBtnText.classList.add('hidden');
                payBtnLoader.classList.remove('hidden');
            }

            function enableBtn() {
                payBtn.disabled = false;
                payBtnText.classList.remove('hidden');
                payBtnLoader.classList.add('hidden');
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const cardNumber = cardNumberInput.value.replace(/\s/g, '');
                const expiry     = expiryInput.value.trim();
                const cvc        = cvcInput.value.trim();
                const fName      = firstNameInput.value.trim();
                const lName      = lastNameInput.value.trim();

                // Basic validation
                function luhnCheck(num) {
                    let arr = (num + '').split('').reverse().map(x => parseInt(x));
                    let sum = arr.reduce((acc, val, idx) => {
                        if (idx % 2) { val *= 2; if (val > 9) val -= 9; }
                        return acc + val;
                    }, 0);
                    return sum % 10 === 0;
                }

                try {
                    if (!fName || !lName) throw new Error('Please enter the cardholder name.');
                    if (!/^\d{13,19}$/.test(cardNumber) || !luhnCheck(cardNumber))
                        throw new Error('Invalid or missing card number.');
                    if (!/^\d{2}\/\d{2}$/.test(expiry))
                        throw new Error('Invalid or missing expiry date. Use MM/YY.');

                    const [mm, yy]  = expiry.split('/');
                    const now       = new Date();
                    const expYear   = 2000 + parseInt(yy, 10);
                    const expMonth  = parseInt(mm, 10);

                    if (
                        expMonth < 1 || expMonth > 12 ||
                        expYear < now.getFullYear() ||
                        (expYear === now.getFullYear() && expMonth < (now.getMonth() + 1))
                    ) throw new Error('Card expiry is in the past.');

                    if (!/^\d{3,4}$/.test(cvc)) throw new Error('Invalid or missing CVC code.');

                    disableBtn();

                    const [expMM] = expiry.split('/');
                    const expYYYY = '20' + yy;

                    const authData  = {
                        clientKey:  "{{ safe_decrypt($paymentSetting['payment_api_public_key'] ?? '') }}",
                        apiLoginID: "{{ safe_decrypt($paymentSetting['payment_api_key'] ?? '') }}"
                    };
                    const cardData  = { cardNumber, month: expMM, year: expYYYY, cardCode: cvc };
                    const secureData = { authData, cardData };

                    Accept.dispatchData(secureData, function (response) {
                        if (response.messages.resultCode === 'Error') {
                            const msg = response.messages.message.map(m => m.text).join(', ');
                            notyf.error('Card Error: ' + msg);
                            enableBtn();
                            return;
                        }

                        document.getElementById('opaqueDataValue').value      = response.opaqueData.dataValue;
                        document.getElementById('opaqueDataDescriptor').value = response.opaqueData.dataDescriptor;

                        const postUrl = "{{ route('front.checkout.order-payment-form.store', $encrypted_id) }}";

                        apiFetch(postUrl, {
                            method:  'POST',
                            headers: {
                                'Content-Type':     'application/json',
                                'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                firstName:            fName,
                                lastName:             lName,
                                opaqueDataValue:      response.opaqueData.dataValue,
                                opaqueDataDescriptor: response.opaqueData.dataDescriptor,
                                idempotency_token:    idempotencyToken,
                            })
                        })
                        .then(res => {
                            if (res.success) {
                                window.location.replace(res.redirect_url);
                            } else {
                                notyf.error(res.message || 'Payment failed. Please try again.');
                                enableBtn();
                            }
                        })
                        .catch(() => {
                            notyf.error('An error occurred. Please try again.');
                            enableBtn();
                        });
                    });

                } catch (err) {
                    notyf.error(err.message);
                }
            });
        });
    </script>
@endpush
@endif
