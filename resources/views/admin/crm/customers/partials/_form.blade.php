<div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 mt-6">
    <!-- Personal Information -->
    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <h2 class="text-lg font-semibold mb-4">Personal Information</h2>

        {!! html()->text('alladdresslist', $addressListJson ?? '')->class('hidden')->attributes([
        'id' => 'alladdresslist',
        'autocomplete' => 'off'
        ]) !!}

        {!! html()->text('notes_json')->class('hidden')->attributes([
        'id' => 'notes_json',
        'autocomplete' => 'off'
        ]) !!}

        {!! html()->text('tax_document_review_status', old('tax_document_status', $customer->tax_document_status ?? 'Pending Review'))
        ->class('hidden')
        ->attributes([
        'id' => 'tax_document_review_status',
        'autocomplete' => 'off',
        ]) !!}

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- First Name  -->
            <div class="mb-4">
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1 ">First Name </label>

                {!! html()->text('first_name', old('first_name', $customer->first_name ?? ''))->class([
                'w-full rounded-md border focus:outline-none px-3 py-2 text-sm shadow-sm focus:ring-2',
                'border-red-500' => $errors->has('first_name'),
                'border-gray-300' => !$errors->has('first_name'),
                ])->attributes([

                'data-parsley-maxlength' => 100,
                'placeholder' => 'Enter First Name',
                'id' => 'primary_first_name',
                'autocomplete' => 'off',
                ]) !!}

                @error('first_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Last Name  -->
            <div class="mb-4">
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1 ">Last Name </label>

                {!! html()->text('last_name', old('last_name', $customer->last_name ?? ''))->class([
                'w-full rounded-md border focus:outline-none px-3 py-2 text-sm shadow-sm focus:ring-2',
                'border-red-500' => $errors->has('last_name'),
                'border-gray-300' => !$errors->has('last_name'),
                ])->attributes([

                'data-parsley-maxlength' => 100,
                'placeholder' => 'Enter Last Name',
                'id' => 'primary_last_name',
                'autocomplete' => 'off',
                ]) !!}

                @error('last_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1 required">Email Address </label>

                {!! html()->email('email', old('email', $customer->email ?? ''))->class([
                'w-full rounded-md border focus:outline-none px-3 py-2 text-sm shadow-sm focus:ring-2',
                'border-red-500' => $errors->has('email'),
                'border-gray-300' => !$errors->has('email'),
                ])->attributes([

                'placeholder' => 'Enter Email',
                'id' => 'email',
                'autocomplete' => 'off',
                'name' => 'email',
                'data-parsley-type' => 'email',
                'data-parsley-trigger' => 'change',
                'data-parsley-remote' => route('admin.crm.customers.check.email.unique', [
                'except' => $customer->id ?? null,
                ]),
                'data-parsley-remote-validator' => 'customemailcheck',
                'data-parsley-remote-message' => 'This email is already taken by another user.',
                ])->required() !!}



                @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>


            <div class="mb-4">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1 required">Phone Number </label>
                {!! html()->text('phone', old('phone', $customer->phone ?? ''))->class([
                'masked-phone w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2',
                'border-red-500' => $errors->has('phone'),
                'border-gray-300' => !$errors->has('phone'),
                ])->attributes([
                'maxlength' => 14,
                'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                'placeholder' => '(xxx) xxx-xxxx',
                'id' => 'phone',
                'autocomplete' => 'tel',
                ])->required() !!}
                @error('phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
      

    </div>

    <!-- Company Information -->
    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <h2 class="text-lg font-semibold mb-4">Company Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Company Name --}}
            <div class="mb-4">
                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1 ">Company Name </label>

                {!! html()->text('company_name', old('company_name', $customer->company_name ?? ''))->class([
                'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2',
                'border-red-500' => $errors->has('company_name'),
                'border-gray-300' => !$errors->has('company_name'),
                ])->attributes([
                'maxlength' => 200,
                'data-parsley-maxlength' => 200,
                'placeholder' => 'Enter company name',
                'id' => 'company_name',
                ]) !!}

                @error('company_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Company Phone --}}

            <div class="mb-4">
                <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-1">Company Phone</label>
                {!! html()->text('company_phone', old('company_phone', $customer->company_phone ?? ''))->class([
                'masked-phone w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2',
                'border-red-500' => $errors->has('company_phone'),
                'border-gray-300' => !$errors->has('company_phone'),
                ])->attributes([
                'maxlength' => 14,
                'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                'placeholder' => '(xxx) xxx-xxxx',
                'id' => 'company_phone',
                'autocomplete' => 'tel',
                ]) !!}
                @error('company_phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <!-- {{-- Website --}} -->
        <div>
            <label for="company_website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>

            <div class="flex gap-2 mb-4">
                {{-- Protocol --}}
                {!! html()->select('website_protocol', [
                'https://' => 'https://',
                'http://' => 'http://',
                ], old('website_protocol', $website_protocol))
                ->class([
                'w-2/6 border rounded-md lg:px-1 py-2 text-sm shadow-sm focus:outline-none focus:ring-2',
                'border-red-500' => $errors->has('website_protocol'),
                'border-gray-300' => !$errors->has('website_protocol'),
                ])
                ->id('website_protocol') !!}


                {{-- Website Name --}}
                {!! html()->text('company_website', old('company_website',$company_website))
                ->class([
                'w-4/6 border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2',
                'border-red-500' => $errors->has('company_website'),
                'border-gray-300' => !$errors->has('company_website'),
                ])
                ->placeholder('example')
                ->id('company_website') !!}

                {{-- Extension --}}
                {!! html()->select('website_extension', [
                '.com' => '.com',
                '.org' => '.org',
                '.net' => '.net',
                '.in' => '.in',
                '.edu' => '.edu',
                '.gov' => '.gov',
                ], old('website_extension', $website_extension))
                ->class([
                'w-2/6 border rounded-md lg:px-1 py-2 text-sm shadow-sm focus:outline-none focus:ring-2',
                'border-red-500' => $errors->has('website_extension'),
                'border-gray-300' => !$errors->has('website_extension'),
                ])
                ->id('website_extension') !!}

                @error('company_website')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>
<!-- Addresses -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">

    <!-- Addresses -->
    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold">Billing Address</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="addresses[0][type]" value="Billing">
            <input type="hidden" name="addresses[0][is_primary]" value="1">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 ">First Name </label>
                <input type="text" id="billing_first_name" name="addresses[0][first_name]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm" placeholder="First Name">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 ">Last Name </label>
                <input type="text" id="billing_last_name" name="addresses[0][last_name]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm" placeholder="Last Name">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1 ">Address </label>
                <input type="text" id="billing_address" name="addresses[0][address]" class="w-full border border-gray-300 shadow-sm  rounded-md px-3 py-2 text-sm" placeholder="Street Address">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 ">City </label>
                <input type="text" id="billing_city" name="addresses[0][city]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm" placeholder="Enter City">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 ">State </label>
                <select id="billing_state" name="addresses[0][state_id]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm">
                    <option value="">-- Select State --</option>
                    @foreach ($states as $state)
                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
                <!-- Zip Code 1 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 ">Zip Code </label>
                    <input id="billing_zip" type="text" name="addresses[0][zip_code]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm" placeholder="Enter Zip Code" maxlength="8">
                </div>

                <!-- Zip Code 2 -->
                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-1 ">Country </label>
                    {!! html()->select('addresses[0][Country]', [
                    'USA' => 'USA',
                    'Canada' => 'Canada',
                    'UK' => 'UK',
                    'India' => 'India',
                    ], old('country', 'USA')) // default to USA if old or user value is not set
                    ->id('country')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'autocomplete' => 'off',
                    'id' => 'billing_country',
                    ])

                    !!}

                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 ">Phone Number </label>
                <input type="text" id="billing_phone" name="addresses[0][phone]" class=" masked-phone w-full border shadow-sm rounded-md px-3 py-2 text-sm" placeholder="(xxx) xxx-xxxx" data-parsley-pattern="^\(\d{3}\)\s\d{3}-\d{4}$"
                    data-parsley-error-message="Please enter phone number in <br> format (xxx) xxx-xxxx">
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <div class="mb-6">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h2 class="text-lg font-semibold">Delivery Address</h2>

                <!-- Checkbox -->
                <label class="flex items-center space-x-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" id="sameAsBilling" name="sameAsBilling"
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" checked>
                    <span>Same as billing address</span>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="addresses[1][type]" value="Shipping">
            <input type="hidden" name="addresses[1][is_primary]" value="1">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 ">First Name </label>
                <input type="text" id="delivery_first_name" name="addresses[1][first_name]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm" placeholder="First Name">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 ">Last Name </label>
                <input type="text" id="delivery_last_name" name="addresses[1][last_name]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm" placeholder="Last Name">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1 ">Address </label>
                <input type="text" id="delivery_address" name="addresses[1][address]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm" placeholder="Street Address">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 ">City </label>
                <input type="text" id="delivery_city" name="addresses[1][city]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm" placeholder="Enter City">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 ">State </label>
                <select id="delivery_state" name="addresses[1][state_id]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm">
                    <option value="">-- Select State --</option>
                    @foreach ($states as $state)
                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
                <!-- Zip Code 1 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 ">Zip Code </label>
                    <input id="delivery_zip" type="text" name="addresses[1][zip_code]" class="w-full border border-gray-300 shadow-sm rounded-md px-3 py-2 text-sm" placeholder="Enter Zip Code" maxlength="8">
                </div>

                <!-- Zip Code 2 -->
                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-1 ">Country </label>


                    {!! html()->select('addresses[1][Country]', [
                    'USA' => 'USA',
                    'Canada' => 'Canada',
                    'UK' => 'UK',
                    'India' => 'India',
                    ], old('country', 'USA')) // default to USA if old or user value is not set
                    ->id('country')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'autocomplete' => 'off',
                    'id' =>"delivery_country" ,
                    ])

                    !!}

                </div>



            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 ">Phone Number </label>
                <input type="text" id="delivery_phone" name="addresses[1][phone]" class=" masked-phone w-full border shadow-sm rounded-md px-3 py-2 text-sm" placeholder="(xxx) xxx-xxxx"
                    data-parsley-pattern="^\(\d{3}\)\s\d{3}-\d{4}$"
                    data-parsley-error-message="Please enter phone number in <br> format (xxx) xxx-xxxx">
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 items-stretch">

    <div class=" flex flex-col h-full">
        <!-- Tags and Notes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-grow">
            <!-- Tags -->
            <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-2">
                    <label for="ContactTags" class="block text-sm font-medium text-gray-700">Tags</label>
                    <button type="button" onclick="openTagModal()"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add
                    </button>
                </div>
                <select id="ContactTags" name="tags[]" multiple
                    class="choices-select w-full rounded-md border border-gray-300 text-sm flex-grow"></select>
            </div>

            <!-- Notes Section -->
            <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <button type="button" id="addNoteBtn"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add
                    </button>
                </div>

                <!-- Notes List -->
                <div id="noteListContainer" class="p-2 max-h-60 overflow-y-auto">
                    <ul id="noteList" class="list-disc text-sm text-gray-700 space-y-1 pl-3 ">
                        <li class="text-gray-400 text-sm">No notes available.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-200 p-4 flex flex-col h-full">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-grow">
            <!-- Assign to Funnels -->
            <div class="md:col-span-2 flex flex-col flex-grow">
                <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Funnels</label>
                <div
                    class="border border-gray-300 rounded-md flex-grow flex items-center justify-center text-gray-400 text-sm bg-gray-50 hover:border-blue-400 hover:text-gray-700 transition-all">
                    Add or assign funnels here
                </div>
            </div>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 bg-gray-50 mt-6">
    <!-- Customer Account (smaller box) -->
    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm col-span-12 lg:col-span-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Customer Account</h2>
        <div class=" text-sm text-gray-600">
            <!-- Row 1: Account Approved & Approved By -->
            <div class="flex flex-col md:flex-row gap-4 mb-4">
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Approved</label>
                    {!! html()
                    ->select('is_credit_account', [
                    '0' => 'Not Approved',
                    '1' => 'Approved',
                    ], old('is_credit_account', $customer->is_credit_account ?? ''))
                    ->id('is_credit_account')
                    ->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm text-gray-700 bg-white',
                    'border-red-500' => $errors->has('is_credit_account'),
                    'border-gray-300' => !$errors->has('is_credit_account'),
                    ])
                    !!}
                    @error('is_credit_account')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="account_approved_by" class="block text-sm font-medium text-gray-700 mb-1">Approved By</label>
                    {!! html()
                    ->select(
                    'account_approved_by',
                    $admins->mapWithKeys(fn($user) => [$user->id => $user->full_name])->toArray(),
                    old('account_approved_by', $customer->account_approved_by ?? '')
                    )
                    ->id('account_approved_by')
                    ->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm text-gray-700 bg-white',
                    'border-red-500' => $errors->has('account_approved_by'),
                    'border-gray-300' => !$errors->has('account_approved_by'),
                    ])
                    ->placeholder('Select approver')

                    !!}

                    @error('account_approved_by')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Row 2: Credit Limit & Application Completed -->
            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Credit Limit</label>


                    @php
                    $creditOptions = collect(range(1000, 20000, 1000))->mapWithKeys(function ($value) {
                    return [$value => number_format($value)];
                    });
                    @endphp

                    {!! html()
                    ->select('credit_limit', $creditOptions->toArray(), old('credit_limit', $customer->credit_limit ?? ''))
                    ->id('credit_limit')
                    ->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm text-gray-700 bg-white',
                    'border-red-500' => $errors->has('credit_limit'),
                    'border-gray-300' => !$errors->has('credit_limit'),
                    ])->placeholder('Select Credit Limit')
                    !!}

                    @error('credit_limit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Completed On</label>

                    {!! html()->text('account_application_completed', old('account_application_completed', \App\Helpers\CustomHelper::formatDate($customer->account_application_completed ?? null) ?? null))->class([
                    'w-full border rounded-md datepicker unded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 bg-white text-gray-700',
                    'border-red-500' => $errors->has('account_application_completed'),
                    'border-gray-300' => !$errors->has('account_application_completed'),
                    ])->attributes([
                    'id' => 'account_application_completed',
                    'placeholder' => 'MM-DD-YYYY',
                    'autocomplete' => 'off',
                    ]) !!}

                    @error('account_application_completed')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror


                </div>
            </div>

            <!-- Row 3 : Status :-Good Standing , Overdue , Bad Debt , Blacklisted   -->
            <div class="flex flex-col md:flex-row gap-4 mt-4" id="accountStatusWrapper">
                <div class="w-full">
                    <label class=" text-sm font-medium text-gray-700 mb-1">Account Status: </label>
                    <span id="creditStatus" class="inline-block rounded-full bg-green-100 text-green-800 text-sm font-semibold px-2 py-1">
                        Good Standing
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Combined Tax Exempt + Upload (wider box) -->
    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm col-span-12 lg:col-span-8">
        <div class="flex flex-col md:flex-row">
            <!-- Tax Exempt Left Panel -->
            <div class="md:w-1/2 pr-0 md:pr-6 border-b md:border-b-0 md:border-r border-gray-300 mb-6 md:mb-0">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Tax Exempt</h2>
                <div class="flex flex-col md:flex-row gap-4 mb-4">
                    <div class="w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>

                        {!! html()
                        ->select('tax_status', [
                        'Taxable' => 'Taxable',
                        'Exempt' => 'Exempt',
                        ], old('tax_status', $customer->tax_status ?? ''))
                        ->id('tax_status')
                        ->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm text-gray-700 bg-white',
                        'border-red-500' => $errors->has('tax_status'),
                        'border-gray-300' => !$errors->has('tax_status'),
                        ])

                        !!}

                        @error('tax_status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                    </div>
                    <div class="w-full">
                        <label for="tax_status_approved_by" class="block text-sm font-medium text-gray-700 mb-1">Approved By</label>

                        {!! html()
                        ->select(
                        'tax_status_approved_by',
                        $admins->mapWithKeys(fn($user) => [$user->id => $user->full_name])->toArray(),
                        old('tax_status_approved_by', $customer->tax_status_approved_by ?? '')
                        )
                        ->id('tax_status_approved_by')
                        ->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm text-gray-700 bg-white',
                        'border-red-500' => $errors->has('tax_status_approved_by'),
                        'border-gray-300' => !$errors->has('tax_status_approved_by'),
                        ])
                        ->placeholder('Select approver')

                        !!}

                        @error('tax_status_approved_by')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-2">Requires approved tax document</p>
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Date</label>

                        {!! html()->text('tax_document_upload_date', old('tax_document_upload_date', \App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date ?? null) ?? null))->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 text-gray-700 bg-white datepicker',
                        'border-red-500' => $errors->has('tax_document_upload_date'),
                        'border-gray-300' => !$errors->has('tax_document_upload_date'),
                        ])->attributes([
                        'id' => 'tax_document_upload_date',
                        'placeholder' => 'MM-DD-YYYY',
                        'autocomplete' => 'off',
                        ]) !!}

                        @error('tax_document_upload_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valid Until</label>

                        {!! html()->text('tax_document_valid_until', old('tax_document_valid_until', \App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until ?? null) ?? null))->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 text-gray-700 bg-white datepicker',
                        'border-red-500' => $errors->has('tax_document_valid_until'),
                        'border-gray-300' => !$errors->has('tax_document_valid_until'),
                        ])->attributes([
                        'id' => 'tax_document_valid_until',
                        'placeholder' => 'MM-DD-YYYY',
                        'autocomplete' => 'off',
                        ]) !!}

                        @error('tax_document_valid_until')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            @if (!empty($customer))
            <input type="hidden" id="is_edit_mode" value="1">
            <input type="hidden" id="customer_id" value="{{ $customer->id }}">
            <input type="hidden" id="tax_document_status" value="{{ $customer->tax_document_status }}">
            @endif

            @php
            $taxDocumentUrl = isset($customer) && $customer->media ? $customer->media->getUrl() : null;
            $taxDocumentMediaId = isset($customer) && $customer->media ? $customer->media->id : null;
            @endphp

            <input type="hidden" id="has_existing_tax_document" value="{{ !empty($taxDocumentUrl) ? '1' : '0' }}">


            <!-- Upload Right Panel -->
            <div class="md:w-1/2 md:pl-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Tax Exempt Upload</h2>


                <div id="uploadedFileBox" class="hidden flex items-center justify-between bg-gray-200 px-2 w-full rounded-md mb-3 shadow-sm ">
                    <div><x-heroicon-o-document-text class="w-5 h-5" /></div>
                    <div class="w-4/6  py-2 text-sm text-gray-600">
                        <div id="uploadedFileName" class="truncate text-nowrap">No file chosen</div>
                        <div id="uploadedDate">Uploaded</div>
                    </div>
                    <div class="w-1/6 flex gap-x-2">
                        <button type="button" id="viewFileBtn" class="text-blue-600 hover:text-blue-800" title="View" target="_blank">
                            <x-heroicon-o-eye class="w-5 h-5" />
                        </button>
                        <button type="button" id="deleteFileBtn" class="text-red-600 hover:text-red-800" title="Delete">
                            <x-heroicon-o-trash class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- BROWSE SECTION (initially visible) -->
                <div id="browseBox">
                    <label for="taxDocumentInput" class="cursor-pointer border rounded-l-md flex items-center shadow-sm">
                        <span class="bg-gray-300 px-2 py-2 rounded-md">Browse...</span>
                        <span id="file-name" class="truncate w-full text-nowrap px-3 py-2 text-sm text-gray-600 rounded-md">No file chosen</span>
                    </label>
                    <input
                        type="file"
                        id="taxDocumentInput"
                        name="tax_document"
                        class="hidden w-full border rounded-r-md px-3 py-2 text-sm shadow-sm"
                        accept="image/*,.pdf,.doc,.docx" />
                </div>


                <div class="flex flex-col md:flex-row gap-4 mt-3 mb-3">
                    <div class="w-full" id="statusLabelWrapper" style="display: {{ !empty($taxDocumentUrl) ? 'block' : 'none' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    </div>
                    <div class="w-full">
                        <span id="taxStatusBadge"
                            class="inline-block float-right rounded-full bg-yellow-100 text-yellow-800 text-xs font-medium px-3 py-1"
                            style="display: {{ !empty($taxDocumentUrl) ? 'inline-block' : 'none' }}">
                            {{ $customer->tax_document_status ?? 'Pending Review' }}
                        </span>

                    </div>
                </div>

                <div class="flex justify-between gap-4 mt-auto"
                    style="display: {{ !empty($taxDocumentUrl) ? 'flex' : 'none' }}">
                    <button type='button' id="approveBtn"
                        class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                        Approve
                    </button>
                    <button type='button' id="rejectBtn"
                        class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                        Reject
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>


