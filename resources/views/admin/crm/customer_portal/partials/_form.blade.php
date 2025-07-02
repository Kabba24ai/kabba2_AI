
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 bg-gray-50">
        <!-- Personal Information -->
        <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4">Personal Information</h2>

            {!! html()->text('alladdresslist', $addressListJson ?? '')->class('hidden')->attributes([
                    'id' => 'alladdresslist',
                    'autocomplete' => 'off'
                ]) !!}

               


                 <!-- First Name  -->
                <div class="mb-4">
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>

                    {!! html()->text('first_name', old('first_name', $customer->first_name ?? ''))->class([
                        'w-full rounded-md border focus:outline-none px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500',
                        'border-red-500' => $errors->has('first_name'),
                        'border-gray-300' => !$errors->has('first_name'),
                    ])->attributes([
                        'maxlength' => 100,
                        'data-parsley-maxlength' => 100,
                        'placeholder' => 'Enter First Name',
                        'id' => 'first_name',
                        'autocomplete' => 'off',
                    ])->required() !!}

                    @error('first_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                 <!-- Last Name  -->
                <div class="mb-4">
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>

                    {!! html()->text('last_name', old('last_name', $customer->last_name ?? ''))->class([
                        'w-full rounded-md border focus:outline-none px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500',
                        'border-red-500' => $errors->has('last_name'),
                        'border-gray-300' => !$errors->has('last_name'),
                    ])->attributes([
                        'maxlength' => 100,
                        'data-parsley-maxlength' => 100,
                        'placeholder' => 'Enter Last Name',
                        'id' => 'last_name',
                        'autocomplete' => 'off',
                    ])->required() !!}

                    @error('last_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                
                <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>

                        {!! html()->email('email', old('email', $customer->email ?? ''))->class([
                        'w-full rounded-md border focus:outline-none px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500',
                        'border-red-500' => $errors->has('email'),
                        'border-gray-300' => !$errors->has('email'),
                    ])->attributes([
                        'maxlength' => 100,
                        'placeholder' => 'Enter Email',
                        'id' => 'email',
                        'autocomplete' => 'off',
                        'name' => 'email', 
                        'data-parsley-type' => 'email',
                        'data-parsley-trigger' => 'change',
                        'data-parsley-remote' => route('admin.crm.customer_portal.check.email.unique', [
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
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    {!! html()->text('phone', old('phone', $customer->phone ?? ''))->class([
                        'masked-phone w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                        'border-red-500' => $errors->has('phone'),
                        'border-gray-300' => !$errors->has('phone'),
                    ])->attributes([
                        'maxlength' => 14,
                        'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                        'data-parsley-error-message' => 'Please enter phone number in format (123) 456-7890',
                        'placeholder' => '(123) 456-7890',
                        'id' => 'phone',
                        'autocomplete' => 'tel',
                    ])->required() !!}
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                 <!-- Status  -->
                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>

                    {!! html()
                        ->select('status', [
                            'Active' => 'Active',
                            'Inactive' => 'Inactive',
                            'Archived' => 'Archived',
                        ], old('status', $customer->status ?? ''))
                        ->id('status')
                        ->class([
                            'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                            'border-red-500' => $errors->has('status'),
                            'border-gray-300' => !$errors->has('status'),
                        ])
                        ->required()
                    !!}

                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

        </div>

        <!-- Company Information -->
        <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4">Company Information</h2>

            {{-- Company Name --}}
            <div class="mb-4">
                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>

                {!! html()->text('company_name', old('company_name', $customer->company_name ?? ''))->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
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
                    'masked-phone w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('company_phone'),
                    'border-gray-300' => !$errors->has('company_phone'),
                ])->attributes([
                    'maxlength' => 14,
                    'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                    'data-parsley-error-message' => 'Please enter phone number in format (123) 456-7890',
                    'placeholder' => '(123) 456-7890',
                    'id' => 'company_phone',
                    'autocomplete' => 'tel',
                ]) !!}
                @error('company_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <!-- {{-- Website --}} -->
            <div>
                <label for="company_website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                <!-- {!! html()->text('company_website', old('company_website', isset($customer) ? $customer->company_website ?? '' : ''))->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('company_website'),
                    'border-gray-300' => !$errors->has('company_website'),
                ])->attributes([
                    'maxlength' => 255,
                    'data-parsley-type' => 'url',
                    'placeholder' => 'https://example.com',
                    'id' => 'company_website',
                    'autocomplete' => 'url',
                ]) !!} -->
                <div class="flex flex-col md:flex-row gap-4 mb-4">
                {{-- Protocol --}}
                {!! html()->select('website_protocol', [
                        'https://' => 'https://',
                        'http://' => 'http://',
                    ], old('website_protocol', $website_protocol))
                    ->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                        'border-red-500' => $errors->has('website_protocol'),
                        'border-gray-300' => !$errors->has('website_protocol'),
                    ])
                    ->id('website_protocol') !!}
                       

                {{-- Website Name --}}
                {!! html()->text('company_website', old('company_website',$company_website))
                    ->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
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
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
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

       <!-- Addresses -->
        <div class="bg-white rounded-lg shadow border border-gray-200 p-6">

        <div class="flex justify-between">
       
        <h2 class="text-lg font-semibold">Addresses</h2>

        <!-- Somewhere else on the page -->
<a href="javascript:void(0)" id="openAddressModal"
   class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600">
   + Add 
</a>

                </div>
<div id="addressList" class="flex flex-col gap-y-2">
           
              
           </div>


           
        </div>

</div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 bg-gray-50 mt-6">

        <!-- Customer Account -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Customer Account</h2>

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
                            'w-full border rounded-md px-3 py-2 text-sm shadow-sm ',
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
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm',
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

                
                    <!-- {!! html()->text('credit_limit', old('credit_limit', $customer->credit_limit ?? ''))->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm  bg-gray-100 ',
                        'border-red-500' => $errors->has('credit_limit'),
                    'border-gray-300' => !$errors->has('credit_limit'),
                ])->attributes([
                    'maxlength' => 200,
                    'data-parsley-maxlength' => 200,
                    'placeholder' => 'Enter Approved By',
                    'id' => 'credit_limit',
                   
                ]) !!} -->
                @php
                    $creditOptions = collect(range(1000, 20000, 1000))->mapWithKeys(function ($value) {
                        return [$value => number_format($value)];
                    });
                @endphp

                {!! html()
                    ->select('credit_limit', $creditOptions->toArray(), old('credit_limit', $customer->credit_limit ?? ''))
                    ->id('credit_limit')
                    ->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm',
                        'border-red-500' => $errors->has('credit_limit'),
                        'border-gray-300' => !$errors->has('credit_limit'),
                    ])
                !!}

                @error('credit_limit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

            </div>
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-1">Completed On</label>

                    {!! html()->text('account_application_completed', old('account_application_completed', $customer->account_application_completed ?? ''))->class([
                        'w-full border datepicker unded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 bg-white',
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
            <div class="flex flex-col md:flex-row gap-4 mt-4">

                        <div class="w-full">
                            <span class="inline-block rounded-full bg-green-100 text-green-800 text-sm font-semibold px-2 py-1">
                            Good Standing
                        </span>
            </div>





                    </div>

        </div>

        <!-- Tax Exempt -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
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
                            'w-full border rounded-md px-3 py-2 text-sm shadow-sm ',
                            'border-red-500' => $errors->has('tax_status'),
                            'border-gray-300' => !$errors->has('tax_status'),
                        ])
                        ->required()
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
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm',
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

                    {!! html()->text('tax_document_upload_date', old('tax_document_upload_date', $customer->tax_document_upload_date ?? null))->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 bg-white datepicker',
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

                    {!! html()->text('tax_document_valid_until', old('tax_document_valid_until', $customer->tax_document_valid_until ?? null))->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 bg-white datepicker',
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

        <!-- Tax Exempt Upload -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm flex flex-col ">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Tax Exempt Upload</h2>
            
            @if (isset($customer) && !$customer->tax_document_media_id)
                {!! html()->text('tax_document_media_id', $customer->tax_document_media_id ?? '')->class('hidden')->attributes([
                    'id' => 'tax_document_media_id',
                    'autocomplete' => 'off'
                ]) !!}
            <div class="flex ">                
                <label for="tax_document" class="shadow-[0px_1px_3px_0px_rgba(0,_0,_0,_0.2)] rounded-l-md px-3 py-2 bg-gray-200">Browse...</label>
                {!! html()->file('tax_document')->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('tax_document'),
                    'border-gray-300' => !$errors->has('tax_document'),
                ])->attributes([
                    'id' => 'tax_document',
                    'accept' => 'application/pdf,image/*',
                ]) !!}
            </div>


            @error('tax_document')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            @else 
           


            @isset($customer->tax_document_upload_date)
            <div class="flex items-center justify-between bg-gray-50 rounded-md py-2 mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-800"> {{$customer->media->original_file_name}}</p>
                    <p class="text-xs text-gray-500"> Uploaded: {{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) }}
                    </p>
                    
                </div>
                <div class="flex gap-2">
                    <a href="{{ !empty($customer) && $customer->media ? $customer->media->getUrl() : 'javascript:void(0)' }}" target="_blank">

                    <svg class="w-5 h-5 text-gray-600 hover:text-blue-600 cursor-pointer" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" />
                    <circle cx="12" cy="12" r="3" />
                    </svg>
                    </a>
                    <a href="javascript:void(0);" onclick="confirmAndDelete('{{ $customer->id }}')" title="Delete">
                    <svg class="w-5 h-5 text-gray-600 hover:text-red-600 cursor-pointer" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m5 0V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" />
                    </svg>
                    </a>
                </div>
            </div>
             @endisset
             
            <div class="flex ">
                <label for="tax_document" class="shadow-[0px_1px_3px_0px_rgba(0,_0,_0,_0.2)] rounded-l-md px-3 py-2 bg-gray-200">Browse...</label>
            {!! html()->file('tax_document')->class([
                'w-full border rounded-r-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                'border-red-500' => $errors->has('tax_document'),
                'border-gray-300' => !$errors->has('tax_document'),
            ])->attributes([
                'id' => 'tax_document',
                'accept' => 'application/pdf,image/*',
            ]) !!}
            </div>

            
            @endif 
            @isset($customer->tax_document_upload_date)

            <div class="flex flex-col md:flex-row gap-4 mt-3 mb-3">
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                </div>
                <div class="w-full">
                    <span class="inline-block float-right rounded-full bg-yellow-100 text-yellow-800 text-xs font-medium px-3 py-1">
                        Pending Review
                    </span>
                </div>
            </div>

            <div class="flex justify-between gap-4 mt-auto">
                <button class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Approve
                </button>
                <button class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Reject
                </button>
            </div>
            @endisset
        </div>
    </div>

<div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm overflow-x-auto mt-6">
  <h2 class="text-lg font-semibold text-gray-900 mb-4">Order History (Read Only)</h2>

  <table class="min-w-full text-sm text-left">
    <thead class="text-gray-500 border-b">
      <tr>
        <th class="px-4 py-2 font-medium uppercase">Order ID</th>
        <th class="px-4 py-2 font-medium uppercase">Product</th>
        <th class="px-4 py-2 font-medium uppercase">Amount</th>
        <th class="px-4 py-2 font-medium uppercase">Payment Method</th>
        <th class="px-4 py-2 font-medium uppercase">Status</th>
        <th class="px-4 py-2 font-medium uppercase">Date</th>
      </tr>
    </thead>
    <tbody>
      <tr class="border-b last:border-0">
        <td class="px-4 py-3 font-semibold text-gray-900">ORD-003</td>
        <td class="px-4 py-3 text-gray-700">Enterprise Widget</td>
        <td class="px-4 py-3 text-gray-700">$599.99</td>
        <td class="px-4 py-3 text-gray-700">Bank Transfer</td>
        <td class="px-4 py-3">
          <span class="inline-block text-xs font-medium bg-green-100 text-green-800 px-3 py-1 rounded-full">
            Paid
          </span>
        </td>
        <td class="px-4 py-3 text-gray-700">Jun 17, 2024</td>
      </tr>
    </tbody>
  </table>
</div>


@push('js')
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
@endpush
