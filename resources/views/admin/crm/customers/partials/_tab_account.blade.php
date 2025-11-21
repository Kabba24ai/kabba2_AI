{{ html()->form()->id('customerForm')->attributes([
                            'autocomplete' => 'off',

                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

{!! html()->text('alladdresslist')->class('hidden')->attributes([
'id' => 'alladdresslist',
'autocomplete' => 'off'
]) !!}

<input type="hidden" id="existing_tags_json"
    value='{{ $customer->tag_objects }}'>


<div class="bg-white p-6 rounded shadow mt-6 mb-0">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Title and Description -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Customer Account Management</h2>
            <p class="text-sm text-gray-500">Manage customer account information and administrative settings</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:gap-2">
            <span class="text-sm text-gray-700 mr-2 sm:mr-0 sm:mb-1 mb-1">Admin Actions:</span>
            <div class="flex flex-wrap items-center gap-2">
                @if($customer->status!='Archived')
                <button onclick="confirmAndSuspend({{ $customer->id }})" type="button" class="bg-red-600 text-white px-4 py-2 rounded text-sm static-view">Suspend Account</button>
                @elseif($customer->status!='Active')
                <button onclick="confirmAndActive({{ $customer->id }})" type="button" class="bg-green-600 text-white px-4 py-2 rounded text-sm static-view">Activate Account</button>
                @endif
                <button type="button" id="openResetPasswordModal" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm static-view">Reset Password</button>
                <button id="editBtn" type="button" class="bg-blue-600 inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-md hover:bg-green-700 transition"> Edit Information</button>
                <button type="submit" id="saveBtn" style="display:none;" class="saveBtn bg-green-600 text-white px-4 py-2 rounded text-sm">Save Changes</button>
                <button type="button" id="cancelBtn" style="display:none;" class="bg-gray-700 text-white px-4 py-2 rounded text-sm">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 mb-0">
    <!-- Personal Information -->
    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200">
        <h3 class="text-base font-semibold text-gray-800 flex items-center gap-1 mb-4">
            <x-heroicon-o-user class="w-5 h-5 text-gray-900" /> Personal Information
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">

            <div class="min-w-0">
                <label class="text-xs text-gray-500 font-medium ">First Name </label>
                <div class="static-view text-sm text-gray-900">{{ $customer->first_name ?? '' }}</div>
                <!-- <input class="edit-view pl-2 pr-2 py-2 w-full bor der border-gray-300 rounded-md text-sm" value="{{ $customer->first_name ?? '' }}" /> -->

                {!! html()->text('first_name', old('first_name', $customer->first_name ?? ''))
                ->class([
                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                'border-red-500' => $errors->has('first_name'),
                ])
                ->attributes([

                'placeholder' => 'Enter First Name',
                'id' => 'first_name',
                'autocomplete' => 'off',
                ]) !!}

            </div>

            <div class="min-w-0">
                <label class="text-xs text-gray-500 font-medium ">Last Name </label>
                <div class="static-view text-sm text-gray-900">{{ $customer->last_name ?? '' }}</div>
                <!-- <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="{{ $customer->last_name ?? '' }}" /> -->

                {!! html()->text('last_name', old('last_name', $customer->last_name ?? ''))
                ->class([
                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                'border-red-500' => $errors->has('last_name'),
                ])
                ->attributes([

                'placeholder' => 'Enter Last Name',
                'id' => 'last_name',
                'autocomplete' => 'off',
                ])
                !!}

            </div>

            <div class="min-w-0">
                <label class="text-xs text-gray-500 font-medium required">Email Address </label>
                <div class=" static-view">
                    <p class="text-gray-900 flex items-center gap-1 text-sm">
                        <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />{{ $customer->email ?? '' }}
                    </p>
                </div>
                <!-- Edit View -->
                <!-- <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" type="email" value="{{ $customer->email ?? '' }}" /> -->

                {!! html()->email('email', old('email', $customer->email ?? ''))
                ->class([
                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                'border-red-500' => $errors->has('email'),
                ])
                ->attributes([

                'placeholder' => 'Enter Email',
                'id' => 'email',
                'autocomplete' => 'off',
                'data-parsley-type' => 'email',
                'data-parsley-trigger' => 'change',
                'data-parsley-remote' => route('admin.crm.customers.check.email.unique', [
                'except' => $customer->id ?? null,
                ]),
                'data-parsley-remote-validator' => 'customemailcheck',
                'data-parsley-remote-message' => 'This email is already taken by another user.',
                ])
                ->required() !!}

            </div>

            <div class="min-w-0">
                <label class="text-xs text-gray-500 font-medium required">Phone Number </label>
                <div class=" static-view">
                    <p class="text-gray-900 flex items-center gap-1 text-gray-900">
                        <x-heroicon-o-phone class="w-4 h-4 text-gray-900" /> {{ App\Helpers\CustomHelper::formatPhone($customer->phone) ?? 'N/A' }}
                    </p>
                </div>
                <!-- Edit View -->
                <!-- <input class="masked-phone edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="{{ $customer->phone ?? '' }}" /> -->

                {!! html()->text('phone', old('phone', $customer->phone ?? ''))
                ->class([
                'masked-phone edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                'border-red-500' => $errors->has('phone'),
                ])
                ->attributes([
                'maxlength' => 14,
                'placeholder' => '(xxx) xxx-xxxx',
                'id' => 'phone',
                'autocomplete' => 'tel',
                'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                ])
                ->required() !!}


            </div>

        </div>
    </div>

    <!-- Company Information -->
    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200">
        <h3 class="text-md font-semibold mb-4 flex items-center gap-1">
            <x-heroicon-o-building-office class="w-5 h-5 text-gray-900" /> Company Information
        </h3>
        <div class="grid grid-cols-1 gap-4 text-sm text-gray-700">
            <div>
                <label class="text-xs text-gray-500 font-medium ">Company Name </label>
                <div class="static-view text-sm text-gray-900">{{ $customer->company_name ?? 'N/A' }}</div>
                <!-- <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="{{ $customer->company_name ?? '' }}" /> -->

                {!! html()->text('company_name', old('company_name', $customer->company_name ?? ''))
                ->class([
                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                'border-red-500' => $errors->has('company_name'),
                ])
                ->attributes([

                'placeholder' => 'Enter Company Name',
                'id' => 'company_name',
                ])
                !!}


            </div>

            <div>
                <label class="text-xs text-gray-500 font-medium">Company Phone</label>
                <div class=" static-view">
                    <p class=" text-gray-900 text-sm flex items-center gap-1">
                        <x-heroicon-o-phone class="w-4 h-4 text-gray-900" /> {{ App\Helpers\CustomHelper::formatPhone($customer->company_phone) ?? 'N/A' }}
                    </p>
                </div>
                <!-- Edit View -->
                <!-- <input class="masked-phone edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="{{ $customer->company_phone ?? '' }}" /> -->


                {!! html()->text('company_phone', old('company_phone', $customer->company_phone ?? ''))
                ->class([
                'masked-phone edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                'border-red-500' => $errors->has('company_phone'),
                ])
                ->attributes([
                'maxlength' => 14,
                'placeholder' => '(xxx) xxx-xxxx',
                'id' => 'company_phone',
                'autocomplete' => 'tel',
                'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                ]) !!}



            </div>

            <div class="col-span-2">
                <label class="text-xs text-gray-500 font-medium">Website</label>
                <div class=" static-view">
                    <a href="{{ $customer->company_website ?? 'javascript:void(0)' }}"
                        class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                        <x-heroicon-o-globe-alt class="w-4 h-4" />
                        {{ !empty($customer->company_website) ? $customer->company_website : 'N/A' }}
                    </a>
                </div>
                <div class="flex gap-2 mb-4">

                    {{-- Protocol --}}
                    {!! html()->select('website_protocol', [
                    'https://' => 'https://',
                    'http://' => 'http://',
                    ], old('website_protocol', $website_protocol ?? null))
                    ->class([
                    'edit-view w-2/6 border rounded-md lg:px-1 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('website_protocol'),
                    'border-gray-300' => !$errors->has('website_protocol'),
                    ])
                    ->id('website_protocol') !!}


                    {{-- Website Name --}}
                    {!! html()->text('company_website', old('company_website',$company_website ?? null))
                    ->class([
                    'edit-view w-4/6 border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
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
                    ], old('website_extension', $website_extension ?? null))
                    ->class([
                    'edit-view w-2/6 border rounded-md lg:px-1 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('website_extension'),
                    'border-gray-300' => !$errors->has('website_extension'),
                    ])
                    ->id('website_extension') !!}

                </div>


            </div>
        </div>
    </div>
</div>
@php
$billingAddress = $customer->addresses->where('is_primary', 1)->firstWhere(fn ($a) => $a->type === 'Billing' );
$shippingAddress = $customer->addresses->where('is_primary', 1)->firstWhere(fn ($a) => $a->type === 'Shipping');

$defaultAddresses = [
['label' => 'Billing', 'data' => $billingAddress],
['label' => 'Shipping', 'data' => $shippingAddress],
];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 mb-0">
    @foreach ($defaultAddresses as $index => $addressItem)
    @php $addresse = $addressItem['data']; @endphp
    <div class="address-block bg-white rounded-lg shadow-sm p-5 border border-gray-200" data-index="{{ $index }}">
        <input type="hidden" name="addresses[{{ $index }}][address_id]" value="{{ $addresse->id ?? '' }}" class="address_id">
        <input type="hidden" name="addresses[{{ $index }}][type]" value="{{ $addressItem['label'] }}" class="type">
        <input type="hidden" name="addresses[{{ $index }}][is_primary]" value="{{ $addresse?->is_primary ? 1 : 0 }}" class="is_primary_input">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">

            <h3 class="text-base font-semibold flex items-center flex-wrap gap-2 text-gray-900">
                <x-heroicon-o-map-pin class="w-5 h-5 text-gray-900 flex-shrink-0" />
                {{ ($addressItem['label']=='Shipping' ? 'Delivery' : $addressItem['label']) }} Address
                @if ($addresse && $addresse->is_primary)
                <span class="text-xs bg-red-100 text-red-600 font-normal px-2 py-0.5 rounded">
                    Default Address
                </span>
                @endif
            </h3>

            @if ($addressItem['label']=='Shipping')
            <!-- Checkbox -->
            <label class="edit-view flex items-center gap-2 text-sm text-gray-700 cursor-pointer font-semibold">
                <input type="checkbox" id="sameAsBilling" name="sameAsBilling"
                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    {{ old('sameAsBilling', $customer->same_as_billing ?? 0) ? 'checked' : '' }}>
                <span>Same as billing address</span>
            </label>
            @endif
        </div>




        <div class="space-y-4">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="edit-view">
                    <label class="text-xs text-gray-500 font-medium ">First Name </label>
                    {!! html()->text("addresses[$index][first_name]", old("addresses.$index.first_name", $addresse->first_name ?? ''))
                    ->class([
                    'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                    'border-red-500' => $errors->has("addresses.$index.first_name"),
                    ])
                    ->attributes([
                    'placeholder' => 'Enter First Name',
                    'class' => 'first_name'
                    ]) !!}
                </div>
                <div class="edit-view">
                    <label class="text-xs text-gray-500 font-medium ">Last Name </label>
                    {!! html()->text("addresses[$index][last_name]", old("addresses.$index.last_name", $addresse->last_name ?? ''))
                    ->class([
                    'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                    'border-red-500' => $errors->has("addresses.$index.last_name"),
                    ])
                    ->attributes([

                    'placeholder' => 'Last Name',
                    'class' => 'last_name'
                    ]) !!}
                </div>
            </div>

            <div>
                <label class="text-xs text-gray-500 font-medium edit-view ">Address </label>
                <div class="static-view text-gray-900 text-sm">

                    @php
                    // Collect all non-empty fields except country
                    $mainParts = array_filter([
                    $addresse?->address,
                    $addresse?->city,
                    $addresse?->state?->name,
                    $addresse?->zip_code,
                    ]);

                    // Add country only if there is at least one other part
                    $parts = $mainParts;
                    if (!empty($mainParts) && !empty($addresse->country)) {
                    $parts[] = $addresse->country;
                    }
                    @endphp

                    <p>{{ implode(', ', $parts) ?: 'No address provided.' }}</p>


                </div>
                {!! html()->text("addresses[$index][address]", old("addresses.$index.address", $addresse->address ?? ''))
                ->class([
                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                'border-red-500' => $errors->has("addresses.$index.address"),
                ])
                ->attributes([
                'placeholder' => 'Enter Address',
                'class' => 'address'
                ]) !!}
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="edit-view">
                    <label class="text-xs text-gray-500 font-medium ">City </label>
                    {!! html()->text("addresses[$index][city]", old("addresses.$index.city", $addresse->city ?? ''))
                    ->class([
                    'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                    'border-red-500' => $errors->has("addresses.$index.city"),
                    ])
                    ->attributes([
                    'placeholder' => 'Enter City',
                    'class' => 'city'
                    ]) !!}
                </div>
                <div class="edit-view">
                    <label class="text-xs text-gray-500 font-medium ">Zip Code </label>
                    {!! html()->text("addresses[$index][zip_code]", old("addresses.$index.zip_code", $addresse->zip_code ?? ''))
                    ->class([
                    'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                    'border-red-500' => $errors->has("addresses.$index.zip_code"),
                    ])
                    ->attributes([
                    'maxlength' => 8,
                    'placeholder' => 'ZIP Code',
                    'class' => 'zip_code'
                    ]) !!}
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <div class="edit-view">
                    <label class="text-xs text-gray-500 font-medium ">State </label>
                    {!! html()
                    ->select("addresses[$index][state_id]",
                    ['' => '-- Select State --'] + $states->pluck('name', 'id')->toArray(),
                    old("addresses.$index.state_id", $addresse->state_id ?? '')
                    )
                    ->class([
                    'pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm state_id',
                    'border-red-500' => $errors->has("addresses.$index.state_id"),
                    ]) !!}
                </div>

                <div class="edit-view">
                    <label class="text-xs text-gray-500 font-medium ">Country </label>

                    {!! html()->select("addresses[$index][Country]", [
                    'USA' => 'USA',
                    'Canada' => 'Canada',
                    'UK' => 'UK',
                    'India' => 'India',
                    ], old("addresses.$index.Country", $addresse->country ?? 'USA')
                    ) // default to USA if old or user value is not set
                    ->class('pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm Country')
                    ->attributes([
                    'autocomplete' => 'off',
                    ])

                    !!}

                </div>

                <div class="edit-view">
                    <label class="text-xs text-gray-500 font-medium ">Phone Number </label>


                    {!! html()->text("addresses[$index][phone]", old("addresses.$index.phone", $addresse->phone ?? ''))
                    ->class([
                    'masked-phone edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                    'border-red-500' => $errors->has('phone'),
                    ])
                    ->attributes([
                    'maxlength' => 14,
                    'placeholder' => '(xxx) xxx-xxxx',
                    'class' => 'phone',
                    'autocomplete' => 'tel',
                    'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                    'data-parsley-error-message' => 'Please enter phone number in <br> format (xxx) xxx-xxxx',
                    ]) !!}

                </div>



            </div>
        </div>
    </div>
    @endforeach
</div>


<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 items-stretch">

    <div class=" flex flex-col h-full">
        <!-- Tags and Notes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-grow">
            <!-- Tags -->
            <div class="edit-view bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col">
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

            <!-- Tags -->
            <div class="static-view bg-white rounded-lg shadow border border-gray-200 p-6 ">
                <div class="">
                    <div class="flex items-center justify-between mb-2">
                        <label for="ContactTags" class="block text-sm font-medium text-gray-700">Tags</label>
                    </div>

                    <!-- <label for="ContactTags" class="block text-sm font-medium text-gray-700">Tags</label> -->
                    <div class="flex flex-wrap gap-2">
                        @foreach ($customer->tag_objects as $tag)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 mr-1">
                                <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                                <path d="M7 7h.01"></path>
                            </svg>
                            #{{ $tag->name }}
                        </span>
                        @endforeach
                    </div>

                </div>

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
            <div class="md:col-span-2 mt-2 flex flex-col flex-grow">
                <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Funnels</label>
                <div
                    class="border border-gray-300 rounded-md flex-grow flex items-center justify-center text-gray-400 text-sm bg-gray-50 hover:border-blue-400 hover:text-gray-700 transition-all">
                    Add or assign funnels here
                </div>
            </div>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 mb-0">
    <!-- Account Status -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-base font-semibold mb-4 flex items-center gap-1">
            <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
            Account Status
        </h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between items-center">
                <span class="text-xs text-gray-500 font-medium">Account Status:</span>

                @php
                $approved = $customer->is_credit_account == 1;
                $hasCreditLimit = !empty($customer->credit_limit);
                @endphp

                @if ($approved && $hasCreditLimit)
                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">Good Standing</span>

                @elseif ($approved && !$hasCreditLimit)
                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs font-medium">Pending</span>
                @else
                N/A
                @endif

            </div>
            <div class="flex justify-between items-center">
                <span class="text-xs text-gray-500 font-medium">Account Approved:</span>
                <div class="static-view">
                    @if($customer->is_credit_account == 1)

                    <span class="text-green-600 font-medium flex items-center gap-1 text-xs">
                        <x-heroicon-o-check-circle class="w-4 h-4 text-green-600" />
                        Approved
                    </span>
                    @else
                    <span class="text-red-600 font-medium flex items-center gap-1 text-xs">
                        <x-heroicon-o-x-circle class="w-4 h-4 text-red-600" />
                        Not Approved
                    </span>
                    @endif
                </div>

                {!! html()
                ->select('is_credit_account', [
                '0' => 'Not Approved',
                '1' => 'Approved',
                ], old('is_credit_account', $customer->is_credit_account ?? ''))
                ->id('is_credit_account')
                ->class([
                'edit-view border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 border-gray-300 ',
                ])
                !!}


            </div>
            <div class="flex justify-between">
                <span class="text-xs text-gray-500 font-medium">Customer Since:</span>
                <span class="text-xs">{{ App\Helpers\CustomHelper::formatDate($customer->created_at) ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-xs text-gray-500 font-medium">Customer ID:</span>
                <span class="text-xs">{{ $customer->unique_id }}</span>
            </div>
        </div>
    </div>

    <!-- Credit Information -->
    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200">
        <div class="flex gap-2 items-start mb-4">
            <h3 class="text-base font-semibold flex items-center gap-1">
                <x-heroicon-o-credit-card class="w-5 h-5 text-gray-900" />
                Credit Information
            </h3>
            <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded">Admin View</span>
        </div>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between items-center">


                <span class="text-xs text-gray-500 font-medium">Credit Limit:</span>
                <span class="text-right static-view">
                    <span class="text-gray-900 font-semibold">

                        {{ \App\Helpers\CustomHelper::formatCurrency($customer->credit_limit) }}

                    </span>
                    <a href="#" class="ml-1 text-blue-500 text-xs inline-flex items-center"><x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /></a>
                </span>




                @php
                $creditOptions = collect(range(1000, 20000, 1000))->mapWithKeys(function ($value) {
                return [$value => number_format($value)];
                });
                @endphp

                {!! html()
                ->select('credit_limit', $creditOptions->toArray(), old('credit_limit', $customer->credit_limit ?? ''))
                ->id('credit_limit')
                ->class([
                'border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 border-gray-300 edit-view',
                ])->placeholder('Select Credit Limit')
                !!}

            </div>

            <div class="flex justify-between items-center">
                <span class="text-xs text-gray-500 font-medium">Current Balance:</span>

                <span class="font-medium ">


                    {{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance) }}


                    <a href="#" class="text-xs font-normal text-green-500 ml-1">Adjust</a></span>




            </div>
            <div class="flex justify-between items-center">
                <span class="text-xs text-gray-500 font-medium">Available Credit:</span>
                <span class="text-green-600 font-medium ">

                    {{ \App\Helpers\CustomHelper::formatCurrency(\App\Helpers\CustomHelper::getAvailableCredit($customer)) }}

            </div>
            <div class="static-view">


                @php
                $climit = $customer->credit_limit ?? 0;
                $available = \App\Helpers\CustomHelper::getAvailableCredit($customer);


                $per = $climit > 0 ? ((($climit - $available) / $climit) * 100) : 0;
                @endphp
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <label class="text-gray-700 mb-1 text-xs">Credit Utilization</label>
                    <div class="text-right text-xs text-gray-500 mt-0.5">{{round($per)}}%</div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                    <div class="bg-blue-500 h-2 rounded-full" style="width:{{round($per)}}%; max-width: 100%;"></div>
                </div>



            </div>
        </div>
    </div>

</div>

<div class="bg-white p-4 rounded-lg shadow border border-gray-200 mt-6 mb-0">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Left: Tax Info -->
        <div class="space-y-2 text-sm text-gray-700">
            <div class="flex items-center gap-1">
                <x-heroicon-o-document class="w-5 h-5 text-gray-900" />
                <h2 class="text-base font-semibold text-gray-800">Tax Exempt Status</h2>
                <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded">Admin Control</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-xs text-gray-500 font-medium">Tax Status:</span>
                <!-- <span class="text-green-700 bg-green-100 px-2 py-1 rounded-full text-xs font-medium">{{ $customer->tax_status ?? 'N/A' }}</span> -->

                <div class="static-view">
                    <div class="inline-flex items-center gap-2">
                        <span class="text-xs font-medium rounded-full bg-green-100 text-green-800">
                            <span class="leading-[1.2] inline-flex items-center gap-2 px-2 py-1">
                                @if ($customer->tax_status === 'Exempt')
                                <x-heroicon-o-shield-check class="w-5 h-5 text-green-500" />
                                @else
                                <x-heroicon-o-shield-check class="w-5 h-5 text-gray-500" />
                                @endif
                                {{ $customer->tax_status ?? 'N/A' }}
                            </span>
                        </span>
                    </div>
                </div>

                {!! html()
                ->select('tax_status', [
                'Taxable' => 'Taxable',
                'Exempt' => 'Exempt',
                ], old('tax_status', $customer->tax_status ?? ''))
                ->id('tax_status')
                ->class([
                'edit-view border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 border-gray-300',
                ])
                !!}

            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-xs text-gray-500 font-medium">Valid Until:</span>


                <div class="static-view">
                    <span class="text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) ?? 'N/A' }}</span>
                </div>

                <div class="edit-view">
                    <input
                        class="w-full sm:w-32 md:w-32 border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 bg-white datepicker border-gray-300"
                        value="{{ \App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until ?? null) }}"
                        type="text"
                        name="tax_document_valid_until"
                        id="tax_document_valid_until"
                        placeholder="MM-DD-YYYY"
                        autocomplete="off" />
                </div>



            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-xs text-gray-500 font-medium">Uploaded:</span>

                <div class="static-view">
                    <span class="text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) ?? 'N/A' }}</span>
                </div>
                <div class="edit-view">
                    <input
                        class="w-full sm:w-32 md:w-32 border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 bg-white datepicker border-gray-300"
                        type="text"
                        value="{{ \App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date ?? null) }}"
                        name="tax_document_upload_date"
                        id="tax_document_upload_date"
                        placeholder="MM-DD-YYYY"
                        autocomplete="off" />
                </div>
            </div>
        </div>


        <div class="flex flex-col items-start md:items-end gap-2 text-left w-auto">
            <div class="md:text-right mb-4">
                <a href="javascript:void(0)" id="opentaxdocModal"
                    class="edit-view px-4 mt-3 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                    Manage Tax Documents
                </a>
            </div>

            <div id="taxDocPreviewWrapper"  class="w-full sm:w-auto overflow-x-auto">
                @if ($customer->media)
                @include('admin.crm.customers.partials.tax_doc_preview', ['customer' => $customer])
                @else
                <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 text-sm p-4 rounded-md">
                    No tax document has been uploaded yet.
                </div>
                @endif
            </div>
        </div>



    </div>
</div>

{{ html()->form()->close() }}

<!--taxdocModalWrapper Wrapper -->
<div id="taxdocModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Upload Tax Exempt Document</h2>
                </div>
                <button type="button" id="closetaxdocBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" p-6 overflow-y-auto">
                <div class="max-w-md mx-auto">
                    <!-- <form id="taxDocForm" enctype="multipart/form-data"> -->

                    {{-- Open Form --}}
                    {!! html()->form()
                    ->id('taxDocForm')
                    ->attribute('enctype', 'multipart/form-data')
                    ->attribute('autocomplete', 'off')
                    ->attribute('data-parsley-validate', true)
                    ->class('space-y-8')
                    ->open()
                    !!}

                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">

                    <!-- Upload Box -->
                    <div class="border-2 border-dashed border-gray-300 p-6 text-center rounded">
                        <div id="uploadUI" class="flex flex-col items-center justify-center">
                            <!-- Icon -->
                            <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 0L8 8m4-4l4 4" />
                            </svg>
                            <!-- Text -->
                            <p class="text-gray-600 text-sm mb-1">Click to upload or drag and drop</p>
                            <p class="text-gray-400 text-xs">PDF, JPG, PNG files up to 10MB</p>

                            <!-- File input -->
                            <label id="chooseFileLabel" class="mt-3 inline-block cursor-pointer">
                                <input type="file" id="tax_document" name="tax_document" class="hidden" accept=".pdf,.png,.jpg,.jpeg" onchange="handleFileChange(event)" required>
                                <span class="bg-blue-600 text-white px-4 py-1 rounded text-sm">Choose File</span>
                            </label>
                        </div>

                        <!-- File Display After Selection -->
                        <div id="fileActions" class="hidden text-sm mt-3 text-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4">
                            <!-- File Name -->
                            <span id="fileNameDisplay" class="font-medium text-center sm:text-left"></span>

                            <!-- Buttons: View & Close -->
                            <div class="flex justify-center sm:justify-start gap-2">
                                <a id="viewFileLink" href="#" target="_blank"
                                    class="bg-blue-600 text-white px-3 py-1 rounded text-sm">View</a>
                                <button type="button" onclick="clearFile()" class="px-3 py-1 text-sm rounded bg-red-600 text-white">✕</button>
                            </div>
                        </div>
                    </div>
                    {{-- Document Type Dropdown --}}
                    <div class="mt-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                        {!! html()->select('tax_document_type', [
                        '' => '-- Select Document Type --',
                        'Tax Exempt Certificate' => 'Tax Exempt Certificate',
                        'Resale Certificate' => 'Resale Certificate',
                        'Non-Profit Exemption' => 'Non-Profit Exemption'
                        ])
                        ->class('w-full border border-gray-300 rounded px-3 py-2 text-sm')->required()
                        !!}
                    </div>

                    <!-- Buttons -->
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" id="cancelBtntaxdoc" class="px-4 py-2 border border-gray-300 rounded text-sm">Cancel</button>
                        <button type="submit" class="saveBtntax px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-teal-700">Upload Document</button>
                    </div>

                    {!! html()->form()->close() !!}


                </div>
            </div>
        </div>
    </div>
</div>


<!-- Reset Password Modal Wrapper -->
<div id="resetPasswordModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center">
                    <div class="text-yellow-500 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-lock w-4 h-4 mr-2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Reset Password</h2>
                </div>
                <button id="closeResetPasswordModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto">


                {{ html()->form()->attributes([
    'class' => 'space-y-5',
    'method' => 'POST',
    'id' => 'resetPasswordForm',
    'autocomplete' => 'off',
    'data-parsley-validate' => true,
    ])->open() }}

                @csrf
                @method('POST')

                {{ html()->hidden('customer_id', $customer->id) }}

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        {{ html()->password('password')->attributes([
                    'class' => 'pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                    'required' => true,
                    'id' => 'password',
                    'data-parsley-minlength' => '6',
                    'data-parsley-minlength-message' => 'Password must be at least 6 characters.',

                ])->placeholder('') }}
                        <button type="button" onclick="toggleVisibility('password', this)" class="absolute inset-y-0 h-[35px] right-3 pl-3 flex items-center text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                        <div id="password-errors" class="mt-1 text-sm text-red-600"></div>
                        @error('password')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Confirmation</label>
                    <div class="relative">
                        {{ html()->password('password_confirmation')->attributes([
                'class' => 'pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                'required' => true,
                'id' => 'password_confirmation',
                'data-parsley-equalto' => '#password',
            ])->placeholder('') }}
                        <button type="button" onclick="toggleVisibility('password_confirmation', this)" class="absolute inset-y-0 h-[35px] right-3 pl-3 flex items-center text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pb-4">
                    <button type="button" id="cancelResetPasswordBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                        Update Password
                    </button>
                </div>

                {{ html()->form()->close() }}

            </div>
        </div>
    </div>
</div>




<!-- Hidden Delete Form -->
<form id="delete-media-form-{{ $customer->id }}"
    method="POST"
    action="{{ route('admin.crm.customers.tax-document.delete', $customer->unique_id) }}"
    style="display: none;">
    @csrf
    @method('DELETE')
</form>
<form id="suspend-customer-form-{{ $customer->id }}"
    method="POST"
    action="{{ route('admin.crm.customers.status-update.customer', $customer->unique_id) }}"
    style="display: none;">
    <input type="hidden" name="cstatus" id="cstatus" value="">
    @csrf
    @method('POST')
</form>





<!-- Notes Modal -->
<div id="notesModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-sm flex flex-col max-h-full overflow-hidden border border-gray-200">

            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900" id="noteModalTitle">Add Note</h2>
                <button type="button" onclick="closeNotesModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto max-h-[70vh] mt-5 mb-5">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">User</label>

                    {!! html()->select(
                    'user_id',
                    $employees->pluck('full_name', 'id')->toArray()
                    )->id('user_id')->class([
                    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                    ]) !!}

                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Note</label>
                    <textarea id="note_text" rows="5"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500"
                        placeholder="Enter note..."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 pb-4 px-4 border-t border-gray-200">
                <button type="button" onclick="closeNotesModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white">Close</button>
                <button type="button" id="saveNoteBtn" class="px-4 py-2 text-sm rounded border border-grey-300 bg-blue-600 text-white">Save</button>
            </div>
        </div>
    </div>
</div>



@push('js')

<script>
    function toggleVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        const svg = btn.querySelector('svg');
        svg.innerHTML = isPassword ?
            `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.042 10.042 0 013.03-4.362M6.873 6.876A9.953 9.953 0 0112 5c4.477 0 8.267 2.943 9.541 7a9.966 9.966 0 01-1.249 2.527M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M3 3l18 18" />` :
            `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
    }
</script>

<script>
    window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));
</script>
<script>
    function confirmAndDelete(customerId) {
        window.showConfirm(
            `Are you sure you want to delete this document? This action cannot be undone!`,
            'Delete Document'
        ).then((result) => {
            if (!result.isConfirmed) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const deleteUrl = "{{ route('admin.crm.customers.tax-document.delete', ['unique_id' => $customer->unique_id]) }}";

            fetch(deleteUrl, {
                    method: 'POST', // ✅ still POST — we send a fake DELETE method inside
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        _method: 'DELETE'
                    }) // ✅ Laravel interprets this as DELETE
                })
                .then(async res => {
                    const contentType = res.headers.get("content-type");
                    if (contentType && contentType.includes("application/json")) {
                        return res.json();
                    } else {
                        const text = await res.text();
                        console.error('Non-JSON response:', text);
                        throw new Error('Unexpected response format');
                    }
                })
                .then(data => {
                    if (data.success) {
                        notyf.success('Tax document deleted successfully');
                        const wrapper = document.getElementById('taxDocPreviewWrapper');
                        wrapper.classList.add('opacity-0');
                        setTimeout(() => {
                            wrapper.innerHTML = `
                        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 text-sm p-4 rounded-md">
                            No tax document has been uploaded yet.
                        </div>
                    `;
                            wrapper.classList.remove('opacity-0');
                        }, 300);
                    } else {
                        notyf.error(data.message || 'Failed to delete document');
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                    notyf.error('Error deleting document');
                });
        });
    }

    function confirmAndSuspend(id) {
        if (confirm("Are you sure you want to suspend this customer?")) {
            document.getElementById(`cstatus`).value = "Archived";
            document.getElementById(`suspend-customer-form-${id}`).submit();
        }
    }

    function confirmAndActive(id) {
        if (confirm("Are you sure you want to activate this customer?")) {
            document.getElementById(`cstatus`).value = "Active";
            document.getElementById(`suspend-customer-form-${id}`).submit();
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const resetModal = document.getElementById('resetPasswordModalWrapper');
        const openBtn = document.getElementById('openResetPasswordModal');
        const closeBtn = document.getElementById('closeResetPasswordModalBtn');
        const cancelBtn = document.getElementById('cancelResetPasswordBtn');

        const openModal = () => resetModal.style.display = 'flex';
        const closeModal = () => resetModal.style.display = 'none';

        openBtn?.addEventListener('click', openModal);
        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);

        // resetModal?.addEventListener('click', (e) => {
        //     if (e.target === resetModal) closeModal();
        // });
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

<script>
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = e.target;


        if (!$(form).parsley().isValid()) {
            return; // stop if validation fails
        }


        const formData = new FormData(form);


        fetch("{{ route('admin.crm.customers.password.update') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notyf.success(data.message || "Password updated.");
                    document.getElementById('resetPasswordModalWrapper').style.display = 'none';
                } else {
                    notyf.error(data.message || "Password reset failed.");
                }
            })
            .catch(error => {
                console.error("AJAX error:", error);
                notyf.error("Something went wrong.");
            });
    });
</script>

<script>
    function bindTaxStatusButtons() {
        document.querySelectorAll('.approve-btn, .reject-btn').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                const status = this.classList.contains('approve-btn') ? 'Approved' : 'Rejected';

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
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const statusSpan = document.getElementById(`tax-status-${customerId}`);
                            statusSpan.textContent = status;
                            statusSpan.className = `font-medium ${
                        status === 'Approved' ? 'text-green-600' :
                        status === 'Rejected' ? 'text-red-600' :
                        'text-yellow-600'
                    }`;

                            if (status === 'Approved') {
                                document.querySelectorAll(`[data-customer-id="${customerId}"].approve-btn`).forEach(btn => btn.style.display = 'none');
                                document.querySelectorAll(`[data-customer-id="${customerId}"].reject-btn`).forEach(btn => btn.style.display = 'none');
                            }

                            notyf.success(data.message || "Status updated successfully.");
                        } else {
                            notyf.error(data.message || "Failed to update status.");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        notyf.error("Something went wrong while updating status.");
                    });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', bindTaxStatusButtons);
</script>

<script>
    document.querySelectorAll('.tax-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const customerId = this.dataset.id;
            const status = this.dataset.status;

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
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update the status text and color
                        const span = document.getElementById(`tax-status-${customerId}`);
                        span.textContent = status;
                        span.className = `font-medium ${
                        status === 'Approved' ? 'text-green-600' : 'text-red-600'
                    }`;

                        notyf.success(data.message || "Status updated.");
                    } else {
                        notyf.error(data.message || "Update failed.");
                    }
                })
                .catch(error => {
                    console.error(error);
                    notyf.error("Something went wrong.");
                });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('taxdocModalWrapper');
        const openBtn = document.getElementById('opentaxdocModal');
        const closeBtn = document.getElementById('closetaxdocBtn');
        const cancelBtn = document.getElementById('cancelBtntaxdoc');
        const form = document.getElementById('taxDocForm');
        const fileInput = document.getElementById('tax_document');


        function resetTaxDocModalForm() {
            form.reset();

            document.getElementById('fileActions').style.display = 'none';
            document.getElementById('fileNameDisplay').textContent = '';
            document.getElementById('viewFileLink').href = '#';
            document.getElementById('uploadUI').style.display = 'flex';

            const docTypeSelect = form.querySelector('[name="tax_document_type"]');
            if (docTypeSelect) docTypeSelect.value = '';
        }


        // Modal open/close
        const openModal = () => modalWrapper.style.display = 'flex';
        const closeModal = () => modalWrapper.style.display = 'none';

        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        // modalWrapper.addEventListener('click', (e) => {
        //     if (e.target === modalWrapper) closeModal();
        // });


        // Handle AJAX form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Check if form is valid using Parsley
            if (!$(form).parsley().validate()) {
                return; // Stop submission if validation fails
            }

            const formData = new FormData(form);
            const uploadBtn = form.querySelector('.saveBtntax');
            uploadBtn.disabled = true;
            uploadBtn.textContent = "Uploading...";

            try {
                const response = await fetch("{{ route('admin.crm.customers.taxdoc.upload') }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    modalWrapper.style.display = 'none';

                    resetTaxDocModalForm();


                    const wrapper = document.getElementById('taxDocPreviewWrapper');
                    wrapper.innerHTML = result.html;

                    // Update upload date field
                    const uploadDateInput = document.getElementById('tax_document_upload_date');
                    if (uploadDateInput && result.upload_date) {
                        uploadDateInput.value = result.upload_date;
                    }

                    bindTaxStatusButtons();


                    notyf.success(result.message || 'Uploaded successfully');
                } else {
                    // Handle validation errors
                    if (result.errors) {
                        Object.values(result.errors).forEach(messages => {
                            messages.forEach(message => notyf.error(message));
                        });
                    } else {
                        notyf.error(result.message || "Upload failed.");
                    }
                }
            } catch (error) {
                console.error("Upload error:", error);
                notyf.error("Something went wrong.");
            }

            uploadBtn.disabled = false;
            uploadBtn.textContent = "Upload Document";
        });

    });
</script>

<!-- Tags Notes Assign to Funnels -->


<!-- this is for appear tags her form choice js  -->

<script>
    function openNotesModal() {
        document.getElementById('notesModal').classList.remove('hidden');

    }

    function closeNotesModal() {
        document.getElementById('notesModal').classList.add('hidden');
    }
</script>

<!-- notes  -->

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const notesModal = document.getElementById('notesModal');
        const noteText = document.getElementById('note_text');
        const userSelect = document.getElementById('user_id');
        const noteList = document.getElementById('noteList');
        const addNoteBtn = document.getElementById('addNoteBtn');
        const saveNoteBtn = document.getElementById('saveNoteBtn');
        const modalTitle = document.getElementById('noteModalTitle');


        let notes = [];
        let editNoteId = null;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        const updateUrlTemplate = `{{ route('admin.crm.customers.notes.update', ['note' => 'NOTE_ID_PLACEHOLDER']) }}`;
        const deleteUrlTemplate = `{{ route('admin.crm.customers.notes.delete', ['note' => 'NOTE_ID_PLACEHOLDER']) }}`;

        // Fetch all notes (from backend API)
        function fetchNotes() {
            fetch(`{{ route('admin.crm.customers.notes.fetch', $customer->id ?? 0) }}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        notes = data.notes || [];
                        renderNotes();
                    } else {
                        notyf.error("Failed to fetch notes");
                    }
                })
                .catch(() => notyf.error("Error fetching notes"));
        }

        //  Open Add Note modal
        addNoteBtn.addEventListener('click', () => {
            modalTitle.textContent = "Add Note";
            noteText.value = "";
            userSelect.selectedIndex = 0;
            editNoteId = null;
            notesModal.classList.remove('hidden');
        });

        //  Close modal
        window.closeNotesModal = function() {
            notesModal.classList.add('hidden');
        };

        //  Save note (create or update)
        saveNoteBtn.addEventListener('click', () => {
            const text = noteText.value.trim();
            const userId = userSelect.value;

            if (!text || !userId) {
                notyf.error('Please fill all fields.');
                return;
            }

            const payload = {
                text,
                user_id: userId
            };

            if (editNoteId) {
                const url = updateUrlTemplate.replace('NOTE_ID_PLACEHOLDER', editNoteId);
                fetch(url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            notyf.success('Note updated successfully');
                            fetchNotes();
                            closeNotesModal();
                        } else {
                            notyf.error(data.message || 'Failed to update note');
                        }
                    })
                    .catch(() => notyf.error('Error updating note'));
            } else {
                fetch(`{{ route('admin.crm.customers.notes.store', $customer->id ?? 0) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            notyf.success('Note added successfully');
                            fetchNotes();
                            closeNotesModal();
                        } else {
                            notyf.error(data.message || 'Failed to add note');
                        }
                    })
                    .catch(() => notyf.error('Error adding note'));
            }
        });

        //  Render Notes List
        function renderNotes() {
            if (notes.length === 0) {
                noteList.innerHTML = `<li class="text-gray-400 text-sm">No notes available.</li>`;

                return;
            }

            noteList.innerHTML = notes
                .map(note => `
                <li class="list-disc border-b border-gray-200 pb-3" data-id="${note.id}">
                    <div class="flex justify-between items-start">
                        <div class="flex-1 pr-3">
                            <p class="text-sm text-gray-800 leading-relaxed font-semibold">${note.text}</p>
                            <div class="mt-1 text-xs text-gray-500 space-y-1">
                                <div>Created ${note.created_at} by <span class="font-semibold">${note.user_name}</span></div>
                                ${note.updated_at ? `<div>Updated ${note.updated_at}</div>` : ""}
                            </div>
                        </div>
                        <div class="flex gap-2 mt-1">
                            <button type="button" class="text-blue-500 hover:text-blue-700 editNoteBtn" data-id="${note.id}" title="Edit">
                                <x-heroicon-o-pencil-square class="w-4 h-4" />
                            </button>
                            <button type="button" class="text-red-500 hover:text-red-700 deleteNoteBtn" data-id="${note.id}" title="Delete">
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </li>
            `)
                .join('');


        }

        //  Handle Edit / Delete
        noteList.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.editNoteBtn');
            const delBtn = e.target.closest('.deleteNoteBtn');

            // Edit
            if (editBtn) {
                const id = parseInt(editBtn.dataset.id);
                const note = notes.find(n => n.id === id);
                if (!note) return;

                modalTitle.textContent = "Edit Note";
                noteText.value = note.text;
                userSelect.value = note.user_id;
                editNoteId = id;
                notesModal.classList.remove('hidden');
            }

            // Delete

            if (delBtn) {
                const id = parseInt(delBtn.dataset.id);
                window.showConfirm('Delete this note?', 'Delete note').then(result => {
                    if (result.isConfirmed) {
                        const url = deleteUrlTemplate.replace('NOTE_ID_PLACEHOLDER', id);
                        fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken
                                },
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    notyf.success('Note deleted successfully');
                                    fetchNotes();
                                } else {
                                    notyf.error(data.message || 'Failed to delete note');
                                }
                            })
                            .catch(() => notyf.error('Error deleting note'));
                    }
                });
            }
        });

        //  Initial load
        fetchNotes();
    });
</script>


<!-- Sync fields ( first name , last name ) -->

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const firstName = document.querySelector('#first_name');
        const lastName = document.querySelector('#last_name');

        const billingFirst = document.getElementById('addresses[0][first_name]');
        const billingLast = document.getElementById('addresses[0][last_name]');
        const deliveryFirst = document.getElementById('addresses[1][first_name]');
        const deliveryLast = document.getElementById('addresses[1][last_name]');

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
            first_name: document.getElementById('addresses[0][first_name]'),
            last_name: document.getElementById('addresses[0][last_name]'),
            address: document.getElementById('addresses[0][address]'),
            city: document.getElementById('addresses[0][city]'),
            state: document.getElementById('addresses[0][state_id]'),
            zip: document.getElementById('addresses[0][zip_code]'),
            country: document.getElementById('addresses[0][Country]'),
            phone: document.getElementById('addresses[0][phone]'),
        };

        const deliveryFields = {
            first_name: document.getElementById('addresses[1][first_name]'),
            last_name: document.getElementById('addresses[1][last_name]'),
            address: document.getElementById('addresses[1][address]'),
            city: document.getElementById('addresses[1][city]'),
            state: document.getElementById('addresses[1][state_id]'),
            zip: document.getElementById('addresses[1][zip_code]'),
            country: document.getElementById('addresses[1][Country]'),
            phone: document.getElementById('addresses[1][phone]'),
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