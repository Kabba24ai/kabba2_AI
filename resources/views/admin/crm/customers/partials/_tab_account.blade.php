
                         {{ html()->form()->id('customerForm')->attributes([
                            'autocomplete' => 'off',
                            
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                         {!! html()->text('alladdresslist')->class('hidden')->attributes([
                            'id' => 'alladdresslist',
                            'autocomplete' => 'off'
                        ]) !!}



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
                                <button onclick="confirmAndSuspend({{ $customer->id }})"  type="button" class="bg-red-600 text-white px-4 py-2 rounded text-sm static-view">Suspend Account</button>
                            @elseif($customer->status!='Active')
                                <button onclick="confirmAndActive({{ $customer->id }})"  type="button" class="bg-green-600 text-white px-4 py-2 rounded text-sm static-view">Active Account</button>
                            @endif
                            <button type="button" id="openResetPasswordModal" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm static-view">Reset Password</button>
                            <button id="editBtn" type="button" class="bg-blue-600 inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-md hover:bg-green-700 transition"> Edit Information</button>    
                            <button  type="submit" id="saveBtn" style="display:none;" class="saveBtn bg-green-600 text-white px-4 py-2 rounded text-sm">Save Changes</button>
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
                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-4 text-sm text-gray-700">

                        <div>
                            <label class="text-xs text-gray-500 font-medium">First Name</label>
                            <div class="static-view text-sm text-gray-900">{{ $customer->first_name ?? '' }}</div>
                             <!-- <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="{{ $customer->first_name ?? '' }}" /> -->

                             {!! html()->text('first_name', old('first_name', $customer->first_name ?? ''))
                                ->class([
                                    'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                    'border-red-500' => $errors->has('first_name'),
                                ])
                                ->attributes([
                                    'maxlength' => 100,
                                    'placeholder' => 'Enter First Name',
                                    'id' => 'first_name',
                                    'autocomplete' => 'off',
                                ])
                                ->required() !!}


                        </div>

                        <div>
                            <label class="text-xs text-gray-500 font-medium">Last Name</label>
                            <div class="static-view text-sm text-gray-900">{{ $customer->last_name ?? '' }}</div>
                            <!-- <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="{{ $customer->last_name ?? '' }}" /> -->

                            {!! html()->text('last_name', old('last_name', $customer->last_name ?? ''))
                            ->class([
                                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                'border-red-500' => $errors->has('last_name'),
                            ])
                            ->attributes([
                                'maxlength' => 100,
                                'placeholder' => 'Enter Last Name',
                                'id' => 'last_name',
                                'autocomplete' => 'off',
                            ])
                            ->required() !!}

                        </div>

                        <div class="col-span-2">
                            <label class="text-xs text-gray-500 font-medium">Email Address</label>
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
                                'maxlength' => 100,
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

                        <div class="col-span-2">
                            <label class="text-xs text-gray-500 font-medium">Phone Number</label>
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
                                'placeholder' => '(123) 456-7890',
                                'id' => 'phone',
                                'autocomplete' => 'tel',
                                'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                                'data-parsley-error-message' => 'Please enter phone number in format (123) 456-7890',
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
                    <div class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                        <div class="col-span-2">
                            <label class="text-xs text-gray-500 font-medium">Company Name</label>
                            <div class="static-view text-sm text-gray-900">{{ $customer->company_name ?? 'N/A' }}</div>
                            <!-- <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="{{ $customer->company_name ?? '' }}" /> -->

                            {!! html()->text('company_name', old('company_name', $customer->company_name ?? ''))
                            ->class([
                                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                'border-red-500' => $errors->has('company_name'),
                            ])
                            ->attributes([
                                'maxlength' => 100,
                                'placeholder' => 'Enter Company Name',
                                'id' => 'company_name',
                            ])
                            ->required() !!}


                        </div>
                        
                        <div class="col-span-2">
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
                                'placeholder' => '(123) 456-7890',
                                'id' => 'company_phone',
                                'autocomplete' => 'tel',
                                'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                                'data-parsley-error-message' => 'Please enter phone number in format (123) 456-7890',
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
    $billingAddress = $customer->addresses->firstWhere(fn ($a) => $a->type === 'Billing' );
    $shippingAddress = $customer->addresses->firstWhere(fn ($a) => $a->type === 'Shipping');

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


            <h3 class="text-base font-semibold mb-4 flex items-center gap-1">
                <x-heroicon-o-map-pin class="w-5 h-5 text-gray-900" />
                {{ ($addressItem['label']=='Shipping' ? 'Delivery' : $addressItem['label']) }} Address
                @if ($addresse && $addresse->is_primary)
                    - <span class="text-xs bg-red-100 text-red-600 font-normal px-2 py-1 rounded">Default Address</span>  
                @endif
            </h3>

            <div class="space-y-4">

            <div class="grid grid-cols-2 gap-4">
                    <div class="edit-view">
                        <label class="text-sm text-gray-700 mb-1">First Name</label>
                        {!! html()->text("addresses[$index][first_name]", old("addresses.$index.first_name", $addresse->first_name ?? ''))
                            ->class([
                                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                'border-red-500' => $errors->has("addresses.$index.first_name"),
                            ])
                            ->attributes([
                                'placeholder' => 'Enter First Name',
                                'class' => 'first_name'
                            ])->required() !!}
                    </div>
                    <div class="edit-view">
                        <label class="text-sm text-gray-700 mb-1">Last Name </label>
                        {!! html()->text("addresses[$index][last_name]", old("addresses.$index.last_name", $addresse->last_name ?? ''))
                            ->class([
                                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                'border-red-500' => $errors->has("addresses.$index.last_name"),
                            ])
                            ->attributes([
                                'maxlength' => 10,
                                'placeholder' => 'Last Name',
                                'class' => 'last_name'
                            ])->required() !!}
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-700 mb-1 edit-view">Address</label>
                    <div class="static-view text-gray-900 text-sm">
                      {{ $addresse?->full_name ?? '' }}<br>
                                            {{ $addresse?->address ?? '' }}  {{ $addresse?->city ? ', ' . $addresse->city : '' }}<br>
                                            {{ $addresse?->state?->name ?? '' }}{{ $addresse?->zip_code ? ' ' . $addresse->zip_code : '' }}<br>

                {{ App\Helpers\CustomHelper::formatPhone(optional($addresse)->phone) ?? ' ' }}
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

                <div class="grid grid-cols-2 gap-4">
                    <div class="edit-view">
                        <label class="text-sm text-gray-700 mb-1">City</label>
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
                        <label class="text-sm text-gray-700 mb-1">Zip Code</label>
                        {!! html()->text("addresses[$index][zip_code]", old("addresses.$index.zip_code", $addresse->zip_code ?? ''))
                            ->class([
                                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                'border-red-500' => $errors->has("addresses.$index.zip_code"),
                            ])
                            ->attributes([
                                'maxlength' => 10,
                                'placeholder' => 'ZIP Code',
                                'class' => 'zip_code'
                            ]) !!}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                            <div class="edit-view">
                                <label class="text-sm text-gray-700 mb-1">State</label>
                                {!! html()
                                    ->select("addresses[$index][state_id]",
                                        $states->pluck('name', 'id')->toArray(),
                                        old("addresses.$index.state_id", $addresse->state_id ?? '')
                                    )
                                    ->class([
                                        'pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm state_id',
                                        'border-red-500' => $errors->has("addresses.$index.state_id"),
                                    ]) !!}
                            </div>

                            <div class="edit-view">
                                <label class="text-sm text-gray-700 mb-1">Phone Number</label>


                                 {!! html()->text("addresses[$index][phone]", old("addresses.$index.phone", $addresse->phone ?? ''))
                                    ->class([
                                        'masked-phone edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                        'border-red-500' => $errors->has('phone'),
                                    ])
                                    ->attributes([
                                        'maxlength' => 14,
                                        'placeholder' => '(123) 456-7890',
                                        'class' => 'phone',
                                        'autocomplete' => 'tel',
                                        'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                                        'data-parsley-error-message' => 'Please enter phone number in format (123) 456-7890',
                                    ])->required() !!}

                            </div>

                </div>                       
            </div>
        </div>
    @endforeach
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
                                'border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 border-gray-300  edit-view',
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
                                
                            

                            {{ \App\Helpers\CustomHelper::formatCurrency(($customer->credit_limit ?? 0) - ($customer->total_account_order_amount ?? 0)) }}



                        </div>
                        <div class="static-view">
                            
                           
                            @php
                                $climit = $customer->credit_limit ?? 0;
                                $available = $customer->total_account_order_amount ?? 0 ;

                                $available = $available ;

                                $per = $climit > 0 ? (($available / $climit) * 100) : 0;
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

            <div class="bg-white p-4 rounded shadow border border-gray-200 mt-6 mb-0">
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
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 font-medium">Valid Until:</span>


                              <div class="static-view">
                              <span class="text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) ?? 'N/A' }}</span>
                            </div>

                            <div class="edit-view">
                            <input
                                class="w-32 border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 bg-white datepicker border-gray-300"
                                value="{{ \App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until ?? null) }}"
                                type="text"
                                name="tax_document_valid_until"
                                id="tax_document_valid_until"
                                placeholder="MM-DD-YYYY"
                                autocomplete="off"
                            />                            
                        </div>



                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 font-medium">Uploaded:</span>

                            <div class="static-view">
                              <span class="text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) ?? 'N/A' }}</span>
                            </div>
                            <div class="edit-view">
                                <input
                                    class="w-32 border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 bg-white datepicker border-gray-300"
                                    type="text"
                                    value="{{ \App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date ?? null) }}"
                                    name="tax_document_upload_date"
                                    id="tax_document_upload_date"
                                    placeholder="MM-DD-YYYY"
                                    autocomplete="off"
                                />                           
                            </div>
                            <!-- <span class="text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) ?? 'N/A' }}</span> -->
                        </div>
                    </div>
                

                                <div class="flex flex-col items-start md:items-end gap-2 text-left w-auto">
                            <div class="md:text-right mb-4">
                                <a href="javascript:void(0)" id="opentaxdocModal"
                                    class="edit-view px-4 mt-3 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                                    Manage Tax Documents
                                </a>
                            </div>

                            <div id="taxDocPreviewWrapper">
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
                    <form id="taxDocForm" enctype="multipart/form-data">

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
                        <!-- Document Type Dropdown -->
                        <div class="mt-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                            <select name="tax_document_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">-- Select Document Type --</option>
                                <option value="Tax Exempt Certificate" {{ old('tax_document_type', $customer->tax_document_type) == 'Tax Exempt Certificate' ? 'selected' : '' }}>Tax Exempt Certificate</option>
                                <option value="Resale Certificate" {{ old('tax_document_type', $customer->tax_document_type) == 'Resale Certificate' ? 'selected' : '' }}>Resale Certificate</option>
                                <option value="Non-Profit Exemption" {{ old('tax_document_type', $customer->tax_document_type) == 'Non-Profit Exemption' ? 'selected' : '' }}>Non-Profit Exemption</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" id="cancelBtntaxdoc" class="px-4 py-2 border border-gray-300 rounded text-sm">Cancel</button>
                            <button type="submit"  class="saveBtntax px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-teal-700">Upload Document</button>
                        </div>

                    </form>


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
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
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
            {{ html()->password('password')->attributes([
                'class' => 'pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                'required' => true,
                'id' => 'password',
                'data-parsley-minlength' => '6',
                'data-parsley-minlength-message' => 'Password must be at least 6 characters.',
                
            ])->placeholder('') }}
            <div id="password-errors" class="mt-1 text-sm text-red-600"></div>
                                        @error('password')
                                            <p class="text-sm text-red-600">{{ $message }}</p>
                                        @enderror
        </div>

        <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password Confirmation</label>
        {{ html()->password('password_confirmation')->attributes([
            'class' => 'pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
            'required' => true,
            'id' => 'password_confirmation',
            'data-parsley-equalto' => '#password',
        ])->placeholder('') }}
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




@push('js')
<script>
    function confirmAndDelete(id) {
        if (confirm("Are you sure you want to delete this document?")) {
            document.getElementById(`delete-media-form-${id}`).submit();
        }
    }
    function confirmAndSuspend(id) {
        if (confirm("Are you sure you want to suspend this customer?")) {
            document.getElementById(`cstatus`).value="Archived";
            document.getElementById(`suspend-customer-form-${id}`).submit();
        }
    }
    function confirmAndActive(id) {
        if (confirm("Are you sure you want to activate this customer?")) {
            document.getElementById(`cstatus`).value="Active";
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

        resetModal?.addEventListener('click', (e) => {
            if (e.target === resetModal) closeModal();
        });
    });
</script>


<script>


document.addEventListener('DOMContentLoaded', () => {

    window.Parsley.addAsyncValidator('customemailcheck', function (xhr) {
    // Expecting { valid: true/false }
    const response = xhr.responseJSON || {};
    return response.valid === true;
});


    const dateInputs = document.querySelectorAll('input.datepicker');

    const jsFormat = @json(config('app.date.js_date_format'));
    dateInputs.forEach(input => {
        new AirDatepicker(input, {
            autoClose: true,
            dateFormat: jsFormat,
            minDate: new Date(),
            locale: {
                days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                months: [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ],
                monthsShort: [
                    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                ],
                today: 'Today',
                clear: 'Clear',
                dateFormat: jsFormat,
                timeFormat: 'hh:mm aa',
                firstDay: 0
            }
        });
    });

});

</script>



<script>
document.getElementById('resetPasswordForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const form = e.target;
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
        button.addEventListener('click', function () {
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
        button.addEventListener('click', function () {
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
    
    // Modal open/close
    const openModal = () => modalWrapper.style.display = 'flex';
    const closeModal = () => modalWrapper.style.display = 'none';

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modalWrapper.addEventListener('click', (e) => {
        if (e.target === modalWrapper) closeModal();
    });


    // Handle AJAX form submission
    form.addEventListener('submit', async (e) => {
    e.preventDefault();

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
    form.reset();

    const wrapper = document.getElementById('taxDocPreviewWrapper');
   wrapper.innerHTML = result.html;
bindTaxStatusButtons(); 


    notyf.success(result.message || 'Uploaded successfully');
}
 else {
            alert(result.message || "Upload failed.");
        }
    } catch (error) {
        console.error("Upload error:", error);
        alert("Something went wrong.");
    }

    uploadBtn.disabled = false;
    uploadBtn.textContent = "Upload Document";
});

});
</script>




@endpush