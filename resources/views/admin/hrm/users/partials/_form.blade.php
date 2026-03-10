<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-user class="w-5 h-5 text-blue-600" />
            Personal Information
        </h2>

        <div class="space-y-6">
            <!-- Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="firstName" class="text-xs text-gray-500 font-medium required">First Name </label>
                    <!-- <input type="text" id="firstName" placeholder="John"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300"> -->

                    {!! html()->text('first_name', old('first_name', $user->first_name ?? ''))
                    ->id('firstName')
                    ->class('w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'placeholder' => 'John',
                    'autocomplete' => 'off',
                    ])
                    ->required()
                    !!}

                </div>
                <div>
                    <label for="middleName" class="text-xs text-gray-500 font-medium">Middle Name</label>

                    {!! html()->text('middle_name', old('middle_name', $user->middle_name ?? ''))
                    ->id('middleName')
                    ->class('w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'placeholder' => 'Michael',
                    'autocomplete' => 'off',
                    ])
                    !!}

                </div>
                <div>
                    <label for="lastName" class="text-xs text-gray-500 font-medium required">Last Name </label>

                    {!! html()->text('last_name', old('last_name', $user->last_name ?? ''))
                    ->id('lastName')
                    ->class('w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'placeholder' => 'Smith',
                    'autocomplete' => 'off',
                    ])->required()
                    !!}

                </div>
            </div>

            <!-- Row 2 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="email" class="text-xs text-gray-500 font-medium required">Email Address </label>

                    {!! html()->email('email', old('email', $user->email ?? ''))
                    ->id('email')
                    ->class('w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                    ->attributes([

                    'placeholder' => 'Enter Email',
                    'id' => 'email',
                    'autocomplete' => 'off',
                    'name' => 'email',
                    'data-parsley-type' => 'email',
                    'data-parsley-trigger' => 'change',
                    'data-parsley-remote' => route('admin.hrm.users.check.email.unique', [
                    'except' => $user->id ?? null,
                    ]),
                    'data-parsley-remote-validator' => 'useremailcheck',
                    'data-parsley-remote-message' => 'This email is already taken by another user.',
                    ])
                    ->required()
                    !!}

                </div>
                <div>
                    <label for="mobile" class="text-xs text-gray-500 font-medium">Mobile Phone</label>

                    {!! html()->text('mobile_phone', old('mobile_phone', $user->mobile_phone ?? ''))
                    ->id('mobile')
                    ->class('masked-phone w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'maxlength' => 14,
                    'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                    'data-parsley-error-message' => 'Please enter phone number in <br> format (xxx) xxx-xxxx',
                    'placeholder' => '(xxx) xxx-xxxx',
                    'id' => 'company_phone',
                    'autocomplete' => 'tel',
                    ])
                    !!}

                </div>
                <div>
                    <label for="phone" class="text-xs text-gray-500 font-medium">Phone Number </label>

                    {!! html()->text('phone_number', old('phone_number', $user->phone_number ?? ''))
                    ->id('phone')
                    ->class('masked-phone w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'maxlength' => 14,
                    'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                    'data-parsley-error-message' => 'Please enter phone number in <br> format (xxx) xxx-xxxx',
                    'placeholder' => '(xxx) xxx-xxxx',
                    'id' => 'company_phone',
                    'autocomplete' => 'off',
                    ])

                    !!}

                </div>
            </div>
            <!-- Row 3 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                    <label for="password" class="text-xs text-gray-500 font-medium">Password</label>
                    <div class="relative">
                        {!! html()->password('password')
                        ->id('password')->value(isset($user) ? '' : '12345678')
                        ->class('pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                        ->attributes([
                        'placeholder' => isset($user)
                        ? 'Leave blank to keep current password'
                        : 'Default password is 12345678',
                        'data-parsley-minlength' => 6,
                        'data-parsley-minlength-message' => 'Password must be at least 6 characters.',
                        'data-parsley-optional' => 'true', // Make validation optional
                        'autocomplete' => 'off',
                        ]) !!}

                        <button type="button" onclick="toggleVisibility('password', this)" class="absolute inset-y-0 h-[35px] right-3 pl-3 flex items-center text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="text-xs text-gray-500 font-medium">Confirm Password</label>
                    <div class="relative">
                        {!! html()->password('password_confirmation')
                        ->id('password_confirmation')->value(isset($user) ? '' : '12345678')
                        ->class('pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                        ->attributes([
                        'placeholder' => isset($user)
                        ? 'Leave blank to keep current password'
                        : 'Default password is 12345678',
                        'data-parsley-equalto' => '#password',
                        'data-parsley-equalto-message' => 'Passwords do not match.',
                        'data-parsley-optional' => 'true', // Optional confirmation
                        ]) !!}

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
                    @error('password_confirmation')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @php
                    $isEdit = isset($user) && $user->exists;
                @endphp

                @if(!$isEdit)
                            <div>
                                <label for="social_security" class="text-xs text-gray-500 font-medium">
                                    Social Security
                                </label>

                                {!! html()->text('social_security', old('social_security'))
                                    ->id('social_security')
                                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                                    ->attributes([
                                        'maxlength' => 10, // 8 digits + 2 dashes
                                        'data-parsley-pattern' => '^\d{3}-\d{2}-\d{3}$',
                                        'data-parsley-error-message' => 'Please enter in format xxx-xx-xxx',
                                        'placeholder' => 'xxx-xx-xxx',
                                        'autocomplete' => 'off',
                                    ])
                                !!}
                            </div>
                @endif

               @if($isEdit)
                    <div>
                        <label class="text-xs text-gray-500 font-medium">
                            Social Security
                        </label>

                        <div class="relative">
                            {!! html()->password('social_security', 'xxx-xx-xxxx')
                                ->id('social_security')
                                ->class('pl-2 pr-10 py-2 w-full border rounded-md text-sm border-gray-300 bg-gray-100 cursor-not-allowed')
                                ->attributes([
                                    'disabled' => true,
                                    'maxlength' => 10,
                                    'data-parsley-pattern' => '^\d{3}-\d{2}-\d{3}$',
                                    'data-parsley-error-message' => 'Please enter in format xxx-xx-xxx',
                                    'placeholder' => 'xxx-xx-xxx',
                                    'autocomplete' => 'off',
                                ])
                            !!}

                            <!-- Lock Button -->
                            <button type="button"
                                data-open-verify
                                data-field-id="social_security"
                                data-verify-url="{{ route('admin.hrm.users.verify-ssn', $user->id) }}"
                                class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700">

                                <x-heroicon-o-lock-closed class="w-5 h-5"/>
                            </button>
                        </div>
                    </div>
                @endif



            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-magnifying-glass class="w-5 h-5 text-blue-600" />
            Address Information
        </h2>

        <div class="space-y-6">
            <!-- Street Address -->
            <div class="mb-4">
                <label for="street" class="text-xs text-gray-500 font-medium required">Street Address </label>

                {!! html()->text('street', old('street', $user->street_address ?? ''))
                ->id('street')
                ->class('w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                ->attributes([
                'placeholder' => '123 Main Street',
                'autocomplete' => 'off',
                ])->required()
                !!}

            </div>

            <!-- Grid for City, State, Zip Code, Country -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="city" class="text-xs text-gray-500 font-medium required">City </label>
                    {!! html()->text('city', old('city', $user->city ?? ''))
                    ->id('city')
                    ->class('w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'placeholder' => 'New York',
                    'autocomplete' => 'off',
                    ])->required()
                    !!}
                </div>
                <div>
                    <label for="state" class="text-xs text-gray-500 font-medium required">State </label>

                    <!-- {!! html()
                             ->select('state',
                             $states->mapWithKeys(fn ($state) => [$state->id => $state->name])->toArray(),
                                 old('state', $user->state ?? '')
                             )
                             ->id('state')
                             ->class([
                                 'w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                 'border-red-500' => $errors->has('state'),
                             ])->required()
                         !!} -->

                    {!! html()
                    ->select(
                    'state',
                    ['' => '-- Select State --'] + $states->pluck('name', 'id')->toArray(),
                    old('state', $user->state ?? '')
                    )
                    ->id('state')
                    ->class([
                    'w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                    'border-red-500' => $errors->has('state'),
                    ])
                    ->required()
                    !!}

                </div>
                <div>
                    <label for="zip" class="text-xs text-gray-500 font-medium">Zip Code</label>

                    {!! html()->text('zip', old('zip', $user->zip_code ?? ''))
                    ->id('zip')
                    ->class('w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'maxlength' => 8,
                    'placeholder' => '10001',
                    'autocomplete' => 'off',
                    ])
                    !!}


                </div>
                <div>
                    <label for="country" class="text-xs text-gray-500 font-medium required">Country </label>

                    {!! html()->select('country', [
                    'USA' => 'USA',
                    'Canada' => 'Canada',
                    'UK' => 'UK',
                    'India' => 'India',
                    ], old('country', $user->country ?? 'USA')) // default to USA if old or user value is not set
                    ->id('country')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'autocomplete' => 'off',
                    ])
                    ->required()
                    !!}


                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-briefcase class="w-5 h-5 text-blue-600" />
            Employment Information
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Start Date -->
            <div>
                <label for="startDate" class="text-xs text-gray-500 font-medium">Start Date </label>

                {!! html()->text('startDate', old('startDate', \App\Helpers\CustomHelper::formatDate($user->start_date ?? null) ?? null))->class([
                'w-full pl-2 pr-2 py-2 border rounded-md text-sm bg-white datepicker border-gray-300',
                'border-red-500' => $errors->has('startDate'),
                'border-gray-300' => !$errors->has('startDate'),
                ])->attributes([
                'id' => 'startDate',
                'placeholder' => 'MM-DD-YYYY',
                'autocomplete' => 'off',
                ]) !!}

            </div>

            <!-- End Date -->
            <div>
                <label for="endDate" class="text-xs text-gray-500 font-medium">End Date</label>


                {!! html()->text('endDate', old('endDate', \App\Helpers\CustomHelper::formatDate($user->end_date ?? null) ?? null))->class([
                'w-full pl-2 pr-2 py-2 border rounded-md text-sm bg-white datepicker border-gray-300',
                'border-red-500' => $errors->has('endDate'),
                'border-gray-300' => !$errors->has('endDate'),
                ])->attributes([
                'id' => 'endDate',
                'placeholder' => 'MM-DD-YYYY',
                'autocomplete' => 'off',
                ]) !!}

            </div>

            <!-- Status -->
            <div>
                <label for="status" class="text-xs text-gray-500 font-medium">Status</label>

                {!! html()
                ->select('status', [
                'Active' => 'Active',
                'Inactive' => 'Inactive',
                ], old('status', $user->status ?? ''))
                ->id('status')
                ->class([
                'w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                'border-red-500' => $errors->has('status'),
                'border-gray-300' => !$errors->has('status'),
                ])
                ->required()
                !!}

                @error('status')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

            </div>

            <!-- Pay Type -->
            <div>
                <label for="payType" class="text-xs text-gray-500 font-medium">Pay Type</label>


                {!! html()
                ->select('payType', $paytypes, old('payType', $user->pay_type ?? ''))
                ->id('payType')
                ->class([
                'w-full pl-2 pr-2 py-2 border rounded-md text-sm',
                'border-red-500' => $errors->has('payType'),
                'border-gray-300' => !$errors->has('payType'),
                ])
                ->required()
                !!}


                @error('payType')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror


            </div>

            <!-- Assign Store -->
            <div>
                <label for="store_id" class="text-xs text-gray-500 font-medium">
                    Assign to Store
                </label>

                {!! html()
                    ->select('store_id', ['' => 'Select Store'] + $stores->toArray(),
                        old('store_id', $user->store_id ?? '')
                    )
                    ->id('store_id')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                !!}
            </div>


        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-clock class="w-5 h-5 text-blue-600" />
            Time Clock Settings
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">


            <div class="flex items-center gap-2">

                {!! html()->checkbox('limit_start',(bool) old('limit_start', $user->limit_start_time ?? false))->class([
                'mt-1 rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500'
                ])->attributes(['id' => 'limit_start']) !!}

                <label for="limit_start" class="flex items-center text-xs text-gray-500 font-medium cursor-pointer">
                    Limit to Shift Start Time
                    <x-heroicon-o-question-mark-circle class="w-4 h-4 text-gray-400 ml-1" />
                </label>
            </div>

            <div class="flex items-center gap-2">

                {!! html()->checkbox('limit_end',(bool) old('limit_end', $user->limit_end_time ?? false))->class([
                'mt-1 rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500'
                ])->attributes(['id' => 'limit_end']) !!}

                <label for="limit_end" class="flex items-center text-xs text-gray-500 font-medium cursor-pointer">
                    Limit to Shift End Time
                    <x-heroicon-o-question-mark-circle class="w-4 h-4 text-gray-400 ml-1" />
                </label>
            </div>

            <div class="flex flex-col">
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Auto Clock-Out Penalty
                </label>

                <select name="auto_clockout_penalty"
                    class="border rounded px-3 py-2 text-xs w-full focus:ring-blue-500 focus:border-blue-500">
                    
                    <option value="">Select penalty time</option>
                    <option value="15" {{ old('auto_clockout_penalty', $user->auto_clockout_penalty ?? '') == 15 ? 'selected' : '' }}>15 Minutes</option>
                    <option value="30" {{ old('auto_clockout_penalty', $user->auto_clockout_penalty ?? '') == 30 ? 'selected' : '' }}>30 Minutes</option>
                    <option value="45" {{ old('auto_clockout_penalty', $user->auto_clockout_penalty ?? '') == 45 ? 'selected' : '' }}>45 Minutes</option>
                    <option value="60" {{ old('auto_clockout_penalty', $user->auto_clockout_penalty ?? '') == 60 ? 'selected' : '' }}>60 Minutes</option>
                    <option value="90" {{ old('auto_clockout_penalty', $user->auto_clockout_penalty ?? '') == 90 ? 'selected' : '' }}>90 Minutes</option>
                    <option value="120" {{ old('auto_clockout_penalty', $user->auto_clockout_penalty ?? '') == 120 ? 'selected' : '' }}>120 Minutes</option>

                </select>
            </div>


        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 hidden">

            {{-- Shift Start Time --}}
            <div class="flex flex-col items-start">
                <label class="block text-xs font-medium text-gray-500 mb-0.5">
                    Shift Start Time
                </label>

                <input
                    type="text"
                    name="shift_start_time"
                    value="{{ old('shift_start_time', $user->shift_start_time ?? '') }}"
                    class="time-picker border rounded px-3 py-3 text-xs w-full"
                    placeholder="Select time"
                    
                    readonly
                >
            </div>

            {{-- Shift End Time --}}
            <div class="flex flex-col items-start">
                <label class="block text-xs font-medium text-gray-500 mb-0.5">
                    Shift End Time
                </label>

                <input
                    type="text"
                    name="shift_end_time"
                    value="{{ old('shift_end_time', $user->shift_end_time ?? '') }}"
                    class="time-picker border rounded px-3 py-3 text-xs w-full"
                    placeholder="Select time"
                    
                    readonly
                >
            </div>

        </div>

    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-phone class="w-5 h-5 text-blue-600" />
            Emergency Contact 1
        </h2>

        <div class="space-y-6">
            <!-- Name Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="emergency_first_name" class="text-xs text-gray-500 font-medium">First Name </label>
                    {!! html()->text('emergency_first_name', old('emergency_first_name', $user->emergencyContactOne->first_name ?? ''))
                    ->id('emergency_first_name')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'placeholder' => 'Jane',
                    'autocomplete' => 'off',
                    ])

                    !!}
                </div>

                <div>
                    <label for="emergency_middle_name" class="text-xs text-gray-500 font-medium">Middle Name</label>
                    {!! html()->text('emergency_middle_name', old('emergency_middle_name', $user->emergencyContactOne->middle_name ?? ''))
                    ->id('emergency_middle_name')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'placeholder' => 'Marie',
                    'autocomplete' => 'off',
                    ])
                    !!}
                </div>

                <div>
                    <label for="emergency_last_name" class="text-xs text-gray-500 font-medium">Last Name </label>
                    {!! html()->text('emergency_last_name', old('emergency_last_name', $user->emergencyContactOne->last_name ?? ''))
                    ->id('emergency_last_name')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'placeholder' => 'Smith',
                    'autocomplete' => 'off',
                    ])

                    !!}
                </div>
            </div>

            <!-- Contact Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="emergency_email" class="text-xs text-gray-500 font  -medium">Email Address</label>
                    {!! html()->email('emergency_email', old('emergency_email', $user->emergencyContactOne->email ?? ''))
                    ->id('emergency_email')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'placeholder' => 'jane.smith@email.com',
                    'autocomplete' => 'off',
                    ])
                    !!}
                </div>

                <div>
                    <label for="emergency_mobile_phone" class="text-xs text-gray-500 font-medium">Mobile Phone</label>
                    {!! html()->text('emergency_mobile_phone', old('emergency_mobile_phone', $user->emergencyContactOne->mobile_phone ?? ''))
                    ->id('emergency_mobile_phone')
                    ->class('masked-phone w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'maxlength' => 14,
                    'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                    'data-parsley-error-message' => 'Please enter mobile number in <br> format (xxx) xxx-xxxx',
                    'placeholder' => '(xxx) xxx-xxxx',
                    'id' => 'company_phone',
                    'autocomplete' => 'tel',
                    ])
                    !!}
                </div>

                <div>
                    <label for="emergency_phone" class="text-xs text-gray-500 font-medium">Phone Number</label>
                    {!! html()->text('emergency_phone', old('emergency_phone', $user->emergencyContactOne->phone_number ?? ''))
                    ->id('emergency_phone')
                    ->class('masked-phone w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'maxlength' => 14,
                    'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                    'data-parsley-error-message' => 'Please enter phone number in <br> format (xxx) xxx-xxxx',
                    'placeholder' => '(xxx) xxx-xxxx',
                    'id' => 'company_phone',
                    'autocomplete' => 'tel',
                    ])
                    !!}
                </div>
            </div>

            <!-- Address -->
            <div class="mb-4">
                <label for="emergency_address" class="text-xs text-gray-500 font-medium">Street Address</label>
                {!! html()->text('emergency_address', old('emergency_address', $user->emergencyContactOne->street_address ?? ''))
                ->id('emergency_address')
                ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                ->attributes([
                'placeholder' => '456 Oak Avenue',
                'autocomplete' => 'off',
                ])
                !!}
            </div>

            <!-- City, State, Zip, Country -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="emergency_city" class="text-xs text-gray-500 font-medium">City</label>
                    {!! html()->text('emergency_city', old('emergency_city', $user->emergencyContactOne->city ?? ''))
                    ->id('emergency_city')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'placeholder' => 'New York',
                    'autocomplete' => 'off',
                    ])
                    !!}
                </div>

                <div>
                    <label for="emergency_state" class="text-xs text-gray-500 font-medium">State</label>
                    <!-- {!! html()
                                    ->select('emergency_state',
                                    $states->mapWithKeys(fn ($state) => [$state->id => $state->name])->toArray(),
                                        old('emergency_state', $user->emergencyContactOne->state ?? '')
                                    )
                                    ->id('emergency_state')
                                    ->class([
                                        'w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                        'border-red-500' => $errors->has('emergency_state'),
                                    ])->required()
                                !!} -->

                    {!! html()
                    ->select(
                    'emergency_state',
                    ['' => '-- Select State --'] + $states->pluck('name', 'id')->toArray(),
                    old('emergency_state', $user->emergencyContactOne->state ?? '')
                    )
                    ->id('emergency_state')
                    ->class([
                    'w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                    'border-red-500' => $errors->has('emergency_state'),
                    ])
                    !!}


                </div>

                <div>
                    <label for="emergency_zip" class="text-xs text-gray-500 font-medium">Zip Code</label>
                    {!! html()->text('emergency_zip', old('emergency_zip', $user->emergencyContactOne->zip_code ?? ''))
                    ->id('emergency_zip')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'maxlength' => 8,
                    'placeholder' => '10001',
                    'autocomplete' => 'off',
                    ])
                    !!}
                </div>

                <div>
                    <label for="emergency_country" class="text-xs text-gray-500 font-medium">Country</label>
                    <!-- {!! html()->text('emergency_country', old('emergency_country', $user->emergencyContactOne->country ?? ''))
                    ->id('emergency_country')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'placeholder' => 'USA',
                    'autocomplete' => 'off',
                    ])
                    !!} -->

                    {!! html()->select('emergency_country', [
                    'USA' => 'USA',
                    'Canada' => 'Canada',
                    'UK' => 'UK',
                    'India' => 'India',
                    ], old('emergency_country', $user->emergencyContactOne->country ?? 'USA')) // default to USA if old or user value is not set
                    ->id('emergency_country')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'autocomplete' => 'off',
                    ])
                    ->required()
                    !!}

                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-phone class="w-5 h-5 text-blue-600" />
            Emergency Contact 2
        </h2>

        <div class="space-y-6">
            <!-- Name Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="emergency2_first_name" class="text-xs text-gray-500 font-medium">First Name</label>
                    {!! html()->text('emergency2_first_name', old('emergency2_first_name', $user->emergencyContactTwo->first_name ?? ''))
                    ->id('emergency2_first_name')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes(['placeholder' => 'Robert', 'autocomplete' => 'off']) !!}
                </div>
                <div>
                    <label for="emergency2_middle_name" class="text-xs text-gray-500 font-medium">Middle Name</label>
                    {!! html()->text('emergency2_middle_name', old('emergency2_middle_name', $user->emergencyContactTwo->middle_name ?? ''))
                    ->id('emergency2_middle_name')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes(['placeholder' => 'James', 'autocomplete' => 'off']) !!}
                </div>
                <div>
                    <label for="emergency2_last_name" class="text-xs text-gray-500 font-medium">Last Name</label>
                    {!! html()->text('emergency2_last_name', old('emergency2_last_name', $user->emergencyContactTwo->last_name ?? ''))
                    ->id('emergency2_last_name')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes(['placeholder' => 'Johnson', 'autocomplete' => 'off']) !!}
                </div>
            </div>

            <!-- Contact Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="emergency2_email" class="text-xs text-gray-500 font-medium">Email Address</label>
                    {!! html()->email('emergency2_email', old('emergency2_email', $user->emergencyContactTwo->email ?? ''))
                    ->id('emergency2_email')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes(['placeholder' => 'robert.johnson@email.com', 'autocomplete' => 'off']) !!}
                </div>
                <div>
                    <label for="emergency2_mobile_phone" class="text-xs text-gray-500 font-medium">Mobile Phone</label>
                    {!! html()->text('emergency2_mobile_phone', old('emergency2_mobile_phone', $user->emergencyContactTwo->mobile_phone ?? ''))
                    ->id('emergency2_mobile_phone')
                    ->class('masked-phone w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes(['maxlength' => 14,
                    'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                    'data-parsley-error-message' => 'Please enter phone number in <br> format (xxx) xxx-xxxx',
                    'placeholder' => '(xxx) xxx-xxxx',
                    'id' => 'company_phone',
                    'autocomplete' => 'tel',]) !!}
                </div>
                <div>
                    <label for="emergency2_phone_number" class="text-xs text-gray-500 font-medium">Phone Number</label>
                    {!! html()->text('emergency2_phone_number', old('emergency2_phone_number', $user->emergencyContactTwo->phone_number ?? ''))
                    ->id('emergency2_phone_number')
                    ->class('masked-phone w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes(['maxlength' => 14,
                    'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                    'data-parsley-error-message' => 'Please enter phone number in <br> format (xxx) xxx-xxxx',
                    'placeholder' => '(xxx) xxx-xxxx',
                    'id' => 'company_phone',
                    'autocomplete' => 'tel',]) !!}
                </div>
            </div>

            <!-- Address -->
            <div class="mb-4">
                <label for="emergency2_street_address" class="text-xs text-gray-500 font-medium">Street Address</label>
                {!! html()->text('emergency2_street_address', old('emergency2_street_address', $user->emergencyContactTwo->street_address ?? ''))
                ->id('emergency2_street_address')
                ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                ->attributes(['placeholder' => '789 Pine Street', 'autocomplete' => 'off']) !!}
            </div>

            <!-- City, State, Zip, Country -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="emergency2_city" class="text-xs text-gray-500 font-medium">City</label>
                    {!! html()->text('emergency2_city', old('emergency2_city', $user->emergencyContactTwo->city ?? ''))
                    ->id('emergency2_city')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes(['placeholder' => 'Brooklyn', 'autocomplete' => 'off']) !!}
                </div>
                <div>
                    <label for="emergency2_state" class="text-xs text-gray-500 font-medium">State</label>
                    <!-- {!! html()
                                ->select('emergency2_state',
                                $states->mapWithKeys(fn ($state) => [$state->id => $state->name])->toArray(),
                                    old('emergency2_state', $user->emergencyContactTwo->state ?? '')
                                )
                                ->id('emergency2_state')
                                ->class([
                                    'w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                    'border-red-500' => $errors->has('emergency2_state'),
                                ])->required()
                            !!} -->

                    {!! html()
                    ->select(
                    'emergency2_state',
                    ['' => '-- Select State --'] + $states->pluck('name', 'id')->toArray(),
                    old('emergency2_state', $user->emergencyContactTwo->state ?? '')
                    )
                    ->id('emergency2_state')
                    ->class([
                    'w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                    'border-red-500' => $errors->has('emergency2_state'),
                    ])

                    !!}

                </div>
                <div>
                    <label for="emergency2_zip" class="text-xs text-gray-500 font-medium">Zip Code</label>
                    {!! html()->text('emergency2_zip', old('emergency2_zip', $user->emergencyContactTwo->zip_code ?? ''))
                    ->id('emergency2_zip')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes(['maxlength' => 8, 'placeholder' => '11201', 'autocomplete' => 'off']) !!}
                </div>
                <div>
                    <label for="emergency2_country" class="text-xs text-gray-500 font-medium">Country</label>
                    
                    <!-- {!! html()->text('emergency2_country', old('emergency2_country', $user->emergencyContactTwo->country ?? ''))
                    ->id('emergency2_country')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes(['placeholder' => 'USA', 'autocomplete' => 'off']) !!} -->

                    {!! html()->select('emergency2_country', [
                    'USA' => 'USA',
                    'Canada' => 'Canada',
                    'UK' => 'UK',
                    'India' => 'India',
                    ], old('emergency2_country', $user->emergencyContactTwo->country ?? 'USA')) // default to USA if old or user value is not set
                    ->id('emergency2_country')
                    ->class('w-full pl-2 pr-2 py-2 border rounded-md text-sm border-gray-300')
                    ->attributes([
                    'autocomplete' => 'off',
                    ])
                    ->required()
                    !!}
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-shield-check class="w-5 h-5 text-blue-600" />
            Role Assignment
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Card Checkbox -->

            @php
            $selectedRoles = old('roles', isset($user) ? $user->roles->pluck('id')->toArray() : []);
            @endphp

            @foreach($roles as $role)
            <label class="flex items-start gap-2 p-4 border-1 rounded-lg transition-all cursor-pointer
                                hover:shadow-sm
                                has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400
                                border-gray-300 bg-white">
                <input
                    type="checkbox"
                    name="roles[]"
                    value="{{ $role->id }}"
                    class="mt-1 text-blue-600"
                    {{ in_array($role->id, $selectedRoles) ? 'checked' : '' }} />

                <div>
                    <p class="font-semibold text-sm text-gray-800">{{ $role->name }}</p>
                    <p class="text-sm text-gray-500">{{ $role->description ?? 'No description' }}</p>
                </div>
            </label>
            @endforeach



        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <div class="flex justify-end space-x-3">
            <!-- Cancel Button -->
            <a href="{{ route('admin.hrm.users.index') }}"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                Cancel
            </a>

            <!-- Add Employee Button -->
            <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                <!-- Heroicon: User Plus -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white mr-2" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h11l3 3v13a2 2 0 01-2 2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-8H7v8m0-16V5h4v4H7z" />
                </svg>
                {{ isset($user) && $user->exists ? 'Update Employee' : 'Add Employee' }}
            </button>
        </div>
    </div>
