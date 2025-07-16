
                         {{ html()->form()->id('customerForm')->attributes([
                            'autocomplete' => 'off',
                            
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                         {!! html()->text('alladdresslist')->class('hidden')->attributes([
                            'id' => 'alladdresslist',
                            'autocomplete' => 'off'
                        ]) !!}



            <div class="bg-white p-6 rounded shadow mb-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Title and Description -->
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Customer Account Management</h2>
                        <p class="text-sm text-gray-500">Manage customer account information and administrative settings</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:gap-2">
                        <span class="text-sm text-gray-700 mr-2 sm:mr-0 sm:mb-1 mb-1">Admin Actions:</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <button  type="button" class="bg-red-600 text-white px-3 py-1 rounded text-sm">Suspend Account</button>
                            <button type="button" class="bg-yellow-500 text-white px-3 py-1 rounded text-sm">Reset Password</button>
                            <button id="editBtn" type="button" class="bg-blue-600 inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-md hover:bg-green-700 transition"> Edit Information</button>    
                            <button  type="submit" id="saveBtn" style="display:none;" class="saveBtn bg-green-600 text-white px-3 py-1 rounded text-sm">Save Changes</button>
                            <button type="button" id="cancelBtn" style="display:none;" class="bg-gray-700 text-white px-3 py-1 rounded text-sm">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                <!-- Personal Information -->
                <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-4">
                        <x-heroicon-o-user class="w-5 h-5 mr-2 text-gray-500" /> Personal Information
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-4 text-sm text-gray-700">

                        <div>
                            <label class="text-gray-700 mb-1">First Name</label>
                            <div class="static-view">{{ $customer->first_name ?? '' }}</div>
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
                            <label class="text-gray-700 mb-1">Last Name</label>
                            <div class="static-view">{{ $customer->last_name ?? '' }}</div>
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
                            <label class="text-gray-700 text-sm mb-1 block">Email Address</label>
                            <div class=" static-view">
                                <p class="text-gray-900 flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-4 h-4 mr-2 text-gray-500" />{{ $customer->email ?? '' }}
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
                            <label class="text-gray-700 text-sm mb-1 block">Phone Number</label>
                            <div class=" static-view">
                                <p class="text-gray-900 flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 mr-2 text-gray-500" /> {{ App\Helpers\CustomHelper::formatPhone($customer->phone) ?? 'N/A' }}
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
                    <h3 class="text-md font-semibold mb-4 flex items-center gap-2">
                        <x-heroicon-o-building-office class="w-5 h-5 text-gray-500" /> Company Information
                    </h3>
                    <div class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                        <div class="col-span-2">
                            <label class="text-sm text-gray-700 mb-1">Company Name</label>
                            <div class="static-view">{{ $customer->company_name ?? '' }}</div>
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
                            <label class="text-gray-700 text-sm mb-1 block">Company Phone</label>
                            <div class=" static-view">
                                <p class="text-gray-900 flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 mr-2 text-gray-500" /> {{ App\Helpers\CustomHelper::formatPhone($customer->company_phone) ?? 'N/A' }}
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
                            <label class="text-gray-700 text-sm mb-1 block">Website</label>
                            <div class=" static-view">
                                <p class="text-gray-900 flex items-center gap-2">
                                      <x-heroicon-o-globe-alt class="w-4 h-4 mr-2 text-gray-500" /><a class="text-blue-600" href="{{ $customer->company_website ?? 'javascript:void(0)' }}">{{ $customer->company_website ?? 'N/A'}}</a>
                                </p>
                            </div>
                            <!-- <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="{{ $customer->company_website  }}" /> -->

                            {!! html()->text('company_website', old('company_website', $customer->company_website ?? ''))
                            ->class([
                                'edit-view pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300',
                                'border-red-500' => $errors->has('company_website'),
                            ])
                            ->attributes([
                                'placeholder' => 'https://example.com',
                                'id' => 'company_website',
                            ]) !!}


                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
          @foreach ($customer->addresses as $index => $addresse)
            <div class="address-block bg-white rounded-lg shadow-sm p-5 border border-gray-200" data-index="{{ $index }}">
                <input type="hidden" name="addresses[{{ $index }}][address_id]" value="{{ $addresse->id }}" class="address_id">

                <h3 class="text-base font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-map-pin class="w-5 h-5 mr-2 text-gray-500" />
                    {{ $addresse->type }} Address
                    @if ($addresse->is_primary)
                        - <span class="text-xs text-red-800 bg-red-100 px-2 py-1 rounded">Default Address</span>  
                    @endif
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm text-gray-700 mb-1 edit-view">Address</label>
                        <div class="static-view text-gray-900  text-sm">
                            {{ $addresse->address }} , {{ $addresse->city }} , {{ $addresse->state->name }} , {{ $addresse->zip_code }}.
                        </div>

                        {!! html()->text("addresses[$index][address]", old("addresses.$index.address",    $addresse->address ?? ''))
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
                            ])
                            ->required() !!}
                    </div>
                </div>
            </div>
          @endforeach

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                <!-- Account Status -->
                <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-base font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                     Account Status
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Account Status:</span>

                             @php
                $approved = $customer->is_credit_account == 1;
                $hasCreditLimit = !empty($customer->credit_limit);
            @endphp

                             @if ($approved && $hasCreditLimit)
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">Good Standing</span>

                @elseif ($approved && !$hasCreditLimit)
                                           <span class="bg-green-100 text-yellow-700 px-2 py-1 rounded-full text-xs font-medium">Pending</span>
                @else
                
                @endif
                            
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Account Approved:</span>
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
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Customer Since:</span>
                            <span class="text-xs">{{ App\Helpers\CustomHelper::formatDate($customer->created_at) ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Customer ID:</span>
                            <span class="text-xs">{{ $customer->unique_id }}</span>
                        </div>
                    </div>
                </div>

                <!-- Credit Information -->
                <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex gap-2 items-start mb-4">
                        <h3 class="text-base font-semibold flex items-center gap-2">
                            <x-heroicon-o-credit-card class="w-5 h-5 text-gray-600" />
                            Credit Information
                        </h3>
                        <span class="text-xs text-red-800 bg-red-100 px-2 py-1 rounded">Admin View</span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Credit Limit:</span>
                            <span class="text-right">
                                <span class="text-gray-900 font-semibold">{{ config('app.currency.code') }}{{ $customer->credit_limit ?? 0 }}</span>
                                <a href="#" class="ml-1 text-blue-500 text-xs inline-flex items-center"><x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /></a>
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Current Balance:</span>
                            <span class="font-medium">$2,750.00 <a href="#" class="text-xs font-normal text-green-500 ml-1">Adjust</a></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Available Credit:</span>
                            <span class="text-green-600 font-medium">$12,250.00</span>
                        </div>
                        <div>
                            <label class="text-gray-700 mb-1 text-sm">Credit Utilization</label>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: 18%;"></div>
                            </div>
                            <div class="text-right text-xs text-gray-500 mt-0.5">18%</div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="bg-white p-4 rounded shadow border border-gray-200 mt-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document class="w-5 h-5 text-gray-500" />
                        <h2 class="text-base font-semibold text-gray-800">Tax Exempt Status</h2>
                        <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded">Admin Control</span>
                    </div>
                    <a href="javascript:void(0)" id="opentaxdocModal" class="px-4 mt-3 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                        Manage Tax Documents
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Left: Tax Info -->
                    <div class="space-y-2 text-sm text-gray-700">
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Tax Status:</span>
                            <span class="text-green-700 bg-green-100 px-2 py-1 rounded-full text-xs font-medium">{{ $customer->tax_status ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Valid Until:</span>
                            <span class="text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Uploaded:</span>
                            <span class="text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) ?? 'N/A' }}</span>
                        </div>
                    </div>

                     @if ($customer->media)
                            <!-- Right: File Card -->
                        <div class="border border-gray-200 rounded-lg p-4 text-sm bg-white w-full">
                                <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
                                    <!-- Left Side: Icon + File Info -->
                                    <div class="flex items-start gap-3 flex-1 min-w-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h10M7 11h10M7 15h10M5 19h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-800 truncate">{{$customer->media->original_file_name ?? ''}}</div>
                                        <div class="text-gray-500 text-xs">
                                        Status: 
                                        <span id="tax-status-{{ $customer->id }}" class="font-medium
                                            {{ $customer->tax_document_status === 'Rejected' ? 'text-red-600' :
                                            ($customer->tax_document_status === 'Approved' ? 'text-green-600' : 'text-yellow-600') }}">
                                            {{ $customer->tax_document_status ?? 'Pending Review' }}
                                        </span>

                                        </div>
                                    </div>
                                    </div>

                                    <!-- Right Side: Actions -->
                                    <div class="flex gap-4 text-sm justify-end sm:justify-start">
                                        <a href="{{ isset($customer->media) ? $customer->media->getUrl() : '' }}" class="text-blue-600">View</a>

                                        <button 
            class="text-green-600 tax-status-btn" 
            data-id="{{ $customer->id }}" 
            data-status="Approved">Approve</button>
        <button 
            class="text-red-600 tax-status-btn" 
            data-id="{{ $customer->id }}" 
            data-status="Rejected">Reject</button>

                                        <!-- <a href="#" class="text-green-600">Approve</a>
                                        <a href="#" class="text-red-600">Reject</a> -->


                                    </div>
                                </div>
                        </div>
                          @else
                           <div class="flex justify-center items-center ">
                                <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 text-sm p-4 rounded-md">
                                    No tax document has been uploaded yet.
                                </div>
                            </div>

                    @endif



                </div>
            </div>

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
                                <input type="file" id="tax_document" name="tax_document" class="hidden" accept=".pdf,.png,.jpg,.jpeg" onchange="handleFileChange(event)" />
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
                        <button type="submit"  class="saveBtn px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-teal-700">Upload Document</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

  {{ html()->form()->close() }}



@push('js')

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


@endpush