@push('js')


<script>
    window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));
</script>


@if (!empty($taxDocumentUrl))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const uploadedFileBox = document.getElementById("uploadedFileBox");
        const uploadedFileName = document.getElementById("uploadedFileName");
        const uploadedDate = document.getElementById("uploadedDate");
        const viewFileBtn = document.getElementById("viewFileBtn");
        const deleteFileBtn = document.getElementById("deleteFileBtn");

        const browseBox = document.getElementById("browseBox");
        // Hide browse, show uploaded box
        browseBox.style.display = 'none';
        uploadedFileBox.classList.remove("hidden");
        uploadedFileName.textContent = "{{ basename($taxDocumentUrl) }}";
        uploadedDate.textContent = "Uploaded: {{ $customer->tax_document_upload_date ?? 'N/A' }}";
        viewFileBtn.setAttribute("onclick", "window.open('{{ $taxDocumentUrl }}', '_blank')");




        // Handle delete via hidden form
        deleteFileBtn.addEventListener("click", function() {
            if (confirm("Are you sure you want to delete this document?")) {
                document.getElementById("delete-media-form-{{ $customer->id }}").submit();
            }
        });


    });
</script>
@endif


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const uploadDateInput = document.getElementById("tax_document_upload_date");
        const validUntilInput = document.getElementById("tax_document_valid_until");
        const fileInput = document.getElementById("taxDocumentInput");
        const statusBadge = document.getElementById("taxStatusBadge");
        const approveBtn = document.getElementById("approveBtn");
        const rejectBtn = document.getElementById("rejectBtn");
        const statusLabelWrapper = document.getElementById("statusLabelWrapper");
        const approverSelect = document.getElementById("tax_status_approved_by");
        const hiddenStatusInput = document.getElementById("tax_document_review_status");

        let manualAction = null;

        function setStatus(text, bgColor, textColor) {
            statusBadge.textContent = text;
            statusBadge.className = `inline-block float-right rounded-full ${bgColor} ${textColor} text-xs font-medium px-3 py-1`;
            hiddenStatusInput.value = text;
        }

        function isDateFilled(dateInput) {
            return dateInput && dateInput.value.trim() !== '';
        }

        function isFileSelected() {
            const hasExistingFile = document.getElementById("has_existing_tax_document")?.value === '1';
            return fileInput && fileInput.files.length > 0 || hasExistingFile;
        }

        function updateStatusBasedOnInputs() {
            const hasFile = isFileSelected();
            const hasUploadDate = isDateFilled(uploadDateInput);
            const hasValidUntilDate = isDateFilled(validUntilInput);
            const approverSelected = approverSelect?.value?.trim() !== "";
            const existingStatus = document.getElementById("tax_document_status")?.value?.trim();


            if (!hasFile) {
                // No file uploaded, hide everything
                statusBadge.style.display = 'none';
                approveBtn.parentElement.style.display = 'none';
                statusLabelWrapper.style.display = 'none';
                hiddenStatusInput.value = '';
                manualAction = null;
                return;
            }

            // File exists: show buttons and status
            statusBadge.style.display = 'inline-block';
            approveBtn.parentElement.style.display = 'flex';
            statusLabelWrapper.style.display = 'block';

            // Determine status
            if (manualAction === 'Approved') {
                setStatus('Approved', 'bg-green-100', 'text-green-800');
            } else if (manualAction === 'Rejected') {
                setStatus('Rejected', 'bg-red-100', 'text-red-800');
            }
            // Existing DB status (from server)
            else if (existingStatus === 'Approved') {
                setStatus('Approved', 'bg-green-100', 'text-green-800');
            } else if (existingStatus === 'Rejected') {
                setStatus('Rejected', 'bg-red-100', 'text-red-800');
            } else if (
                (hasUploadDate || hasValidUntilDate || approverSelected)
            ) {
                if (hasUploadDate && hasValidUntilDate && approverSelected) {
                    setStatus('Pending Review', 'bg-yellow-100', 'text-yellow-800');
                } else {
                    setStatus('Expired', 'bg-gray-200', 'text-gray-700');
                }
            } else {
                // Only file selected, nothing else → show Pending Review
                setStatus('Pending Review', 'bg-yellow-100', 'text-yellow-800');
            }
        }

        approveBtn.addEventListener("click", () => {
            const hasFile = isFileSelected();
            const hasUploadDate = isDateFilled(uploadDateInput);
            const hasValidUntilDate = isDateFilled(validUntilInput);
            const approverSelected = approverSelect?.value?.trim() !== "";

            const isEditMode = document.getElementById("is_edit_mode")?.value === '1';

            if (hasFile && hasUploadDate && hasValidUntilDate && approverSelected) {
                manualAction = 'Approved';

                updateStatusBasedOnInputs();

                if (isEditMode) {
                    sendReviewStatusAjax('Approved');
                }

            } else {
                notyf.error('Please select a file, fill both dates, and select an approver before approving.');

            }
        });

        rejectBtn.addEventListener("click", () => {
            const hasFile = isFileSelected();
            const hasUploadDate = isDateFilled(uploadDateInput);
            const hasValidUntilDate = isDateFilled(validUntilInput);
            const approverSelected = approverSelect?.value?.trim() !== "";

            const isEditMode = document.getElementById("is_edit_mode")?.value === '1';


            if (hasFile && hasUploadDate && hasValidUntilDate && approverSelected) {
                manualAction = 'Rejected';
                updateStatusBasedOnInputs();



                if (isEditMode) {
                    sendReviewStatusAjax('Rejected');
                }


            } else {
                notyf.error('Please select a file, fill both dates, and select an approver before rejecting.');

            }
        });

        // Reset manual action when any field changes
        [uploadDateInput, validUntilInput, fileInput, approverSelect].forEach(input => {
            input?.addEventListener("change", () => {
                manualAction = null;
                updateStatusBasedOnInputs();
            });
        });

        // Run once on load
        updateStatusBasedOnInputs();


        function sendReviewStatusAjax(status) {
            const customerId = document.getElementById("customer_id")?.value;

            if (!customerId || !status) return;

            console.log('customerId:', customerId);
            console.log('status:', status);

            fetch("{{ route('admin.crm.customers.tax_status.update') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        customer_id: customerId,
                        status: status,
                    }),
                })
                .then(res => {
                    if (!res.ok) throw new Error("Network response was not ok");
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        console.log('AJAX success:', data.message);
                        notyf.success(data.message || "Status updated successfully.");
                    } else {
                        console.error('AJAX error:', data.message);
                        notyf.error(data.message || "Failed to update status.");
                    }
                })
                .catch(error => {
                    console.error('AJAX catch:', error);
                    notyf.error("Something went wrong while updating status.");
                });
        }




        const fileInputforshow = document.getElementById("taxDocumentInput");
        const fileNameSpan = document.getElementById("file-name");
        const uploadedFileBox = document.getElementById("uploadedFileBox");
        const uploadedFileName = document.getElementById("uploadedFileName");
        const uploadedDate = document.getElementById("uploadedDate");
        const browseBox = document.getElementById("browseBox");
        const viewFileBtn = document.getElementById("viewFileBtn");
        const deleteFileBtn = document.getElementById("deleteFileBtn");

        let uploadedFileURL = null;

        function formatDate(date) {
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            };
            return date.toLocaleDateString('en-US', options);
        }

        fileInputforshow.addEventListener('change', function(e) {
            if (!e.target.files.length) return;

            const file = e.target.files[0];
            fileNameSpan.textContent = file.name;

            // Hide browse, show uploaded box
            browseBox.style.display = 'none';
            uploadedFileBox.classList.remove('hidden');

            uploadedFileName.textContent = file.name;
            uploadedDate.textContent = `Uploaded ${formatDate(new Date())}`;

            // Create object URL for viewing
            uploadedFileURL = URL.createObjectURL(file);
            viewFileBtn.onclick = () => window.open(uploadedFileURL, '_blank');
        });

        deleteFileBtn.addEventListener('click', function() {
            fileInputforshow.value = ''; // Clear the file input
            fileNameSpan.textContent = 'No file chosen';
            uploadedFileBox.classList.add('hidden');
            browseBox.style.display = 'block';

            uploadedFileName.textContent = '';
            uploadedDate.textContent = '';
            uploadedFileURL = null;

            updateStatusBasedOnInputs();
        });



    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const creditStatus = document.getElementById('creditStatus');
        const accountApproved = document.getElementById('is_credit_account');
        const creditLimit = document.getElementById('credit_limit');
        const statusWrapper = document.getElementById('accountStatusWrapper');


        function updateStatus() {
            const isApproved = accountApproved.value === '1';
            const hasCreditLimit = creditLimit.value !== '';


            statusWrapper.style.display = isApproved ? 'flex' : 'none';

            if (isApproved && hasCreditLimit) {
                creditStatus.textContent = 'Good Standing';
                creditStatus.className = 'inline-block rounded-full text-sm font-semibold px-2 py-1 bg-green-100 text-green-800';
            } else if (isApproved && !hasCreditLimit) {
                creditStatus.textContent = 'Pending';
                creditStatus.className = 'inline-block rounded-full text-sm font-semibold px-2 py-1 bg-yellow-100 text-yellow-800';
            } else {
                creditStatus.textContent = '';
                creditStatus.className = 'inline-block rounded-full text-sm font-semibold px-2 py-1 bg-gray-100 text-gray-700';
            }
        }

        updateStatus();


        accountApproved.addEventListener('change', updateStatus);
        creditLimit.addEventListener('change', updateStatus);
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', () => {

        window.Parsley.addAsyncValidator('customemailcheck', function(xhr) {
            // Expecting { valid: true/false }
            const response = xhr.responseJSON || {};
            return response.valid === true;
        });


    });