</div>



{{-- Modal (hidden by default) --}}
<div id="verify-modal" class="fixed inset-0 z-[99999] hidden" role="dialog" aria-modal="true"
    aria-labelledby="verify-title">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40"></div>

    <!-- Panel -->
    <div class="relative mx-auto my-10 max-w-lg w-[92%]">
        <div class="bg-white rounded-xl shadow-xl border border-gray-200">
            <div class="px-5 pt-4 pb-2 flex items-start justify-between">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-lock-closed class="w-5 h-5 text-red-500" />
                    <h3 id="verify-title" class="text-lg font-semibold text-gray-900">Security Verification Required
                    </h3>
                </div>
                <button type="button" class="p-1 text-gray-400 hover:text-gray-600" data-modal-close
                    aria-label="Close">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            <div class="px-5 pb-4">
                <p class="text-sm text-gray-600 mb-3">Enter your Master Password to edit the Master Passcode.</p>

                <div class="relative">
                    <input id="verify-password" type="password" autocomplete="current-password"
                        class="w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 border-gray-300"
                        placeholder="Enter your Master Password">
                    <!-- eye -->
                    <button type="button"
                        class="absolute inset-y-0 right-0 w-10 grid place-items-center text-gray-400 hover:text-gray-600"
                        data-toggle="visibility" data-target="verify-password" aria-label="Show password">
                        <x-heroicon-o-eye data-eye class="w-5 h-5" />
                        <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                    </button>
                </div>

                <p id="verify-error" class="text-sm text-red-600 mt-2 hidden"></p>

                <div class="mt-4 flex items-center gap-3">
                    <button type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-indigo-500 text-white hover:bg-indigo-600 disabled:opacity-60"
                        id="verify-submit">
                        <x-heroicon-o-lock-closed class="w-4 h-4" />
                        Verify & Edit
                    </button>
                    <button type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                        data-modal-close>
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>



