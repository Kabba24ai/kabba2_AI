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
                            class="border-yellow-400 px-[20px] py-2 lg:py-3 max-w-full text-[14px] font-medium items-center inline-flex gap-3 relative border-2 ">
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
            <div class="flex flex-col md:flex-row gap-2">
                <div class="w-full md:w-1/2 md:border-r px-4 md:pr-8 pb-12">
                    <!-- Billing Info -->
                    <h2 class="text-2xl font-bold mt-2">Billing information</h2>

                    {{ html()->form()->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-4',
                            'id' => 'checkout-form',
                        ])->open() }}
                    @csrf
                    <input type="hidden" name="cart" id="cart-input">
                    @error('cart')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                    <!-- Address Selection -->
                    @auth('customer')
                        @php
                            $addresses =
                                auth('customer')->user()->addresses()->where('type', 'Billing')->get() ?? collect();
                            $primaryAddress = $addresses->firstWhere('is_primary', true);
                        @endphp
                        <div>
                            <label for="selAddress" class="block text-sm font-medium text-gray-800 mt-3">
                                Select available addresses:
                            </label>
                            <select id="selAddress" name="address_id"
                                class="block w-full border border-gray-300 rounded-md py-2 px-3 text-gray-700 bg-white capitalize">
                                <option disabled selected value="">Select Address...</option>
                                <option value="new" class="font-bold">+ Add New Address</option>
                                @foreach ($addresses as $addressItem)
                                    <option value="{{ $addressItem->id }}" data-addressItem="{{ json_encode($addressItem) }}"
                                        {{ old('selAddress') == $addressItem->id || (empty(old('selAddress')) && $addressItem->is_primary) ? 'selected' : '' }}>
                                        {{ collect([$addressItem->address, $addressItem->city, $addressItem->state_name, $addressItem->zip_code])->filter()->join(', ') }}
                                    </option>
                                @endforeach
                            </select>
                            <!-- Selected Address Preview -->
                            <div id="selectedAddressPreview"
                                class="border-2 border-dashed border-green-600 rounded-md p-4 mt-4 relative capitalize bg-green-50/10 {{ $primaryAddress ? '' : 'hidden' }}">
                                <h5 class="text-base font-bold" id="addressName">
                                    {{ $primaryAddress ? $primaryAddress->full_name : '' }}
                                </h5>
                                <p id="addressFull">
                                    {{ $primaryAddress? collect([$primaryAddress->addressItem, $primaryAddress->city, $primaryAddress->state_name, $primaryAddress->zip_code])->filter()->join(', '): '' }}
                                </p>
                                <p class="text-sm" id="addressPhone">
                                    {{ $primaryAddress ? 'Phone: ' . $primaryAddress->phone : '' }}
                                </p>
                                <p class="mt-2 text-sm" id="addressEmail">
                                    {{ $primaryAddress ? 'Email: ' . $primaryAddress->email : '' }}
                                </p>
                                <span class="text-green-600 font-medium text-xs absolute top-2 right-3" id="addressDefault">
                                    {{ $primaryAddress && $primaryAddress->is_primary ? 'Default' : '' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="mb-6 text-sm text-gray-600">
                            Already have an account?
                            <a href="{{ route('front.auth.login.index') }}"
                                class="text-blue-600 hover:underline ml-1">Login</a>
                        </div>
                        @php
                            $primaryAddress = null;
                        @endphp
                    @endauth
                    <!-- Tax Exempt -->
                    <div class="flex items-center gap-2">
                        <input id="taxExempt" type="checkbox" class="accent-blue-500 h-4 w-4" />
                        <label for="taxExempt" class="text-sm">Tax Exempt</label>
                    </div>

                    <div id="billingDiv" class="space-y-4 {{ $primaryAddress ? 'hidden' : '' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 ">
                            <div>
                                <label for="billingFirstName" class="block text-sm text-gray-600 mb-1">First Name</label>
                                {{ html()->text('billingFirstName', old('billingFirstName', $primaryAddress ? $primaryAddress->first_name : null))->class([
                                        'w-full rounded-lg border border border-gray-300 px-4 py-2  shadow-sm text-sm',
                                        'input-error' => $errors->has('billingFirstName'),
                                    ])->attributes([
                                        'maxlength' => 240,
                                        'data-parsley-maxlength' => 240,
                                        'placeholder' => 'First Name',
                                        'autocomplete' => 'off',
                                        'id' => 'billingFirstName',
                                    ])->required() }}
                                <span id="errorBillingFirstName"></span>
                                @error('billingFirstName')
                                    <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="billingLastName" class="block text-sm text-gray-600 mb-1">Last Name</label>
                                {{ html()->text('billingLastName', old('billingLastName', $primaryAddress ? $primaryAddress->last_name : null))->class([
                                        'w-full rounded-lg border border border-gray-300 px-4 py-2  shadow-sm text-sm',
                                        'input-error' => $errors->has('billingLastName'),
                                    ])->attributes([
                                        'maxlength' => 240,
                                        'data-parsley-maxlength' => 240,
                                        'placeholder' => 'Last Name',
                                        'autocomplete' => 'off',
                                        'id' => 'billingLastName',
                                    ])->required() }}
                                <span id="errorBillingLastName"></span>
                                @error('billingLastName')
                                    <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="billingCompany" class="block text-sm text-gray-600 mb-1">Company Name</label>
                            {{ html()->text('billingCompany', old('billingCompany', $primaryAddress ? $primaryAddress->company : null))->class(
                                    'w-full rounded-lg border border-gray-300 px-4 py-2  shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                                )->attributes([
                                    'maxlength' => 240,
                                    'data-parsley-maxlength' => 240,
                                    'placeholder' => 'Company Name',
                                    'autocomplete' => 'off',
                                    'id' => 'billingCompany',
                                ]) }}
                            @error('billingCompany')
                                <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <label for="billingEmail" class="block text-sm text-gray-600 mb-1">Email</label>
                                {{ html()->email(
                                        'billingEmail',
                                        auth('customer')->check()
                                            ? auth('customer')->user()->email
                                            : old('billingEmail', $primaryAddress ? $primaryAddress->email : null),
                                    )->class(
                                        'w-full rounded-lg border border-gray-300 px-4 py-2  shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                                    )->attributes(
                                        array_merge(
                                            [
                                                'maxlength' => 240,
                                                'data-parsley-type' => 'email',
                                                'placeholder' => 'Email',
                                                'autocomplete' => 'off',
                                                'id' => 'billingEmail',
                                            ],
                                            auth('customer')->check() ? ['readonly' => 'readonly'] : [],
                                        ),
                                    )->required() }}
                                @error('billingEmail')
                                    <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="billingPhone" class="block text-sm text-gray-600 mb-1">Phone</label>
                                {{ html()->text('billingPhone', old('billingPhone', $primaryAddress ? $primaryAddress->phone : null))->class(
                                        'masked-phone w-full rounded-lg border border-gray-300 px-4 py-2  shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                                    )->attributes([
                                        'maxlength' => 240,
                                        'placeholder' => 'Phone',
                                        'autocomplete' => 'off',
                                        'id' => 'billingPhone',
                                    ])->required() }}
                                @error('billingPhone')
                                    <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="billingAddress" class="block text-sm text-gray-600 mb-1">Address</label>
                            {{ html()->textarea('billingAddress', old('billingAddress', $primaryAddress ? $primaryAddress->address : null))->class(
                                    'w-full rounded-lg border border-gray-300 px-4 py-2  shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                                )->attributes([
                                    'rows' => 2,
                                    'maxlength' => 500,
                                    'placeholder' => 'Address',
                                    'id' => 'billingAddress',
                                ])->required() }}
                            @error('billingAddress')
                                <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="billingState" class="block text-sm text-gray-600 mb-1">State</label>
                                <select name="billingState" id="billingState"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2  shadow-sm text-sm focus:border-gray-900 focus:outline-none">
                                    <option disabled value="">Select State...</option>
                                    @foreach ($states as $id => $name)
                                        <option value="{{ $id }}" @selected(old('billingState', $primaryAddress ? $primaryAddress->state_id : null) == $id)>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('billingState')
                                    <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="billingCity" class="block text-sm text-gray-600 mb-1">City</label>
                                {{ html()->text('billingCity', old('billingCity', $primaryAddress ? $primaryAddress->city : null))->class(
                                        'w-full rounded-lg border border-gray-300 px-4 py-2  shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                                    )->attributes([
                                        'maxlength' => 50,
                                        'placeholder' => 'City',
                                        'autocomplete' => 'off',
                                        'id' => 'billingCity',
                                    ])->required() }}
                                @error('billingCity')
                                    <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="billingZip" class="block text-sm text-gray-600 mb-1">ZIp Code</label>
                                {{ html()->text('billingZip', old('billingZip', $primaryAddress ? $primaryAddress->zip_code : null))->class(
                                        'w-full rounded-lg border border-gray-300 px-4 py-2  shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                                    )->attributes([
                                        'maxlength' => 8,
                                        'placeholder' => 'Zip code',
                                        'autocomplete' => 'off',
                                        'id' => 'billingZip',
                                    ])->required() }}
                                @error('billingZip')
                                    <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- <!-- Password section -->
                                            <div class="flex items-center gap-2 mt-2">
                                                <input id="showPassword" name="showPassword" type="checkbox" value="Yes"
                                                    class="accent-blue-600 h-4 w-4" checked />
                                                <label for="showPassword" class="text-sm">Enter Your Custom Password</label>
                                            </div>
                                            <div id="passwordFields" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                                <div>
                                                    <label for="password" class="block text-sm text-gray-600 mb-1">Password</label>
                                                    <input type="password" name="password" placeholder="Password" autocomplete="new-password"
                                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 shadow-sm text-sm focus:border-gray-900 focus:outline-none{{ $errors->has('password') ? ' border-red-400' : '' }}" />
                                                    @error('password')
                                                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label for="password_confirmation" class="block text-sm text-gray-600 mb-1">Password
                                                        confirmation</label>
                                                    <input type="password" name="password_confirmation" placeholder="Password confirmation"
                                                        autocomplete="new-password"
                                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 shadow-sm text-sm focus:border-gray-900 focus:outline-none{{ $errors->has('password_confirmation') ? ' border-red-400' : '' }}" />
                                                    @error('password_confirmation')
                                                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div> --}}


                        {{-- <div class="text-xs text-gray-600 leading-relaxed mt-2">
                                                By providing your phone number and/or email, you agree to receive order information from
                                                us via text and/or email as well as other information pertaining to renting or buying
                                                equipment.
                                                <a href="#" class="text-blue-600 hover:underline ml-1">Learn More</a>
                                            </div> --}}
                    </div>


                    <!-- Delivery Info -->
                    <h4 class="text-lg font-semibold">Delivery information</h4>
                    <div class="flex items-center gap-2 mb-4">
                        <input type="hidden" name="sameAsBilling" value="{{ old('sameAsBilling', 'Yes') }}">
                        <input id="sameAsBilling" type="checkbox" class="accent-blue-500 h-4 w-4"
                            {{ old('sameAsBilling', 'Yes') == 'Yes' ? 'checked' : '' }} />
                        <label for="sameAsBilling" class="text-sm">Same as billing information</label>
                        @error('sameAsBilling')
                            <span class="text-sm text-red-500 ml-2">{{ $message }}</span>
                        @enderror
                    </div>
                    <div id="deliveryDiv" class="space-y-4 {{ old('sameAsBilling', 'Yes') == 'Yes' ? 'hidden' : '' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="deliveryFirstName" class="block text-sm text-gray-600 mb-1">First Name</label>
                                {{ html()->text('deliveryFirstName')->class(
                                        'w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none' .
                                            ($errors->has('deliveryFirstName') ? ' border-red-400' : ''),
                                    )->attributes([
                                        'placeholder' => 'First Name',
                                        'autocomplete' => 'off',
                                        'id' => 'deliveryFirstName',
                                    ]) }}
                                @error('deliveryFirstName')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="deliveryLastName" class="block text-sm text-gray-600 mb-1">Last Name</label>
                                {{ html()->text('deliveryLastName')->class(
                                        'w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none' .
                                            ($errors->has('deliveryLastName') ? ' border-red-400' : ''),
                                    )->attributes([
                                        'placeholder' => 'Last Name',
                                        'autocomplete' => 'off',
                                        'id' => 'deliveryLastName',
                                    ]) }}
                                @error('deliveryLastName')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="deliveryEmail" class="block text-sm text-gray-600 mb-1">Email</label>
                                {{ html()->email('deliveryEmail')->class(
                                        'w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none' .
                                            ($errors->has('deliveryEmail') ? ' border-red-400' : ''),
                                    )->attributes([
                                        'placeholder' => 'Email',
                                        'autocomplete' => 'off',
                                        'id' => 'deliveryEmail',
                                    ])->attributes([
                                        'readonly' => true,
                                    ]) }}
                                @error('deliveryEmail')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="deliveryPhone" class="block text-sm text-gray-600 mb-1">Phone</label>
                                {{ html()->text('deliveryPhone')->class(
                                        'masked-phone w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none' .
                                            ($errors->has('deliveryPhone') ? ' border-red-400' : ''),
                                    )->attributes([
                                        'placeholder' => 'Phone',
                                        'autocomplete' => 'off',
                                        'id' => 'deliveryPhone',
                                    ]) }}
                                @error('deliveryPhone')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="deliveryAddress" class="block text-sm text-gray-600 mb-1">Address</label>
                            {{ html()->textarea('deliveryAddress')->class(
                                    'w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none' .
                                        ($errors->has('deliveryAddress') ? ' border-red-400' : ''),
                                )->attributes([
                                    'rows' => 2,
                                    'placeholder' => 'Address',
                                    'id' => 'deliveryAddress',
                                ]) }}
                            @error('deliveryAddress')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="deliveryState" class="block text-sm text-gray-600 mb-1">State</label>
                                <select name="deliveryState" id="deliveryState"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none{{ $errors->has('deliveryState') ? ' border-red-400' : '' }}">
                                    <option disabled value="">Select State...</option>
                                    @foreach ($states as $id => $name)
                                        <option value="{{ $id }}" @selected(old('deliveryState') == $id)>
                                            {{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('deliveryState')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="deliveryCity" class="block text-sm text-gray-600 mb-1">City</label>
                                {{ html()->text('deliveryCity')->class(
                                        'w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none' .
                                            ($errors->has('deliveryCity') ? ' border-red-400' : ''),
                                    )->attributes([
                                        'placeholder' => 'City',
                                        'autocomplete' => 'off',
                                        'id' => 'deliveryCity',
                                    ]) }}
                                @error('deliveryCity')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="deliveryZip" class="block text-sm text-gray-600 mb-1">Zip code</label>
                                {{ html()->text('deliveryZip')->class(
                                        'w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none' .
                                            ($errors->has('deliveryZip') ? ' border-red-400' : ''),
                                    )->attributes([
                                        'placeholder' => 'Zip Code',
                                        'autocomplete' => 'off',
                                        'id' => 'deliveryZip',
                                    ]) }}
                                @error('deliveryZip')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>


                    <!-- Order Notes -->
                    <div>
                        <h4 class="text-lg font-semibold mb-1">Order notes</h4>
                        {{ html()->textarea('orderNotes')->class(
                                'w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none' .
                                    ($errors->has('orderNotes') ? ' border-red-400' : ''),
                            )->attributes([
                                'rows' => 2,
                                'placeholder' => 'Notes about your order, e.g. special notes for delivery.',
                                'id' => 'orderNotes',
                            ]) }}
                        @error('orderNotes')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>



                    <!-- Payment Method -->
                    <div class="mx-auto" id="paymentForm">
                        <h4 class="text-lg font-semibold mb-3">Payment method</h4>
                        <div class="space-y-4">

                            <!-- Credit/Debit -->
                            <div class="border-2 rounded-lg p-4 payment-option {{ old('payment') == 'Card' ? 'border-blue-500' : '' }}"
                                data-value="Card">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="payment" value="Card"
                                        {{ old('payment') == 'Card' ? 'checked' : '' }} />
                                    <span>Pay Using Credit or Debit</span>
                                </label>
                                <div id="cardSection" class="{{ old('payment') == 'Card' ? 'block' : 'hidden' }}">
                                    <!-- Card Visual -->
                                    <div class="relative h-52 w-full max-w-md  mt-4 card-container">
                                        <!-- Card Front -->
                                        <div id="cardFront"
                                            class="absolute w-full h-full rounded-xl p-5 bg-gradient-to-r from-gray-300 to-gray-300 text-white shadow-lg transition-all duration-300 card-face card-front"
                                            style="{{ old('payment') == 'Card' ? '' : 'display:block' }}">
                                            <div
                                                class="w-10 h-7 relative bg-gray-400 rounded mt-5 before:w-[70%] before:content-[''] before:h-[60%] before:bg-gray-300 before:top-[20%] before:rounded-r before:absolute">
                                            </div>
                                            <div id="displayCardNumber" class="mt-8 text-2xl tracking-widest font-mono">
                                                •••• •••• •••• ••••
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
                                        <input type="text" placeholder="First name" id="firstName" name="firstName"
                                            value="{{ old('firstName') }}"
                                            class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500" />
                                        @error('firstName')
                                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                        <input type="text" placeholder="Last name" id="lastName" name="lastName"
                                            value="{{ old('lastName') }}"
                                            class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500" />
                                        @error('lastName')
                                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                        <input type="text" placeholder="Card number" maxlength="19" id="cardNumber"
                                            name="cardNumber" value="{{ old('cardNumber') }}"
                                            class="border border-gray-300 rounded-md py-2 px-3 text-sm md:col-span-2 focus:border-blue-500" />
                                        @error('cardNumber')
                                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                        <input type="text" placeholder="MM/YY" maxlength="5" id="expiry"
                                            name="expiry" value="{{ old('expiry') }}"
                                            class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500" />
                                        @error('expiry')
                                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                        <input type="text" placeholder="CVC" maxlength="4" id="cvc"
                                            name="cvc" value="{{ old('cvc') }}"
                                            class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500" />
                                        @error('cvc')
                                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                        <input type="hidden" name="opaqueDataValue" id="opaqueDataValue" />
                                        <input type="hidden" name="opaqueDataDescriptor" id="opaqueDataDescriptor" />
                                    </div>
                                </div>
                            </div>
                            <!-- Cash On Delivery -->
                            <div class="border-2 rounded-lg p-4 payment-option {{ old('payment', 'COD') == 'COD' ? 'border-blue-500' : '' }}"
                                data-value="COD">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="payment" value="COD"
                                        {{ old('payment', 'COD') == 'COD' ? 'checked' : '' }} />
                                    <span>Cash on delivery (COD)</span>
                                </label>
                            </div>
                            <!-- Add Account -->
                            <div class="border-2 rounded-lg p-4 payment-option {{ old('payment') == 'Account' ? 'border-blue-500' : '' }}"
                                data-value="Account">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="payment" value="Account"
                                        {{ old('payment') == 'Account' ? 'checked' : '' }} />
                                    <span>Add Account</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-between items-center mt-8">
                        <a href="javascript:history.back()"
                            class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                            <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back
                        </a>
                        <button type="submit" id="checkoutBtn"
                            class="bg-yellow-400 hover:bg-yellow-300 text-black text-base font-medium rounded px-8 py-3 transition flex items-center gap-2">
                            <span id="checkoutBtnText">Checkout</span>
                            <span id="checkoutBtnLoader" class="hidden">
                                <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin text-yellow-600" />
                            </span>
                        </button>
                    </div>
                    {{ html()->form()->close() }}
                </div>

                <!-- Cart Summary -->
                <div id="cartSummary" class="w-full md:w-1/2 mt-10 pl-6">
                    <div class="border-b pb-4 mb-6">
                        <h2 class="text-2xl font-bold mb-2">Cart Summary</h2>
                        <p class="text-sm text-gray-600">Review your items before proceeding to checkout.</p>
                    </div>
                    <div class="border-b pb-6">
                        <ul class="flex flex-col">
                            <li class="flex justify-between mb-1">
                                <span class="">Subtotal:</span>
                                <span class=" font-bold">+ $0.00</span>
                            </li>
                            <li class="flex justify-between mb-1">
                                <span class="">Tax</span>
                                <span class=" font-bold">+ $0.00</span>
                            </li>
                            <li class="flex justify-between mb-1">
                                <span class=" font-bold">Total</span>
                                <span class=" font-bold">+ $0.00</span>
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
    @if ($paymentSetting['payment_test_mode'] ?? false)
        <script type="text/javascript" src="https://jstest.authorize.net/v1/Accept.js"></script>
    @else
        <script type="text/javascript" src="https://js.authorize.net/v1/Accept.js"></script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const form = document.getElementById('checkout-form');
            if (!form) return;

            var checkoutBtn = document.getElementById('checkoutBtn');
            var checkoutBtnText = document.getElementById('checkoutBtnText');
            var checkoutBtnLoader = document.getElementById('checkoutBtnLoader');
            form.addEventListener('submit', function(e) {
                // 1. Cart validation
                let cart = window.CartStorage.getCart();
                if (!cart || cart.length === 0) {
                    e.preventDefault();
                    notyf.error('Your cart is empty. Please add items before checking out.');
                    return;
                }
                document.getElementById('cart-input').value = JSON.stringify(cart);

                // 2. Only process credit card fields if "Card" payment is selected
                const paymentType = document.querySelector('input[name="payment"]:checked');
                if (paymentType && paymentType.value === 'Card') {
                    e.preventDefault(); // Pause form submit until Accept.js finishes
                    // Disable button and show loader
                    if (checkoutBtn) {
                        checkoutBtn.disabled = true;
                        checkoutBtnText.classList.add('hidden');
                        checkoutBtnLoader.classList.remove('hidden');
                    }

                    // Card fields
                    const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                    const expiry = document.getElementById('expiry').value.trim();
                    const cvc = document.getElementById('cvc').value.trim();

                    // Basic validation
                    function luhnCheck(num) {
                        let arr = (num + '').split('').reverse().map(x => parseInt(x));
                        let sum = arr.reduce((acc, val, idx) => {
                            if (idx % 2) {
                                val *= 2;
                                if (val > 9) val -= 9;
                            }
                            return acc + val;
                        }, 0);
                        return sum % 10 === 0;
                    }

                    if (!/^\d{13,19}$/.test(cardNumber) || !luhnCheck(cardNumber)) {
                        notyf.error('Invalid card number.');
                        if (checkoutBtn) {
                            checkoutBtn.disabled = false;
                            checkoutBtnText.classList.remove('hidden');
                            checkoutBtnLoader.classList.add('hidden');
                        }
                        return false;
                    }

                    if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                        notyf.error('Invalid expiry date. Use MM/YY.');
                        if (checkoutBtn) {
                            checkoutBtn.disabled = false;
                            checkoutBtnText.classList.remove('hidden');
                            checkoutBtnLoader.classList.add('hidden');
                        }
                        return false;
                    }
                    const [mm, yy] = expiry.split('/');
                    const now = new Date();
                    const expiryYear = 2000 + parseInt(yy, 10);
                    const expiryMonth = parseInt(mm, 10);

                    if (
                        expiryMonth < 1 || expiryMonth > 12 ||
                        expiryYear < now.getFullYear() ||
                        (expiryYear === now.getFullYear() && expiryMonth < (now.getMonth() + 1))
                    ) {
                        notyf.error('Card expiry is in the past.');
                        if (checkoutBtn) {
                            checkoutBtn.disabled = false;
                            checkoutBtnText.classList.remove('hidden');
                            checkoutBtnLoader.classList.add('hidden');
                        }
                        return false;
                    }

                    if (!/^\d{3,4}$/.test(cvc)) {
                        notyf.error('Invalid CVC code.');
                        if (checkoutBtn) {
                            checkoutBtn.disabled = false;
                            checkoutBtnText.classList.remove('hidden');
                            checkoutBtnLoader.classList.add('hidden');
                        }
                        return false;
                    }

                    // 3. If validation passes, use Accept.js to tokenize the card
                    const [expMonth, expYearShort] = expiry.split('/');
                    const expYear = '20' + expYearShort;

                    // Fill in your actual config values here (best: pass from Blade using Laravel config)
                    const authData = {
                        clientKey: '{{ $paymentSetting['payment_api_public_key'] ?? '' }}',
                        apiLoginID: '{{ $paymentSetting['payment_api_key'] ?? '' }}'
                    };
                    const cardData = {
                        cardNumber: cardNumber,
                        month: expMonth,
                        year: expYear,
                        cardCode: cvc
                    };
                    const secureData = {
                        authData: authData,
                        cardData: cardData
                    };

                    Accept.dispatchData(secureData, function(response) {
                        if (response.messages.resultCode === "Error") {
                            let errorMsg = response.messages.message.map(m => m.text).join(', ');
                            notyf.error('Card Error: ' + errorMsg);
                            if (checkoutBtn) {
                                checkoutBtn.disabled = false;
                                checkoutBtnText.classList.remove('hidden');
                                checkoutBtnLoader.classList.add('hidden');
                            }
                        } else {
                            document.getElementById('opaqueDataValue').value = response.opaqueData
                                .dataValue;
                            document.getElementById('opaqueDataDescriptor').value = response
                                .opaqueData.dataDescriptor;
                            form.submit(); // Now actually submit the form
                        }
                    });

                    // Don't submit until Accept.js finishes
                    return false;
                } else {
                    // If not "Card", just continue normal submit
                    if (checkoutBtn) {
                        checkoutBtn.disabled = true;
                        checkoutBtnText.classList.add('hidden');
                        checkoutBtnLoader.classList.remove('hidden');
                    }
                }
            });

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
            const hiddenSameAsBilling = document.querySelector('input[name="sameAsBilling"][type="hidden"]');
            const deliveryDiv = document.getElementById('deliveryDiv');

            // Map billing fields to delivery fields (exclude deliveryEmail)
            const fields = [
                ['billingFirstName', 'deliveryFirstName'],
                ['billingLastName', 'deliveryLastName'],
                // ['billingEmail', 'deliveryEmail'], // deliveryEmail should always match billingEmail, not editable
                ['billingPhone', 'deliveryPhone'],
                ['billingAddress', 'deliveryAddress'],
                ['billingState', 'deliveryState'],
                ['billingCity', 'deliveryCity'],
                ['billingZip', 'deliveryZip'],
            ];

            // Always keep deliveryEmail in sync with billingEmail
            function syncDeliveryEmail() {
                const billing = document.getElementById('billingEmail');
                const delivery = document.getElementById('deliveryEmail');
                if (billing && delivery) {
                    delivery.value = billing.value;
                }
            }

            // Define the sync function
            function syncDeliveryFields() {
                fields.forEach(([billingId, deliveryId]) => {
                    const billing = document.getElementById(billingId);
                    const delivery = document.getElementById(deliveryId);
                    if (billing && delivery) {
                        delivery.value = billing.value;
                    }
                });
                syncDeliveryEmail();
            }

            // Handler references for easy removal
            function addBillingListeners() {
                fields.forEach(([billingId, _]) => {
                    const billing = document.getElementById(billingId);
                    if (billing) {
                        billing.addEventListener('input', syncDeliveryFields);
                        billing.addEventListener('change', syncDeliveryFields);
                    }
                });
                // Always sync deliveryEmail with billingEmail
                const billingEmail = document.getElementById('billingEmail');
                if (billingEmail) {
                    billingEmail.addEventListener('input', syncDeliveryEmail);
                    billingEmail.addEventListener('change', syncDeliveryEmail);
                }
            }

            function removeBillingListeners() {
                fields.forEach(([billingId, _]) => {
                    const billing = document.getElementById(billingId);
                    if (billing) {
                        billing.removeEventListener('input', syncDeliveryFields);
                        billing.removeEventListener('change', syncDeliveryFields);
                    }
                });
                // Remove deliveryEmail sync
                const billingEmail = document.getElementById('billingEmail');
                if (billingEmail) {
                    billingEmail.removeEventListener('input', syncDeliveryEmail);
                    billingEmail.removeEventListener('change', syncDeliveryEmail);
                }
            }

            sameAsBilling.addEventListener('change', function() {
                if (this.checked) {
                    hiddenSameAsBilling.value = 'Yes';
                    syncDeliveryFields(); // Sync immediately
                    addBillingListeners(); // Start syncing on billing field changes
                    deliveryDiv.classList.add('hidden'); // Hide delivery section
                } else {
                    hiddenSameAsBilling.value = 'No';
                    removeBillingListeners(); // Stop syncing
                    deliveryDiv.classList.remove('hidden'); // Show delivery section
                    // Prefill delivery fields with billing values when showing delivery section
                    syncDeliveryFields();
                    // Still keep deliveryEmail in sync and readonly
                    syncDeliveryEmail();
                }
            });

            // On page load, always sync deliveryEmail and keep it readonly/disabled
            syncDeliveryEmail();
            const deliveryEmail = document.getElementById('deliveryEmail');
            if (deliveryEmail) {
                deliveryEmail.readOnly = true;
                // deliveryEmail.disabled = true;
            }

            // Helper to fill billing fields from address object
            function fillBillingFields(addr) {
                if (!addr) return;
                if (document.getElementById('billingFirstName')) document.getElementById('billingFirstName').value =
                    addr.first_name || '';
                if (document.getElementById('billingLastName')) document.getElementById('billingLastName').value =
                    addr.last_name || '';
                if (document.getElementById('billingCompany')) document.getElementById('billingCompany').value =
                    addr.company || '';
                // if (document.getElementById('billingEmail')) document.getElementById('billingEmail').value = addr.email || '';
                if (document.getElementById('billingPhone')) document.getElementById('billingPhone').value = addr
                    .phone || '';
                if (document.getElementById('billingAddress')) document.getElementById('billingAddress').value =
                    addr.address || '';
                if (document.getElementById('billingState')) document.getElementById('billingState').value = addr
                    .state_id || '';
                if (document.getElementById('billingCity')) document.getElementById('billingCity').value = addr
                    .city || '';
                if (document.getElementById('billingZip')) document.getElementById('billingZip').value = addr
                    .zip_code || '';
            }

            function updateAddressPreview() {
                const sel = document.getElementById('selAddress');
                const preview = document.getElementById('selectedAddressPreview');
                const billingDiv = document.getElementById('billingDiv');
                const selectedOption = sel ? sel.options[sel.selectedIndex] : null;


                if (selectedOption) {
                    if (selectedOption.value === 'new') {
                        // Show billingDiv, hide preview, clear billing fields
                        billingDiv.classList.remove('hidden');
                        preview.classList.add('hidden');
                        fillBillingFields({});
                        return;
                    }
                    if (selectedOption.dataset.addressitem) {
                        const addr = JSON.parse(selectedOption.dataset.addressitem);
                        document.getElementById('addressName').textContent = (addr.full_name || '');
                        document.getElementById('addressFull').textContent = [
                            addr.addressItem,
                            addr.city,
                            addr.state_name,
                            addr.zip_code
                        ].filter(Boolean).join(', ');
                        document.getElementById('addressPhone').textContent = 'Phone: ' + (addr.phone || '');
                        document.getElementById('addressEmail').textContent = 'Email: ' + (addr.email || '');
                        document.getElementById('addressDefault').textContent = addr.is_primary ? 'Default' : '';
                        preview.classList.remove('hidden');
                        billingDiv.classList.add('hidden');
                        fillBillingFields(addr);
                        return;
                    }
                }
                // Default: hide preview, show billingDiv, clear billing fields
                preview.classList.add('hidden');
                billingDiv.classList.remove('hidden');
                fillBillingFields({});
            }

            const sel = document.getElementById('selAddress');
            if (sel) {
                sel.addEventListener('change', updateAddressPreview);
            }


            // ============================
            // Custom Password Show/Hide
            // ============================
            const showPassword = document.getElementById('showPassword');
            const passwordFields = document.getElementById('passwordFields');

            if (showPassword && passwordFields) {
                function togglePasswordFields() {
                    passwordFields.style.display = showPassword.checked ? '' : 'none';
                }

                showPassword.addEventListener('change', togglePasswordFields);
                // Set initial state
                togglePasswordFields();
            }

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