</script>


<!-- Sync fields ( first name , last name ) -->

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const firstName = document.querySelector('#primary_first_name');
        const lastName = document.querySelector('#primary_last_name');

        const billingFirst = document.querySelector('#billing_first_name');
        const billingLast = document.querySelector('#billing_last_name');
        const deliveryFirst = document.querySelector('#delivery_first_name');
        const deliveryLast = document.querySelector('#delivery_last_name');

        let billingLinked = true;
        let deliveryLinked = true;

        // Sync names from main fields → address fields
        firstName.addEventListener('input', () => {
            if (billingLinked) billingFirst.value = firstName.value;
            if (deliveryLinked) deliveryFirst.value = firstName.value;
        });

        lastName.addEventListener('input', () => {
            if (billingLinked) billingLast.value = lastName.value;
            if (deliveryLinked) deliveryLast.value = lastName.value;
        });

        // Stop syncing when user manually edits address fields
        billingFirst.addEventListener('input', () => billingLinked = false);
        billingLast.addEventListener('input', () => billingLinked = false);
        deliveryFirst.addEventListener('input', () => deliveryLinked = false);
        deliveryLast.addEventListener('input', () => deliveryLinked = false);
    });
</script>

<!-- Sync fields Same As Billing -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize IMask for both fields
        const phoneMasks = {};
        document.querySelectorAll('.masked-phone').forEach(input => {
            phoneMasks[input.id] = IMask(input, {
                mask: '(000) 000-0000'
            });
        });

        const billingFields = {
            first_name: document.querySelector('#billing_first_name'),
            last_name: document.querySelector('#billing_last_name'),
            address: document.querySelector('#billing_address'),
            city: document.querySelector('#billing_city'),
            state: document.querySelector('#billing_state'),
            zip: document.querySelector('#billing_zip'),
            country: document.querySelector('#billing_country'),
            phone: document.querySelector('#billing_phone'),
        };

        const deliveryFields = {
            first_name: document.querySelector('#delivery_first_name'),
            last_name: document.querySelector('#delivery_last_name'),
            address: document.querySelector('#delivery_address'),
            city: document.querySelector('#delivery_city'),
            state: document.querySelector('#delivery_state'),
            zip: document.querySelector('#delivery_zip'),
            country: document.querySelector('#delivery_country'),
            phone: document.querySelector('#delivery_phone'),
        };

        const sameAsBilling = document.querySelector('#sameAsBilling');

        // Copy billing → delivery safely (works even with IMask)
        const copyBillingToDelivery = () => {
            for (let key in billingFields) {
                if (key === 'phone') {
                    // Use IMask-safe API
                    const billingMask = phoneMasks[billingFields[key].id];
                    const deliveryMask = phoneMasks[deliveryFields[key].id];
                    deliveryMask.unmaskedValue = billingMask.unmaskedValue;
                } else {
                    deliveryFields[key].value = billingFields[key].value;
                }
            }
        };

        const clearDelivery = () => {
            for (let key in deliveryFields) {
                if (key === 'country') {
                    deliveryFields[key].value = 'USA';
                } else {
                    deliveryFields[key].value = '';
                }
            }
            // Reset mask explicitly
            const deliveryMask = phoneMasks['delivery_phone'];
            if (deliveryMask) deliveryMask.unmaskedValue = '';
        };

        const toggleDeliveryFields = (disabled) => {
            for (let key in deliveryFields) {
                deliveryFields[key].disabled = disabled;
                deliveryFields[key].classList.toggle('bg-gray-100', disabled);
                deliveryFields[key].classList.toggle('cursor-not-allowed', disabled);
            }
        };

        // Initial setup
        if (sameAsBilling.checked) {
            copyBillingToDelivery();
            toggleDeliveryFields(true);
        }

        // Listen for billing changes (with slight delay)
        for (let key in billingFields) {
            billingFields[key].addEventListener('input', () => {
                if (sameAsBilling.checked) {
                    setTimeout(copyBillingToDelivery, 10); // Wait for IMask to finish formatting
                }
            });
        }

        // Checkbox toggle
        sameAsBilling.addEventListener('change', () => {
            if (sameAsBilling.checked) {
                copyBillingToDelivery();
                toggleDeliveryFields(true);
            } else {
                clearDelivery();
                toggleDeliveryFields(false);
            }
        });
    });
</script>




@endpush