@push('js')

<script>
    document.addEventListener('DOMContentLoaded', () => {

        window.Parsley.addAsyncValidator('useremailcheck', function(xhr) {
            // Expecting { valid: true/false }
            const response = xhr.responseJSON || {};
            return response.valid === true;
        });


        const shiftStartInput = document.querySelector('input[name="shift_start_time"]');

if (shiftStartInput) {
    let lastValue = shiftStartInput.value
        ? flatpickr.parseDate(shiftStartInput.value, "h:i K")
        : null;

    flatpickr(shiftStartInput, {
        enableTime: true,
        noCalendar: true,
        dateFormat: "h:i K",
        time_24hr: false,

        onClose: function (_, dateStr) {
            const newValue = dateStr
                ? flatpickr.parseDate(dateStr, "h:i K")
                : null;

            const changed =
                (lastValue && newValue && newValue.getTime() !== lastValue.getTime()) ||
                (!lastValue && newValue);

            if (changed) {
                // Optional: AJAX save OR just update hidden input
                lastValue = newValue;
            }
        }
    });
}

const shiftEndInput = document.querySelector('input[name="shift_end_time"]');

if (shiftEndInput) {
    let lastValue = shiftEndInput.value
        ? flatpickr.parseDate(shiftEndInput.value, "h:i K")
        : null;

    flatpickr(shiftEndInput, {
        enableTime: true,
        noCalendar: true,
        dateFormat: "h:i K",
        time_24hr: false,

        onClose: function (_, dateStr) {
            const newValue = dateStr
                ? flatpickr.parseDate(dateStr, "h:i K")
                : null;

            const changed =
                (lastValue && newValue && newValue.getTime() !== lastValue.getTime()) ||
                (!lastValue && newValue);

            if (changed) {
                lastValue = newValue;
            }
        }
    });
}

    });
