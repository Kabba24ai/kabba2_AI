@extends('front.layouts.app')

@section('title', $title)

@push('css')
    <style>
        .license-file-preview {
            transition: opacity 280ms ease, filter 280ms ease, transform 280ms ease;
        }

        .license-file-preview.is-inactive {
            opacity: 0.45;
            filter: grayscale(100%);
            transform: scale(0.99);
        }
    </style>
@endpush

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
    <section class="lg:pb-[50px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="flex flex-col md:flex-row gap-2">
                <div class="w-full md:w-1/2 md:border-r px-0 lg:px-4 md:pr-6 pb-12">
                    {{ html()->form()->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-4',
                            'id' => 'checkout-form',
                        ])->open() }}
                    @csrf

                    <!-- Billing Info -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mt-2 mb-4 gap-3">
                        <h2 class="text-2xl font-bold m-0">Billing information</h2>
                        @guest('customer')
                            <div class="text-sm text-gray-600 flex items-center gap-x-2">
                                <a href="{{ route('front.auth.register.index') }}" class="text-blue-600 hover:underline">Create Account</a>
                                <span>|</span>
                                <a id="loginWithEmailLink" href="{{ route('front.auth.login.index') }}" class="text-blue-600 hover:underline">Login To Account</a>
                            </div>
                        @endguest
                    </div>

                    <input type="hidden" name="cart" id="cart-input">
                    @error('cart')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                    <!-- Address Selection -->
                    @auth('customer')
                        @php
                            // $addresses =
                            //     auth('customer')->user()->addresses()->where('type', 'Billing')->get() ?? collect();
                            // $primaryAddress = $addresses->firstWhere('is_primary', true);
                            $customer = auth('customer')->user();

                            $primaryAddress = $customer->billingAddress;
                            $companyName = $customer->company_name ?? null;
                            $companyWebsite = $customer->company_website ?? null;
                            $firstName = $customer->first_name ?? null;
                            $lastName = $customer->last_name ?? null;
                            $email = $customer->email ?? null;
                            $phone = $customer->phone ?? null;
                            $licenseExpiryDate = $customer->license_expiry_date;
                            $isValidLicense = !empty($licenseExpiryDate) && \Carbon\Carbon::parse($licenseExpiryDate)->endOfDay()->gte(now());

                            $customerLicenseFrontUrl = $isValidLicense ? optional($customer->licenseFront)->url : null;
                            $customerLicenseBackUrl  = $isValidLicense ? optional($customer->licenseBack)->url : null;
                            $hasCustomerLicenseOnFile = !empty($customerLicenseFrontUrl) || !empty($customerLicenseBackUrl);
                        @endphp
                        <div>
                            {{-- <label for="selAddress" class="block text-sm font-medium text-gray-800 mt-3">
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
                            </select> --}}
                            <!-- Selected Address Preview -->
                            <div id="selectedAddressPreview"
                                class="border-2 border-dashed border-green-600 rounded-md p-4 mt-4 relative capitalize bg-green-50/10 {{ $primaryAddress ? '' : 'hidden' }}">
                                <h5 class="text-base font-bold" id="addressName">
                                    {{ $primaryAddress ? $primaryAddress->full_name : '' }}
                                </h5>
                                <p id="addressCompany">
                                    {{ $primaryAddress ? 'Company: ' . $companyName : '' }}
                                </p>
                                <p id="addressFull">
                                    {{ $primaryAddress ? $primaryAddress->full_address : '' }}
                                </p>
                                <p class="text-sm" id="addressPhone">
                                    {{ $primaryAddress ? 'Phone: ' . $primaryAddress->phone : '' }}
                                </p>
                                <p class="mt-2 text-sm" id="addressEmail">
                                    {{ $primaryAddress ? 'Email: ' . ($primaryAddress->email ?? $email) : '' }}
                                </p>
                                <span class="text-green-600 font-medium text-xs absolute top-2 right-3" id="addressDefault">
                                    {{ $primaryAddress && $primaryAddress->is_primary ? 'Default' : '' }}
                                </span>
                            </div>

                            @if ($hasCustomerLicenseOnFile)
                                <div id="licenseOnFileSection" class="mt-4 license-file-preview">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div
                                            class="aspect-[16/10] rounded-md border-2 border-gray-400 overflow-hidden bg-white flex items-center justify-center">
                                            @if (!empty($customerLicenseFrontUrl))
                                                <img src="{{ $customerLicenseFrontUrl }}" alt="License front"
                                                    class="h-full w-full object-cover object-center" />
                                            @else
                                                <span class="text-xs text-gray-500">Front image not available</span>
                                            @endif
                                        </div>
                                        <div
                                            class="aspect-[16/10] rounded-md border-2 border-gray-400 overflow-hidden bg-white flex items-center justify-center">
                                            @if (!empty($customerLicenseBackUrl))
                                                <img src="{{ $customerLicenseBackUrl }}" alt="License back"
                                                    class="h-full w-full object-cover object-center" />
                                            @else
                                                <span class="text-xs text-gray-500">Back image not available</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-3 flex items-center gap-2">
                                        <input type="hidden" name="auto_inject" value="0">
                                        <input id="auto_inject" name="auto_inject" type="checkbox" value="1"
                                            class="accent-blue-500 h-4 w-4"
                                            {{ old('auto_inject', '1') == '1' ? 'checked' : '' }}>
                                        <label for="auto_inject" class="text-sm text-grey-600">Use License On File</label>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        @php
                            $primaryAddress = null;
                            $companyName = null;
                            $companyWebsite = null;
                            $firstName = null;
                            $lastName = null;
                            $phone = null;
                        @endphp
                    @endauth
                    <!-- Tax Exempt -->

                    <div id="billingDiv" class="space-y-4 {{ $primaryAddress ? 'hidden' : '' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 ">
                            <div>
                                <label for="billingFirstName" class="block text-sm text-gray-600 mb-1">First Name</label>
                                {{ html()->text('billingFirstName', old('billingFirstName', $primaryAddress ? $primaryAddress->first_name : $firstName))->class([
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
                                {{ html()->text('billingLastName', old('billingLastName', $primaryAddress ? $primaryAddress->last_name : $lastName))->class([
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <label for="billingCompany" class="block text-sm text-gray-600 mb-1">Company Name</label>
                                {{ html()->text('billingCompany', old('billingCompany', $companyName ? $companyName : null))->class(
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
                            @if (!$primaryAddress)
                                <div>
                                    <label for="po_id" class="block text-sm text-gray-600 mb-1">PO#</label>
                                    {{ html()->text('po_id', old('po_id'))->class(
                                            'w-full rounded-lg border border-gray-300 px-4 py-2 shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                                        )->attributes([
                                            'maxlength' => 255,
                                            'data-parsley-maxlength' => 255,
                                            'placeholder' => 'PO Number',
                                            'autocomplete' => 'off',
                                            'id' => 'po_id',
                                        ]) }}
                                    @error('po_id')
                                        <p class="mt-1 text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
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
                                <p id="billingEmailError" class="hidden mt-1 text-xs text-red-600"></p>
                                @error('billingEmail')
                                    <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="billingPhone" class="block text-sm text-gray-600 mb-1">Phone</label>
                                {{ html()->text('billingPhone', old('billingPhone', $primaryAddress ? $primaryAddress->phone : $phone))->class(
                                        'masked-phone w-full rounded-lg border border-gray-300 px-4 py-2  shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                                    )->attributes([
                                        'maxlength' => 240,
                                        'placeholder' => '(xxx) xxx-xxxx',
                                        'autocomplete' => 'off',
                                        'id' => 'billingPhone',
                                    ])->required() }}
                                @error('billingPhone')
                                    <p class="mt-1  text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-2">
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
                                    <option value="">Select State...</option>
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
                                <label for="billingZip" class="block text-sm text-gray-600 mb-1">Zip Code</label>
                                {{ html()->number('billingZip', old('billingZip', $primaryAddress ? $primaryAddress->zip_code : null))->class(
                                        'w-full rounded-lg border border-gray-300 px-4 py-2  shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                                    )->attributes([
                                        'maxlength' => 8,
                                        'placeholder' => 'Zip code',
                                        'autocomplete' => 'off',
                                        'data-parsley-type' => 'number',
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

                    @if ($primaryAddress)
                        <div >
                            <label for="po_id" class="block text-sm text-gray-600 mb-1">PO#</label>
                            {{ html()->text('po_id', old('po_id'))->class(
                                    'w-full sm:w-56 rounded-lg border border-gray-300 px-4 py-2 shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                                )->attributes([
                                    'maxlength' => 255,
                                    'data-parsley-maxlength' => 255,
                                    'placeholder' => 'PO Number',
                                    'autocomplete' => 'off',
                                ]) }}
                            @error('po_id')
                                <p class="mt-1 text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

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
                                        'placeholder' => '(xxx) xxx-xxxx',
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
                                    <option value="">Select State...</option>
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
                                {{ html()->number('deliveryZip')->class(
                                        'w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none' .
                                            ($errors->has('deliveryZip') ? ' border-red-400' : ''),
                                    )->attributes([
                                        'placeholder' => 'Zip Code',
                                        'autocomplete' => 'off',
                                        'data-parsley-type' => 'number',
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

                    @php
                    $customer = auth('customer')->user();
                @endphp
                    @php
                        $account = $customer
                            ? \App\Helpers\CustomHelper::getCustomerAccountStatus($customer)
                            : null;

                        $isSuspended = $customer && in_array($customer->status, ['Archived', 'Suspended']);

                        $isBadDebt = $customer && ($account['badge']['label'] ?? '') === 'Bad Debt';

                        $isImpersonating = session('impersonated_by_admin') && auth('customer')->check();

                        $canCheckout = $customer && ((!$isSuspended && !$isBadDebt) || $isImpersonating);
                    @endphp



                    @if((!$isSuspended && !$isBadDebt) || $isImpersonating)
                    <!-- Payment Method -->
                    <div class="mx-auto" id="paymentForm">
                        <h4 class="text-lg font-semibold mb-3">Payment method</h4>
                        <div class="space-y-4">

                            <!-- Credit/Debit -->
                            <div id="cardPaymentOption"
                                class="border-2 rounded-lg p-4 payment-option {{ old('payment', 'Card') == 'Card' ? 'border-blue-500' : '' }}"
                                data-value="Card">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="payment" value="Card"
                                        {{ old('payment', 'Card') == 'Card' ? 'checked' : '' }} />
                                    <span>Credit or Debit</span>
                                </label>

                                <div id="cardSection"
                                    class="{{ old('payment', 'Card') == 'Card' ? 'block' : 'hidden' }}">
                                    @if (session()->has('impersonated_by_admin'))
                                        <select name="customer_card" id="customer_card"
                                            class="w-full max-w-sm rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none{{ $errors->has('card') ? ' border-red-400' : '' }}">
                                            <option value="">New Card</option>
                                            @foreach (auth('customer')->user()->cards as $card)
                                                <option value="{{ $card->unique_id }}">{{ $card->card_number }}</option>
                                            @endforeach
                                        </select>
                                        @error('customer_card')
                                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    @endif
                                    <!-- Card Visual -->
                                    <div class="relative h-card-height lg:h-52 w-full max-w-sm  mt-4 card-container">
                                        <!-- Card Front -->
                                        <div id="cardFront"
                                            class="absolute w-full h-full rounded-xl p-5 bg-gradient-to-r from-gray-300 to-gray-300 text-white shadow-lg transition-all duration-300 card-face card-front"
                                            style="{{ old('payment', 'Card') == 'Card' ? '' : 'display:block' }}">
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
                                    <div class="grid md:grid-cols-2 gap-4 mt-4 max-w-sm">
                                        <div class="md:col-span-1">
                                            <input type="text" placeholder="First name" id="firstName"
                                                name="firstName" value="{{ old('firstName') }}"
                                                class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500 w-full" />
                                            @error('firstName')
                                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="md:col-span-1">
                                            <input type="text" placeholder="Last name" id="lastName" name="lastName"
                                                value="{{ old('lastName') }}"
                                                class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500 w-full" />
                                            @error('lastName')
                                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" placeholder="Card number" maxlength="19"
                                                id="cardNumber" name="cardNumber" value="{{ old('cardNumber') }}"
                                                class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500 w-full" />
                                            @error('cardNumber')
                                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="md:col-span-1">
                                            <input type="text" placeholder="MM/YY" maxlength="5" id="expiry"
                                                name="expiry" value="{{ old('expiry') }}"
                                                class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500 w-full" />
                                            @error('expiry')
                                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="md:col-span-1">
                                            <input type="text" placeholder="CVC" maxlength="4" id="cvc"
                                                name="cvc" value="{{ old('cvc') }}"
                                                class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:border-blue-500 w-full" />
                                            @error('cvc')
                                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <input type="hidden" name="opaqueDataValue" id="opaqueDataValue" />
                                        <input type="hidden" name="opaqueDataDescriptor" id="opaqueDataDescriptor" />
                                    </div>
                                </div>
                            </div>
                            <!-- Pay On Delivery -->
                            <div class="border-2 rounded-lg p-4 payment-option {{ old('payment') == 'COD' ? 'border-blue-500' : '' }}"
                                data-value="COD">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="payment" value="COD"
                                        {{ old('payment') == 'COD' ? 'checked' : '' }} />
                                    <span>Pay on Delivery (POD)</span>
                                </label>
                                <p id="codNote"
                                    class="{{ old('payment') == 'COD' ? 'block' : 'hidden' }} text-sm text-red-600 mt-2"
                                    data-default-message="POD Orders are not reserved / locked in until paid. If you want to lock in your order, please pay using a credit card or call sales."
                                    data-hide-cc-message="POD Orders are not reserved / locked in until paid. Please call sales to lock in your order.">
                                    POD Orders are not reserved / locked in until paid. If you want to lock in your order,
                                    please pay using a credit card or call sales.
                                </p>
                            </div>
                            <!-- Add Account -->

                            @php
                                $customer = auth('customer')->user();
                            @endphp
                            @if ($customer && $customer->credit_limit > 0 && $customer->is_credit_account == 1)
                                @php
                                    $days = $customer->days_since_last_payment;
                                    $badge = $customer->payment_status_badge;
                                @endphp

                                @if ($badge === 'safe')
                                    {{-- Green (1–30) --}}
                                    <div class="flex items-center justify-between border-2 rounded-lg p-4 payment-option "
                                        data-value="Account">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="payment" value="Account"
                                                {{ old('payment') == 'Account' ? 'checked' : '' }} />
                                            <span>Add to Account</span>
                                            <p>*Account is Current & in good standing!</p>

                                        </label>
                                        <div class="bg-green-500 p-2 rounded-full inline-flex items-center justify-center">
                                            <x-heroicon-o-currency-dollar class="w-6 h-6 text-white" />
                                        </div>
                                    </div>
                                @elseif($badge === 'warning')
                                    {{-- Dark Yellow (31–45) --}}
                                    <div class="flex items-center justify-between border-2 rounded-lg p-4 payment-option "
                                        data-value="Account">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="payment" value="Account"
                                                {{ old('payment') == 'Account' ? 'checked' : '' }} />
                                            <span>Add to Account</span>
                                            <p>*Account has payments due - Management Approval Required</p>
                                        </label>
                                        <div
                                            class="bg-yellow-700 p-2 rounded-full inline-flex items-center justify-center">
                                            <x-heroicon-o-currency-dollar class="w-6 h-6 text-white" />
                                        </div>
                                    </div>
                                @elseif($badge === 'danger')
                                    {{-- Pink (46+) --}}
                                    <div class="flex items-center justify-between border-2 rounded-lg p-4 payment-option "
                                        data-value="Account">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="payment" value="Account"
                                                {{ old('payment') == 'Account' ? 'checked' : '' }} />
                                            <span>Add to Account</span>
                                            <p>*Account is Past Due • Management Approval Required</p>
                                        </label>
                                        <div class="bg-pink-500 p-2 rounded-full inline-flex items-center justify-center">
                                            <x-heroicon-o-currency-dollar class="w-6 h-6 text-white" />
                                        </div>
                                    </div>
                                @elseif($badge === 'no-payment')
                                    <div class="flex items-center justify-between border-2 rounded-lg p-4 payment-option "
                                        data-value="Account">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="payment" value="Account"
                                                {{ old('payment') == 'Account' ? 'checked' : '' }} />
                                            <span>Add to Account</span>
                                            <p>*Account is Current & in good standing!</p>
                                        </label>
                                        <div class="bg-green-500 p-2 rounded-full inline-flex items-center justify-center">
                                            <x-heroicon-o-currency-dollar class="w-6 h-6 text-white" />
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                    @endif

                     @if($isSuspended || $isBadDebt)
                        <div class="mb-4 p-4 rounded-lg border border-red-200 bg-red-50 text-red-700">


                            @if($isBadDebt)
                                <div class="flex items-start gap-3 mt-2">
                                     <x-heroicon-o-currency-dollar
            class="w-5 h-5 mt-0.5 text-red-600 shrink-0"
        />

                                    <div>
                                        <p class="font-semibold">Your account has an outstanding balance</p>
                                        <p class="text-sm mt-1">
                                            We noticed there is a pending balance on your account. Please clear the outstanding amount before placing a new order.
                                        </p>
                                         <p class="text-sm mt-2 text-red-600">
                                            If you believe this message was shown in error, please contact your administrator or support team for assistance.
                                        </p>
                                    </div>
                                </div>
                            @endif

                        </div>
                    @endif

                    <!-- Buttons -->
                    <div class="mt-8 flex flex-col gap-3">
                        {{-- Back | Employee Code + Checkout --}}
                        <div class="flex justify-between items-start">
                            <a href="javascript:history.back()"
                                class="text-sm text-blue-600 hover:underline flex items-center gap-1 mt-3">
                                <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Back
                            </a>
                            @if((!$isSuspended && !$isBadDebt) || $isImpersonating)
                            <div class="flex items-start gap-4">
                                <div>
                                    <label for="employee_code" class="sr-only">Employee Code</label>
                                    {{ html()->text('employee_code', old('employee_code'))->class([
                                            'w-36 rounded-lg border border-gray-300 px-3 py-2.5 shadow-sm text-sm',
                                            'input-error' => $errors->has('employee_code'),
                                        ])->attributes([
                                            'maxlength' => 20,
                                            'data-parsley-maxlength' => 20,
                                            'placeholder' => 'Employee Code',
                                            'autocomplete' => 'off',
                                            'id' => 'employee_code',
                                        ]) }}
                                    @error('employee_code')
                                        <p class="mt-1 text-red-600 text-xs">{{ $message }}</p>
                                    @enderror
                                    {{-- Tax Exempt below employee code --}}
                                    <div class="flex items-center justify-center gap-2 mt-1">
                                        <input type="hidden" name="tax_exempt" value="0" />
                                        <input id="taxExempt" name="tax_exempt" type="checkbox" value="1"
                                            class="accent-blue-500 h-4 w-4 align-middle"
                                            {{ old('tax_exempt', session('tax_exempt', false)) ? 'checked' : '' }} />
                                        <label for="taxExempt" class="text-sm align-middle">Tax Exempt</label>
                                    </div>
                                </div>
                                <button type="submit" id="checkoutBtn"
                                    class="bg-yellow-400 hover:bg-yellow-300 text-black text-base font-medium rounded px-8 py-3 transition flex items-center gap-2">
                                    <span id="checkoutBtnText">Checkout</span>
                                    <span id="checkoutBtnLoader" class="hidden">
                                        <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin text-yellow-600" />
                                    </span>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                    {{ html()->form()->close() }}
                </div>

                <!-- Cart Summary -->
                <div class="w-full md:w-1/2 mt:0 lg:mt-10 pl-0 lg:pl-6">
                    <!-- Dynamic Cart Summary (Replaced by AJAX) -->
                    <div id="cartSummary">
                        <div class="border-b pb-4 mb-6">
                            <h2 class="text-2xl font-bold mb-2">Cart Summary</h2>
                            <p class="text-sm text-gray-600">Review your items before proceeding to checkout.</p>
                        </div>
                        <div class="border-b pb-6">
                            <ul class="flex flex-col">
                                <li class="flex justify-between mb-1">
                                    <span class="">Subtotal:</span>
                                    <span class=" font-bold">$0.00</span>
                                </li>
                                <li class="flex justify-between mb-1">
                                    <span class="">Tax</span>
                                    <span class=" font-bold">$0.00</span>
                                </li>
                                <li class="flex justify-between mb-1">
                                    <span class=" font-bold">Total</span>
                                    <span class=" font-bold">$0.00</span>
                                </li>
                            </ul>
                        </div>
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
            {{ html()->form()->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-4',
                    'id' => 'taxForm',
                ])->open() }}
            @csrf
            <div class="p-6 py-4">
                <span class="font-medium">Note: </span>
                <p class="text-gray-700 mb-4 inline text-sm italic">Tax Exempt sales must be pre-approved with
                    documentation on file prior to placing the order. Call if you need assistance with placing an order with
                    Tax Exempt status.</p>
                <input type="password" name="admin_code" id="admin_code"
                    class="form-input mt-1 block w-full px-4 py-2 rounded-md border border-gray-300 shadow-sm"
                    placeholder="Admin Code" required data-parsley-type="digits" data-parsley-minlength="6"
                    data-parsley-required-message="Please enter the admin code"
                    data-parsley-type-message="Admin code must be numeric"
                    data-parsley-minlength-message="Admin code must be at least 6 digits" />

            </div>

            <div class="border-t p-6 py-4">
                <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-end gap-3">
                    <button type="button" onclick="cancelModal()"
                        class="bg-gray-600 hover:bg-gray-700 text-white text-base font-medium rounded px-8 py-3 ">Cancel</button>
                    <button type="submit" id="taxSubmitBtn"
                        class="bg-yellow-400 hover:bg-yellow-300 text-black text-base font-medium rounded px-8 py-3">
                        <span id="taxSubmitText">Submit</span>
                        <span id="taxSubmitLoader" class="hidden">
                            <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin text-yellow-600" />
                        </span>
                    </button>
                </div>
            </div>
            {{ html()->form()->close() }}
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

            const checkoutBtn = document.getElementById('checkoutBtn');
            const checkoutBtnText = document.getElementById('checkoutBtnText');
            const checkoutBtnLoader = document.getElementById('checkoutBtnLoader');
            const autoInjectCheckbox = document.getElementById('auto_inject');
            const licenseOnFileSection = document.getElementById('licenseOnFileSection');

            function syncLicenseOnFilePreviewState() {
                if (!autoInjectCheckbox || !licenseOnFileSection) return;
                licenseOnFileSection.classList.toggle('is-inactive', !autoInjectCheckbox.checked);
            }

            if (autoInjectCheckbox && licenseOnFileSection) {
                autoInjectCheckbox.addEventListener('change', syncLicenseOnFilePreviewState);
                syncLicenseOnFilePreviewState();
            }

            function disableCheckoutButton() {
                if (!checkoutBtn) return;
                checkoutBtn.disabled = true;
                checkoutBtnText.classList.add('hidden');
                checkoutBtnLoader.classList.remove('hidden');
            }

            function enableCheckoutButton() {
                if (!checkoutBtn) return;
                checkoutBtn.disabled = false;
                checkoutBtnText.classList.remove('hidden');
                checkoutBtnLoader.classList.add('hidden');
            }

            function getFormDataAsObject(form) {
                const formData = new FormData(form);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                return data;
            }

            function submitCheckout(data) {
                const url = window.location.href; // Post to same URL
                apiFetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest' // To indicate AJAX
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        if (response.success) {
                            window.location.replace(response.redirect_url);
                        } else {
                            notyf.error(response.message || 'An error occurred during checkout.');
                            enableCheckoutButton();
                        }
                    })
                    .catch(() => {
                        enableCheckoutButton();
                    });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Always prevent default submission

                const employeeCode = document.getElementById('employee_code').value.trim();
                const isRequired =
                    '{{ session()->has('impersonated_by_admin') || session()->has('master_passcode') }}';
                if (isRequired && !employeeCode) {
                    notyf.error('Employee code is required.');
                    return;
                }

                const parsleyForm = $(form).parsley();

                const isFormValid = parsleyForm.validate({
                    force: true
                });
                if (!isFormValid) {
                    // Use custom helper method
                    const parsleyErrors = Parsley.getHiddenFieldErrors(parsleyForm);
                    if (parsleyErrors.length > 0) {
                        parsleyErrors.forEach(error => notyf.error(error));
                    }
                    return;
                }

                // 1. Cart validation
                let cart = window.CartStorage.getCart();
                if (!cart || cart.length === 0) {
                    notyf.error('Your cart is empty. Please add items before checking out.');
                    return;
                }
                document.getElementById('cart-input').value = JSON.stringify(cart);

                disableCheckoutButton();

                // 2. Only process credit card fields if "Card" payment is selected
                const paymentType = document.querySelector('input[name="payment"]:checked');
                if (paymentType && paymentType.value === 'Card') {

                    // Get impersonation + saved card info (adapt selectors to your form)
                    const impersonatedByAdmin = '{{ session('impersonated_by_admin') }}';
                    const customerCard = document.getElementById('customer_card')?.value?.trim();

                    // If impersonated and using a saved card → no need to tokenize new card
                    if (impersonatedByAdmin && customerCard) {
                        // Just submit directly via API
                        const data = getFormDataAsObject(form);
                        submitCheckout(data);
                        return;
                    }

                    try {

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
                            throw new Error('Invalid or missing card number.');
                        }

                        if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                            throw new Error('Invalid or missing expiry date. Use MM/YY.');
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
                            throw new Error('Card expiry is in the past.');
                        }

                        if (!/^\d{3,4}$/.test(cvc)) {
                            throw new Error('Invalid or missing CVC code.');
                        }

                        // 3. If validation passes, use Accept.js to tokenize the card
                        const [expMonth, expYearShort] = expiry.split('/');
                        const expYear = '20' + expYearShort;

                        // Fill in your actual config values here (best: pass from Blade using Laravel config)
                        const authData = {
                            clientKey: "{{ Crypt::decryptString($paymentSetting['payment_api_public_key']) ?? '' }}",
                            apiLoginID: "{{ Crypt::decryptString($paymentSetting['payment_api_key']) ?? '' }}"
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
                                let errorMsg = response.messages.message.map(m => m.text).join(
                                ', ');
                                notyf.error('Card Error: ' + errorMsg);
                                enableCheckoutButton();
                            } else {
                                document.getElementById('opaqueDataValue').value = response
                                    .opaqueData.dataValue;
                                document.getElementById('opaqueDataDescriptor').value = response
                                    .opaqueData.dataDescriptor;
                                const data = getFormDataAsObject(form);
                                submitCheckout(data);
                            }
                        });
                    } catch (error) {
                        notyf.error(error.message);
                        enableCheckoutButton();
                    }
                } else {
                    // For non-card payments: submit via API
                    const data = getFormDataAsObject(form);
                    submitCheckout(data);
                }
            });

            // ================================
            // Payment Method Highlight & Toggle
            // ================================

            const paymentOptions = document.querySelectorAll('.payment-option');
            const radioButtons = document.querySelectorAll('input[name="payment"]');
            const cardPaymentOption = document.getElementById('cardPaymentOption');
            const cardSection = document.getElementById('cardSection');
            const codNote = document.getElementById('codNote');
            let hideCcPaymentOption = false;

            function updateHighlight() {
                paymentOptions.forEach(opt => {
                    opt.classList.remove('border-blue-500');
                    opt.classList.add('border-gray-200');
                });
                const checkedRadio = document.querySelector('input[name="payment"]:checked');
                if (!checkedRadio) {
                    cardSection.style.display = 'none';
                    codNote.style.display = 'none';
                    return;
                }
                const selected = checkedRadio.value;

                const selectedOption = document.querySelector(`.payment-option[data-value="${selected}"]`);
                if (selectedOption) {
                    selectedOption.classList.add('border-blue-500');
                }
                // Show/hide card input section
                cardSection.style.display = !hideCcPaymentOption && selected === 'Card' ? 'block' : 'none';
                codNote.style.display = selected === 'COD' ? 'block' : 'none';

            }

            function applyPaymentRestrictions(shouldHideCcPaymentOption) {
                hideCcPaymentOption = shouldHideCcPaymentOption;

                if (cardPaymentOption) {
                    cardPaymentOption.classList.toggle('hidden', hideCcPaymentOption);
                }

                codNote.textContent = hideCcPaymentOption ? codNote.dataset.hideCcMessage : codNote.dataset
                    .defaultMessage;

                const checkedRadio = document.querySelector('input[name="payment"]:checked');
                if (hideCcPaymentOption && checkedRadio?.value === 'Card') {
                    const fallbackOption = document.querySelector('input[name="payment"][value="COD"]') ||
                        document.querySelector('input[name="payment"][value="Account"]');

                    if (fallbackOption) {
                        fallbackOption.checked = true;
                    }
                }

                updateHighlight();
            }

            radioButtons.forEach(r => r.addEventListener('change', updateHighlight));
            updateHighlight();

            document.addEventListener('cart:summary-updated', function(event) {
                applyPaymentRestrictions(Boolean(event.detail?.hideCcPaymentOption));
            });

            if (typeof window.loadCartSidebarPreview === 'function') {
                window.loadCartSidebarPreview();
            }

            // Card info live update
            const billingFirstNameInput = document.getElementById('billingFirstName');
            const billingLastNameInput = document.getElementById('billingLastName');
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
            if (billingFirstNameInput) {
                billingFirstNameInput.addEventListener('input', function() {
                    firstNameInput.value = this.value;
                    firstNameInput.dispatchEvent(new Event('input'));
                });
            }

            if (billingLastNameInput) {
                billingLastNameInput.addEventListener('input', function() {
                    lastNameInput.value = this.value;
                    lastNameInput.dispatchEvent(new Event('input'));
                });
            }

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
                //['billingAddress', 'deliveryAddress'],
                ['billingState', 'deliveryState'],
                // ['billingCity', 'deliveryCity'],
                // ['billingZip', 'deliveryZip'],
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
                        document.getElementById('addressCompany').textContent = 'Company: ' + (addr.company || '');
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
                    document.getElementById('admin_code').value = '';
                    taxModal.classList.remove('hidden');
                } else {
                    taxModal.classList.add('hidden');
                    handleTaxExemptRequest(false);
                }
            }

            // Show/hide modal when checkbox changes
            taxExempt.addEventListener('change', handleTaxExemptChange);

            // Optional: When modal is closed, uncheck the box
            window.cancelModal = function() {
                taxModal.classList.add('hidden');
                taxExempt.checked = false;
            };

            document.getElementById('taxForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = this;
                const parsleyForm = $(form).parsley();

                const isFormValid = parsleyForm.validate({
                    force: true
                });
                if (!isFormValid) {
                    return;
                }

                handleTaxExemptRequest();
            });

            function handleTaxExemptRequest(is_tax_exempt = true) {
                const adminCode = document.getElementById('admin_code').value;
                const submitButton = document.getElementById('taxSubmitBtn');
                const submitText = document.getElementById('taxSubmitText');
                const submitLoader = document.getElementById('taxSubmitLoader');

                // Disable button & show loader
                submitButton.disabled = true;
                submitText.classList.add('hidden');
                submitLoader.classList.remove('hidden');

                const taxExemptUrl = '{{ route('front.checkout.tax-exempt') }}';
                apiFetch(taxExemptUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            is_tax_exempt: is_tax_exempt,
                            passcode: adminCode
                        })
                    })
                    .then(response => {
                        if (response.success) {
                            notyf.success(response.message);
                            window.loadCartSidebarPreview(); // Refresh cart preview
                            if (is_tax_exempt) {
                                cancelModal();
                                // If tax exempt is enabled, check the box
                                document.getElementById('taxExempt').checked = true;
                            } else {
                                // If tax exempt is disabled, uncheck the box
                                document.getElementById('taxExempt').checked = false;
                            }
                        } else {
                            notyf.error(response.message);
                            document.getElementById('taxExempt').checked = false;
                        }
                    })
                    .finally(() => {
                        // Re-enable button & hide loader
                        submitButton.disabled = false;
                        submitText.classList.remove('hidden');
                        submitLoader.classList.add('hidden');
                    });
            }

            // ============================
            // Email Uniqueness Check
            // ============================

            const emailInput = document.getElementById('billingEmail');
            const emailError = document.getElementById('billingEmailError');
            if (!emailInput || !emailError) return;

            const CHECK_URL = "{{ route('front.auth.register.check.email.unique') }}";

            let emailValid = true;
            let emailChecking = false;
            let lastCheckedEmail = "";

            const loginLink = document.getElementById('loginWithEmailLink');


            function showEmailError(msg, email = null) {

                emailValid = false;
                emailError.textContent = msg;
                emailError.classList.remove('hidden');
                emailInput.classList.add('border-red-500');

                if (email && loginLink) {
                    const baseUrl = "{{ route('front.auth.login.index') }}";
                    loginLink.href = `${baseUrl}?email=${encodeURIComponent(email)}`;
                }

            }

            function clearEmailError() {
                emailValid = true;
                emailError.textContent = "";
                emailError.classList.add('hidden');
                emailInput.classList.remove('border-red-500');
            }

            async function checkEmailUnique(email) {
                if (!email) return;

                // basic format check
                const okFormat = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                if (!okFormat) {
                    showEmailError("Please enter a valid email address.");
                    return;
                }

                // avoid repeated calls for same email
                if (email === lastCheckedEmail) return;

                emailChecking = true;
                lastCheckedEmail = email;
                clearEmailError();
                showEmailError("please wait, checking email...");
                checkoutBtn.disabled = true;
                checkoutBtn.classList.add('opacity-50', 'cursor-not-allowed');
                try {
                    const res = await fetch(`${CHECK_URL}?email=${encodeURIComponent(email)}`, {
                        method: "GET",
                        headers: {
                            "Accept": "application/json"
                        }
                    });


                    const data = await res.json();

                    // Adjust this based on your API response shape:
                    // Expecting: { valid: true } or { valid: false }
                    if (data.valid === true) {
                        clearEmailError();
                        checkoutBtn.disabled = false;
                        checkoutBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    } else {
                        showEmailError(
                            "There is an account already created with this email address. Please login to add this order to your account history or use a different email address and checkout as a Guest.",
                            email
                        );
                    }
                } catch (err) {
                    console.error("Email check error:", err);
                    showEmailError("Unable to validate email right now. Please try again.");
                } finally {
                    emailChecking = false;
                }
            }

            emailInput.addEventListener("blur", function() { //
                if (emailInput.readOnly) return; // skip for logged-in readonly email
                checkEmailUnique(emailInput.value.trim());
            });
        });
    </script>
@endpush
