
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6 bg-gray-50">
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

                <!-- Phone -->
                <!-- <div class="mb-4">
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>

                    {!! html()->text('phone', old('phone', $customer->phone ?? ''))->class([
                        'w-full rounded-md border focus:outline-none px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500',
                        'border-red-500' => $errors->has('phone'),
                        'border-gray-300' => !$errors->has('phone'),
                    ])->attributes([
                        'maxlength' => 15,
                        'data-parsley-type' => 'digits',
                        'data-parsley-maxlength' => 15,
                        'placeholder' => 'Enter phone number',
                        'id' => 'phone',
                        'autocomplete' => 'off',
                    ])->required() !!}

                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div> -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>

                    {!! html()->email('email', old('email', $customer->email ?? ''))->class([
                        'w-full rounded-md border focus:outline-none px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500',
                        'border-red-500' => $errors->has('email'),
                        'border-gray-300' => !$errors->has('email'),
                    ])->attributes([
                        'maxlength' => 100,
                        'data-parsley-maxlength' => 100,
                        'placeholder' => 'Enter Email-Id',
                        'id' => 'email',
                        'autocomplete' => 'off',
                    ])->required() !!}

                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    {!! html()->text('phone', old('phone', $customer->phone ?? ''))->class([
                        'phone-input w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                        'border-red-500' => $errors->has('phone'),
                        'border-gray-300' => !$errors->has('phone'),
                    ])->attributes([
                        'maxlength' => 14,
                        'data-parsley-pattern' => '^\(\d{3}\) \d{3}-\d{4}$',
                        'data-parsley-error-message' => 'Please enter phone number in format (123)456-7890',
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
            @php

            if(isset($customer)){
                $companyPhone = $customer->billingAddress->phone
                    ?? $customer->shippingAddress->phone
                    ?? optional($customer->addresses->first())->phone
                    ?? '';
            }
            
            @endphp

            {{-- Company Phone --}}
         
            <div class="mb-4">
                <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-1">Company Phone</label>
                {!! html()->text('company_phone', old('company_phone', $companyPhone ?? ''))->class([
                    'phone-input w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('company_phone'),
                    'border-gray-300' => !$errors->has('company_phone'),
                ])->attributes([
                    'maxlength' => 14,
                    'data-parsley-pattern' => '^\(\d{3}\) \d{3}-\d{4}$',
                    'data-parsley-error-message' => 'Please enter phone number in format (123)456-7890',
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
                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                {!! html()->text('website', old('website', isset($customer) ? $customer->addresses->first()->website ?? '' : ''))->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                    'border-red-500' => $errors->has('website'),
                    'border-gray-300' => !$errors->has('website'),
                ])->attributes([
                    'maxlength' => 255,
                    'data-parsley-type' => 'url',
                    'placeholder' => 'https://example.com',
                    'id' => 'website',
                    'autocomplete' => 'url',
                ]) !!}


                @error('website')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

       <!-- Addresses -->
        <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4">Addresses</h2>

            <!--  Billing Address  -->
            <div class="mb-4">
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
            </div>

            <!--  Delivery Address  -->
            <div>
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
            </div>
        </div>

</div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6 bg-gray-50">

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
                        ->required()
                    !!}

                    @error('is_credit_account')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

            </div>



            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-1">Approved By</label>
                
                {!! html()->text('approved_by', old('approved_by', $customer->approved_by ?? ''))->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm   ',
                    'border-red-500' => $errors->has('approved_by'),
                    'border-gray-300' => !$errors->has('approved_by'),
                ])->attributes([
                    'maxlength' => 200,
                    'data-parsley-maxlength' => 200,
                    'id' => 'approved_by',
                ]) !!}

                @error('approved_by')
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

                {!! html()->text('applicationcom', old('applicationcom', $customer->applicationcom ?? ''))->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 bg-white',
                        'border-red-500' => $errors->has('applicationcom'),
                        'border-gray-300' => !$errors->has('applicationcom'),
                    ])->attributes([
                        'id' => 'applicationcom',
                        'placeholder' => 'MM-DD-YYYY',
                        'autocomplete' => 'off',
                    ]) !!}

                    @error('applicationcom')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror


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
                <label class="block text-sm font-medium text-gray-700 mb-1">Approved By</label>

                {!! html()->text('approved_by', old('approved_by', $customer->approved_by ?? ''))->class([
                    'w-full border rounded-md px-3 py-2 text-sm shadow-sm',
                    'border-red-500' => $errors->has('approved_by'),
                    'border-gray-300' => !$errors->has('approved_by'),
                ])->attributes([
                    'maxlength' => 200,
                    'data-parsley-maxlength' => 200,
                    'id' => 'approved_by',
                ]) !!}

                @error('approved_by')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror


            </div>
            </div>

            <p class="text-sm text-gray-500 mb-2">Requires approved tax document</p>

            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload Date</label>

                    {!! html()->date('tax_document_upload_date', old('tax_document_upload_date', $customer->tax_document_upload_date ?? null))->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 bg-white',
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

                    {!! html()->date('tax_document_valid_until', old('tax_document_valid_until', $customer->tax_document_valid_until ?? null))->class([
                        'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 bg-white',
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
           


            <div class="flex items-center justify-between bg-gray-50 rounded-md py-2 mb-4">
                <div>
                    @isset($customer->tax_document_upload_date)

                    <p class="text-sm font-medium text-gray-800"> {{$customer->media->original_file_name}}</p>
                    <p class="text-xs text-gray-500"> Uploaded: {{ \Carbon\Carbon::parse($customer->tax_document_upload_date)->format(config('app.date.date_format')) }}
                    </p>
                    @endisset
                </div>
                <div class="flex gap-2">
                 
                    <svg class="w-5 h-5 text-gray-600 hover:text-blue-600 cursor-pointer" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" />
                    <circle cx="12" cy="12" r="3" />
                    </svg>
                  
                    <svg class="w-5 h-5 text-gray-600 hover:text-red-600 cursor-pointer" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m5 0V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" />
                    </svg>
                </div>
            </div>
{!! html()->file('tax_document')->class([
                'w-full border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500',
                'border-red-500' => $errors->has('tax_document'),
                'border-gray-300' => !$errors->has('tax_document'),
            ])->attributes([
                'id' => 'tax_document',
                'accept' => 'application/pdf,image/*',
            ]) !!}
    
            
            @endif 

            <div class="flex flex-col md:flex-row gap-4">
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
        </div>
    </div>

<div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm overflow-x-auto">
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
document.addEventListener('DOMContentLoaded', function() {
    // Function to format phone number
    function formatPhoneNumber(value) {
        const digits = value.replace(/\D/g, ''); // Remove all non-digits
        
        if (digits.length >= 6) {
            return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 10)}`;
        } else if (digits.length >= 3) {
            return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
        } else if (digits.length > 0) {
            return `(${digits}`;
        }
        return '';
    }
    
    // Function to handle phone input formatting
    function initPhoneInput(input) {
        // Format phone number as user types
        input.addEventListener('input', function(e) {
            const cursorPosition = e.target.selectionStart;
            const oldValue = e.target.value;
            const newValue = formatPhoneNumber(oldValue);
            
            e.target.value = newValue;
            
            // Maintain cursor position
            if (newValue.length >= oldValue.length) {
                e.target.setSelectionRange(cursorPosition + 1, cursorPosition + 1);
            } else {
                e.target.setSelectionRange(cursorPosition, cursorPosition);
            }
        });
        
        // Prevent non-numeric input
        input.addEventListener('keydown', function(e) {
            // Allow: backspace, delete, tab, escape, enter
            if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true) ||
                // Allow: home, end, left, right, down, up
                (e.keyCode >= 35 && e.keyCode <= 40)) {
                return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
        
        // Handle paste events
        input.addEventListener('paste', function(e) {
            setTimeout(function() {
                e.target.value = formatPhoneNumber(e.target.value);
            }, 1);
        });
    }
    
    // Initialize all phone inputs
    const phoneInputs = document.querySelectorAll('.phone-input');
    phoneInputs.forEach(function(input) {
        initPhoneInput(input);
    });
});
document.addEventListener('DOMContentLoaded', function () {
    flatpickr("#applicationcom", {
        dateFormat: "m-d-Y",
        allowInput: true,
        minDate: "today",
        defaultDate: null,
    });
});
document.addEventListener('DOMContentLoaded', function () {
    flatpickr("#tax_document_upload_date", {
        dateFormat: "m-d-Y",
        allowInput: true,
        minDate: "today",
        defaultDate: null,
    });
});
document.addEventListener('DOMContentLoaded', function () {
    flatpickr("#tax_document_valid_until", {
        dateFormat: "m-d-Y",
        allowInput: true,
        minDate: "today",
        defaultDate: null,
    });
});
</script>
@endpush