</script>

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
document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("social_security");

    if (!input) return;

    input.addEventListener("input", function (e) {
        let value = e.target.value.replace(/\D/g, ""); // remove non-digits
        value = value.substring(0, 8); // max 8 digits

        let formatted = "";

        if (value.length > 0) {
            formatted = value.substring(0, 3);
        }
        if (value.length >= 4) {
            formatted += "-" + value.substring(3, 5);
        }
        if (value.length >= 6) {
            formatted += "-" + value.substring(5, 8);
        }

        e.target.value = formatted;
    });
});
</script>




<script>
document.addEventListener('DOMContentLoaded', function () {

        // password visibility (delegated)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-toggle="visibility"]');
            if (!btn) return;
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            // Only allow visibility toggle if input is NOT disabled (i.e., verified)
            if (input.hasAttribute('disabled')) {
                // If not verified, open modal for verification
                if (input.id === 'verify-password') return; // Don't open modal for the modal's own input
                // Find related lock button and trigger modal
                const opener = document.querySelector(`[data-open-verify][data-field-id="${input.id}"]`);
                if (opener) opener.click();
                return;
            }

            const eye = btn.querySelector('[data-eye]');
            const eyeOff = btn.querySelector('[data-eye-off]');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            if (eye) eye.classList.toggle('hidden', !isPassword);
            if (eyeOff) eyeOff.classList.toggle('hidden', isPassword);
            btn.setAttribute('aria-label', isPassword ? 'Hide value' : 'Show value');
            btn.setAttribute('aria-pressed', String(isPassword));
        });

        const modal = document.getElementById('verify-modal');
        const verifyInput = document.getElementById('verify-password');
        const verifyError = document.getElementById('verify-error');
        const verifyBtn = document.getElementById('verify-submit');

        let targetFieldId = null;
        let verifyUrl = null;


        // open modal from lock buttons
        document.addEventListener('click', function(e) {
            const opener = e.target.closest('[data-open-verify]');
            if (!opener) return;

            targetFieldId = opener.getAttribute('data-field-id');

                verifyUrl = opener.getAttribute('data-verify-url');


           const field = document.getElementById(targetFieldId);

        // Only block if it is an INPUT and already enabled
        if (field && field.tagName !== 'P' && !field.hasAttribute('disabled')) return;


            openModal();
        });

        // close modal (X or Cancel or backdrop)
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-modal-close]') || e.target.closest('[data-modal-close]')) {
                closeModal();
            }
            if (e.target === modal) { // click backdrop
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        function openModal() {
            verifyInput.value = '';
            verifyError.textContent = '';
            verifyError.classList.add('hidden');
            modal.classList.remove('hidden');
            setTimeout(() => verifyInput.focus(), 0);
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

   

   

    // Submit verification
    verifyBtn.addEventListener('click', async function () {

        if (!verifyUrl) return;

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const response = await fetch(verifyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                password: verifyInput.value,
                field_name: targetFieldId
            })
        });

        const data = await response.json();

        if (data.success) {
                    const field = document.getElementById(targetFieldId);

            if (!field) return;

            // Set decrypted value
            field.value = data.ssn || '';

            field.removeAttribute('disabled');
            field.classList.remove('bg-gray-100', 'cursor-not-allowed');
            field.type = 'text';

            // Re-bind parsley validation
            if (window.$ && $(field).parsley) {
                $(field).parsley().reset();
            }

            // Optional: remove lock button after verification
            const lockBtn = document.querySelector(
                `[data-open-verify][data-field-id="${targetFieldId}"]`
            );

            if (lockBtn) {
                lockBtn.remove();
            }

            modal.classList.add('hidden');
        } else {
            verifyError.textContent = data.message;
            verifyError.classList.remove('hidden');
        }
    });


});
</script>



@endpush
