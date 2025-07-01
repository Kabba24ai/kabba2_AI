
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 bg-gray-50">
        <!-- Personal Information -->
        <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4">Personal Information</h2>

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
                        'data-parsley-type' => 'email',
                        'data-parsley-trigger' => 'change',
                        'data-parsley-remote' => route('admin.crm.customer_portal.check.email.unique', [
                            'except' => $customer->id ?? null, 
                        ]),
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
                {!! html()->text('company_website', old('company_website', isset($customer) ? $customer->company_website ?? '' : ''))->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('company_website'),
                    'border-gray-300' => !$errors->has('company_website'),
                ])->attributes([
                    'maxlength' => 255,
                    'data-parsley-type' => 'url',
                    'placeholder' => 'https://example.com',
                    'id' => 'company_website',
                    'autocomplete' => 'url',
                ]) !!}


                @error('company_website')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

       <!-- Addresses -->
        <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4">Addresses</h2>
            <div x-data="{ showAddressModal: false }">
                <a href="javascript:void(0)"
                @click="showAddressModal = true"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                    + Add Addresses
                </a>
 
                <!-- Modal -->
                <div
                    x-show="showAddressModal"
                    x-transition
                    x-cloak
                    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
                    <div
                        @click.away="showAddressModal = false"
                        class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-xl p-6 space-y-5 border border-gray-200 dark:border-gray-700" >
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Add Address</h3>
                            <button @click="showAddressModal = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
                        </div>
            
                        <!-- Address form goes here -->
                        
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Full name</label>
                                    <input type="text" placeholder="Enter name"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Phone</label>
                                    <input type="text" placeholder="Enter phone"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Zip code</label>
                                    <input type="text" placeholder="Enter zip code"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" placeholder="Enter email"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500">
                                </div>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">Address</label>
                                <input type="text" placeholder="Enter address"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">State</label>
                                    <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700">
                                            <option value="">Select State</option>
                                            <option value="NY">New York</option>
                                            <option value="CA">California</option>
                                            <option value="TX">Texas</option>
                                            <option value="FL">Florida</option>
                                            <option value="IL">Illinois</option>
                                            <!-- Add more states as needed -->
                                        </select>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">City</label>
                                    <input type="text" placeholder="Enter city"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500">
                                </div>
                            </div>

            
                            <!-- Add more fields as needed -->
            
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="showAddressModal = false"
                                    class="px-4 py-2 text-sm rounded border border-gray-300 bg-white dark:bg-gray-700 dark:text-white">Cancel</button>
                                <button type="submit"
                                    class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-blue-700">Add </button>
                            </div>
                        
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white dark:bg-gray-900 mt-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Address</th>
                            <th class="px-4 py-2">Zip Code</th>
                            <th class="px-4 py-2">Country</th>
                            <th class="px-4 py-2">State</th>
                            <th class="px-4 py-2">City</th>
                            <th class="px-4 py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="">
                        <tr class="">
                            <td class="px-4 py-2">1</td>
                            <td class="px-4 py-2">County Road</td>
                            <td class="px-4 py-2">333333</td>
                            <td class="px-4 py-2">US</td>
                            <td class="px-4 py-2">Texas</td>
                            <td class="px-4 py-2">Pearland</td>
                            <td class="px-4 py-2 text-center space-x-2">
                                <a href="#">
                                    <button class="text-green-600 hover:text-green-800" title="Edit">
                                        <x-heroicon-o-pencil class="w-5 h-5" />
                                    </button>
                                </a>
                                <a href="#">
                                    <button class="text-red-600 hover:text-red-800" title="Delete">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2">2</td>
                            <td class="px-4 py-2">County Road</td>
                            <td class="px-4 py-2">333333</td>
                            <td class="px-4 py-2">US</td>
                            <td class="px-4 py-2">Texas</td>
                            <td class="px-4 py-2">Pearland</td>
                            <td class="px-4 py-2 text-center space-x-2">
                               <a href="#">
                                    <button class="text-green-600 hover:text-green-800" title="Edit">
                                        <x-heroicon-o-pencil class="w-5 h-5" />
                                    </button>
                                </a>
                                <a href="#">
                                    <button class="text-red-600 hover:text-red-800" title="Delete">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!--  Billing Address  -->
            <!-- <div class="mb-4">
                {{ html()->label('Billing Address', 'billing_address')->class('block text-sm font-medium text-gray-700 mb-1') }}

                {{ html()->textarea('billing_address', old('billing_address', isset($customer->billingAddress) ? $customer->billingAddress->address : ''))->id('billing_address')->attributes([
                    'rows' => 2,
                    'maxlength' => 255, 
                    'data-parsley-maxlength' => 255,
                    'placeholder' => 'Enter billing address',
                ])->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('billing_address'),
                    'border-gray-300' => !$errors->has('billing_address'),
                ]) }}

                @error('billing_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>-->

            <!--  Delivery Address  -->
            <!--<div>
                {{ html()->label('Delivery Address', 'delivery_address')->class('block text-sm font-medium text-gray-700 mb-1') }}

                {{ html()->textarea('delivery_address', old('delivery_address', $customer->shippingAddress->address ?? ''))->id('delivery_address')->attributes([
                    'rows' => 2,
                    'maxlength' => 255,
                    'data-parsley-maxlength' => 255,
                    'placeholder' => 'Enter delivery address',
                ])->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('delivery_address'),
                    'border-gray-300' => !$errors->has('delivery_address'),
                ]) }}

                @error('delivery_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div> -->
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

                
                    {!! html()->text('credit_limit', old('credit_limit', $customer->credit_limit ?? ''))->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm  bg-gray-100 ',
                        'border-red-500' => $errors->has('credit_limit'),
                    'border-gray-300' => !$errors->has('credit_limit'),
                ])->attributes([
                    'maxlength' => 200,
                    'data-parsley-maxlength' => 200,
                    'placeholder' => 'Enter Approved By',
                    'id' => 'credit_limit',
                   
                ]) !!}

                @error('credit_limit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

            </div>
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-1">Application Completed</label>

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
            <div class="flex flex-col md:flex-row gap-4">

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
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm flex flex-col justify-between">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Tax Exempt Upload</h2>
            
            @if (isset($customer) && !$customer->tax_document_media_id)

            {!! html()->file('tax_document')->class([
                'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                'border-red-500' => $errors->has('tax_document'),
                'border-gray-300' => !$errors->has('tax_document'),
            ])->attributes([
                'id' => 'tax_document',
                'accept' => 'application/pdf,image/*',
            ]) !!}

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
                    <svg class="w-5 h-5 text-gray-600 hover:text-red-600 cursor-pointer" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m5 0V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" />
                    </svg>
                </div>
            </div>
             @endisset
{!! html()->file('tax_document')->class([
                'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                'border-red-500' => $errors->has('tax_document'),
                'border-gray-300' => !$errors->has('tax_document'),
            ])->attributes([
                'id' => 'tax_document',
                'accept' => 'application/pdf,image/*',
            ]) !!}
    
            
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
