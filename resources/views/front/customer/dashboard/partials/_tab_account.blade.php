

         {{ html()->form()->id('customerForm')->attributes([
            'autocomplete' => 'off',
                            
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                         {!! html()->text('alladdresslist')->class('hidden')->attributes([
                            'id' => 'alladdresslist',
                            'autocomplete' => 'off'
                        ]) !!}

                       {!! html()->text('unique_id', $customer->unique_id)->class('hidden')->attributes([
                            'id' => 'unique_id',
                            'autocomplete' => 'off'
                        ]) !!}
                   
                   <div class="bg-white p-6 rounded shadow mb-4 border border-gray-200 mt-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <!-- Title and Description -->
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Account Settings</h2>
                                <p class="text-sm text-gray-500">Manage your account information and preferences</p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-2">
                                <div class="flex items-center gap-2">
                                    <button type="button" id="editBtn" class="bg-blue-600 inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">Edit Information</button>
                                    <button type="submit" id="saveBtn" style="display:none;" class="bg-green-600 text-white px-4 py-2 rounded text-sm saveBtn">Save Changes</button>
                                    <button type="button" id="cancelBtn" style="display:none;" class="bg-gray-700 text-white px-4 py-2 rounded text-sm">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">

                        <!-- Personal Information -->
                        <div class="bg-white rounded-md shadow-sm p-5 border border-gray-200">
                            <h3 class="text-base font-semibold text-gray-800 flex items-center gap-1 mb-4">
                                <x-heroicon-o-user class="w-5 h-5 text-gray-900" /> Personal Information
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">

                                <div class="min-w-0">
                                    <label class="text-xs text-gray-500 font-medium required">First Name </label>
                                    <div class="static-view text-sm text-gray-900">{{ $customer->first_name ?? '' }}</div>
                                    <!-- <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="{{ $customer->first_name ?? '' }}" /> -->

                                    {!! html()->text('first_name', old('first_name', $customer->first_name ?? ''))
                                        ->class([
                                            'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                            'border-red-500' => $errors->has('first_name'),
                                        ])
                                        ->attributes([
                                           
                                            'placeholder' => 'Enter First Name',
                                            'id' => 'first_name',
                                            'autocomplete' => 'off',
                                        ])
                                        ->required() !!}


                                </div>

                                <div class="min-w-0">
                                    <label class="text-xs text-gray-500 font-medium required">Last Name </label>
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
                                    ->required() !!}

                                </div>

                                <div class="sm:col-span-2 min-w-0">
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
                                        'data-parsley-remote' => route('front.customer.dashboard.check.email.unique', [
                                            'except' => $customer->id ?? null,
                                        ]),
                                        'data-parsley-remote-validator' => 'customemailcheck',
                                        'data-parsley-remote-message' => 'This email is already taken by another user.',
                                    ])
                                    ->required() !!}

                                </div>

                                <div class="sm:col-span-2 min-w-0">
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
                                        'data-parsley-error-message' => 'Please enter phone number in <br> format (xxx) xxx-xxxx',
                                    ])
                                    ->required() !!}

                                </div>

                            </div>
                        </div>

                        <div class="bg-white rounded-md shadow-sm p-5 border border-gray-200">
                            <h3 class="text-md font-semibold mb-4 flex items-center gap-1">
                                <x-heroicon-o-building-office class="w-5 h-5 text-gray-900" /> Company Information
                            </h3>
                            <div class="grid grid-cols-1 gap-4 text-sm text-gray-700">
                                <div class="col-span-2">
                                    <label class="text-xs text-gray-500 font-medium required">Company Name </label>
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
                            $billingAddress = $customer->addresses->firstWhere(fn ($a) => $a->type === 'Billing' );
                            $shippingAddress = $customer->addresses->firstWhere(fn ($a) => $a->type === 'Shipping');

                            $defaultAddresses = [
                                ['label' => 'Billing', 'data' => $billingAddress],
                                ['label' => 'Shipping', 'data' => $shippingAddress],
                            ];
                        @endphp
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">

                        @foreach ($defaultAddresses as $index => $addressItem)
                                @php $addresse = $addressItem['data']; @endphp
                                <div class="address-block bg-white rounded-lg shadow-sm p-5 border border-gray-200" data-index="{{ $index }}">
                                    <input type="hidden" name="addresses[{{ $index }}][address_id]" value="{{ $addresse->id ?? '' }}" class="address_id">
                                    <input type="hidden" name="addresses[{{ $index }}][type]" value="{{ $addressItem['label'] }}" class="type">
                                    
                                    
                                    <input type="hidden" name="addresses[{{ $index }}][is_primary]" value="{{ $addresse?->is_primary ? 1 : 0 }}" class="is_primary_input">


                                    <h3 class="text-base font-semibold mb-4 flex flex-wrap items-start gap-1">
                                        <x-heroicon-o-map-pin class="w-5 h-5 text-gray-900" />
                                        {{ ($addressItem['label']=='Shipping' ? 'Delivery' : $addressItem['label']) }} Address
                                        @if ($addresse && $addresse->is_primary)
                                            - <span class="text-xs bg-red-100 text-red-600 font-normal px-2 py-1 rounded">Default Address</span>  
                                        @endif
                                    </h3>

                                    <div class="space-y-4">

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="edit-view">
                                                <label class="text-xs text-gray-500 font-medium mb-1 required">First Name </label>
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
                                                <label class="text-xs text-gray-500 font-medium mb-1 required">Last Name </label>
                                                {!! html()->text("addresses[$index][last_name]", old("addresses.$index.last_name", $addresse->last_name ?? ''))
                                                    ->class([
                                                        'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                                        'border-red-500' => $errors->has("addresses.$index.last_name"),
                                                    ])
                                                    ->attributes([
                                                      
                                                        'placeholder' => 'Last Name',
                                                        'class' => 'last_name'
                                                    ])->required() !!}
                                            </div>
                                        </div>

                                        <div>
                                            <label class="text-xs text-gray-500 font-medium mb-1 edit-view required">Address </label>
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
                                                ])->required() !!}
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="edit-view">
                                                <label class="text-xs text-gray-500 font-medium mb-1 required">City </label>
                                                {!! html()->text("addresses[$index][city]", old("addresses.$index.city", $addresse->city ?? ''))
                                                    ->class([
                                                        'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                                        'border-red-500' => $errors->has("addresses.$index.city"),
                                                    ])
                                                    ->attributes([
                                                        'placeholder' => 'Enter City',
                                                        'class' => 'city'
                                                    ])->required() !!}
                                            </div>
                                            <div class="edit-view">
                                                <label class="text-xs text-gray-500 font-medium mb-1 required">Zip Code </label>
                                                {!! html()->text("addresses[$index][zip_code]", old("addresses.$index.zip_code", $addresse->zip_code ?? ''))
                                                    ->class([
                                                        'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                                        'border-red-500' => $errors->has("addresses.$index.zip_code"),
                                                    ])
                                                    ->attributes([
                                                        'maxlength' => 8,
                                                        'placeholder' => 'ZIP Code',
                                                        'class' => 'zip_code'
                                                    ])->required() !!}
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div class="edit-view">
                                                        <label class="text-xs text-gray-500 font-medium mb-1 required">State </label>
                                                        {!! html()
                                                            ->select("addresses[$index][state_id]",
                                                                ['' => '-- Select State --'] + $states->pluck('name', 'id')->toArray(),
                                                                old("addresses.$index.state_id", $addresse->state_id ?? '')
                                                            )
                                                            ->class([
                                                                'pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm state_id',
                                                                'border-red-500' => $errors->has("addresses.$index.state_id"),
                                                            ]) ->attribute('required', true) 
                                                             !!}
                                                    </div>

                                                    <div class="edit-view">
                                                        <label class="text-xs text-gray-500 font-medium mb-1 required">Phone Number </label>


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
                                                            ])->required() !!}

                                                    </div>

                                        </div>                       
                                    </div>
                                </div>
                        @endforeach


                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <!-- Account Status -->
                        <div class="bg-white p-5 rounded-md shadow-sm border border-gray-200">
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
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500 font-medium">Customer Since:</span>
                                    <span class="text-xs">{{ App\Helpers\CustomHelper::formatDate($customer->created_at) ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500 font-medium">Customer ID:</span>
                                    <span class="text-xs">{{ $customer->unique_id }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Credit Information -->
                        <div class="bg-white p-5 rounded-md shadow-sm border border-gray-200">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                                <h3 class="text-base font-semibold flex items-center gap-1"> 
                                    <x-heroicon-o-credit-card class="w-5 h-5 text-gray-900" />
                                    Credit Information
                                </h3>
                                <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded">Admin View</span>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-500 font-medium">Credit Limit:</span>
                                    <span class="text-right">
                                        <span class="text-gray-900 font-semibold">{{ \App\Helpers\CustomHelper::formatCurrency($customer->credit_limit) }}</span>
                                    </span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-500 font-medium">Current Balance:</span>
                                    <span class="font-medium"> {{ \App\Helpers\CustomHelper::formatCurrency($customer->available_credit_balance) }} </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-500 font-medium">Available Credit:</span>
                                    <span class="text-green-600 font-medium"> {{ \App\Helpers\CustomHelper::formatCurrency(\App\Helpers\CustomHelper::getAvailableCredit($customer)) }}</span>
                                </div>

                            @php
                                $climit = $customer->credit_limit ?? 0;
                                $available = \App\Helpers\CustomHelper::getAvailableCredit($customer);


                                $per = $climit > 0 ? ((($climit - $available) / $climit) * 100) : 0;
                            @endphp

                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <label class="text-xs text-gray-500 font-medium">Credit Utilization</label>
                                    <div class="text-right text-xs text-gray-500 mt-0.5">{{round($per)}}%</div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width:{{round($per)}}%; max-width: 100%;"></div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="bg-white p-4 rounded shadow border border-gray-200 mt-6 mb-6">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                            <div class="flex gap-1 flex-wrap items-start ">
                                <x-heroicon-o-document class="w-5 h-5 text-gray-900" />
                                <h2 class="text-base font-semibold flex items-center gap-1">Tax Exempt Status</h2>
                                <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded">Admin Control</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Left: Tax Info -->
                            <div class="space-y-2 text-sm text-gray-700">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500 font-medium">Tax Status:</span>
                                    <div class="inline-flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 gap-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                            <x-heroicon-o-shield-check class="w-5 h-5 text-green-500" />
                                            <span class="leading-[1.2]">Tax Exempt</span>
                                        </span>
                                    </div>
                                    <!-- <span class="text-green-700 bg-green-100 px-2 py-1 rounded-full text-xs font-medium">Tax Exempt</span> -->
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500 font-medium">Valid Until:</span>
                                    <span class="text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500 font-medium">Uploaded:</span> 
                                    <span class="text-sm" id="tax_document_upload_date">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) ?? 'N/A' }}</span>
                                </div>
                            </div>

                            <!-- Right: File Card -->
                            <div class="border border-gray-200 rounded-lg p-4 text-sm bg-white w-full">
                                 <div id="taxDocPreviewWrapper" class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">

                              
                                  @include('front.customer.dashboard.partials.tax_doc_preview', ['customer' => $customer])


                                </div>
                            </div>

                        </div>
                    </div>

  {{ html()->form()->close() }}



<!-- taxdocModalWrapper Wrapper -->
<div id="taxdocModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Upload Tax Exempt Document</h2>
                </div>
                <button type="button" id="closetaxdocBtn" class="text-gray-400 hover:text-gray-700  text-xl">&times;</button>
            </div>
            <div class=" p-6 overflow-y-auto">
                <div class="max-w-md mx-auto">

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
                                <input type="file" id="fileInput" name="tax_document" class="hidden" accept=".pdf,.png,.jpg,.jpeg" onchange="handleFileChange(event)" required/>
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
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Document Type </label>

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



  @push('js')


<script>
        document.addEventListener('click', (e) => {

            const modalWrapper = document.getElementById('taxdocModalWrapper');

            if (e.target && e.target.id === 'opentaxdocModal') {
                modalWrapper.style.display = 'flex';
            }
        });
</script>

<script>
    
        document.addEventListener('DOMContentLoaded', () => {

            window.Parsley.addAsyncValidator('customemailcheck', function (xhr) {
            // Expecting { valid: true/false }
            const response = xhr.responseJSON || {};
            return response.valid === true;
        });
        });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('customerForm');
        const saveBtn = document.querySelector('.saveBtn');
        const addressListInput = document.getElementById('alladdresslist');

        let allowSubmit = false;

        // Function to collect address data
        function collectAddresses() {
            const addressBlocks = document.querySelectorAll('.address-block');
            const data = [];

            

            addressBlocks.forEach((block, index) => {
                data.push({
                    first_name: block.querySelector('.first_name')?.value || null,
                    last_name: block.querySelector('.last_name')?.value || null,
                    phone: block.querySelector('.phone')?.value || null,
                    address_id: block.querySelector('.address_id')?.value || null,
                    type: block.querySelector('.type')?.value || null,
                    address: block.querySelector('.address')?.value || '',
                    city: block.querySelector('.city')?.value || '',
                    zip_code: block.querySelector('.zip_code')?.value || '',
                    state_id: block.querySelector('.state_id')?.value || '',
                });
            });

            addressListInput.value = JSON.stringify(data);
        }

        // Triggered only when save button is clicked
        saveBtn.addEventListener('click', function () {
            allowSubmit = true;

            // Collect address list first
            collectAddresses();

            // Validate form before allowing submission
            if (!$(form).parsley().isValid()) {
                $(form).parsley().validate();
                allowSubmit = false; // Block if validation fails
            }
        });

        // Intercept form submission
        form.addEventListener('submit', function (e) {
            if (!allowSubmit) {
                e.preventDefault(); // Block submission if not allowed
            }
            allowSubmit = false; // Reset after every attempt
        });

        // Define custom async validator outside of any event
        window.Parsley.addAsyncValidator('customemailcheck', function (xhr) {
            const response = xhr.responseJSON || {};
            return response.valid === true;
        });
    });
</script>

<script >

    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('taxdocModalWrapper');
        const openBtn = document.getElementById('opentaxdocModal');
        const closeBtn = document.getElementById('closetaxdocBtn');
        const cancelBtn = document.getElementById('cancelBtntaxdoc');
        const form = document.getElementById('taxDocForm');
        const fileInput = document.getElementById('fileInput');

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
                const response = await fetch("{{ route('front.customer.dashboard.taxdoc.upload') }}", {
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
                        uploadDateInput.textContent  = result.upload_date;
                    }

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

      
  @endpush