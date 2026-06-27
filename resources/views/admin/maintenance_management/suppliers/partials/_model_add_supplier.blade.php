<!-- Supplier Modal -->
<div id="AddSupplier" class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div
            class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-5xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full"
            onclick="event.stopPropagation()">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                <!-- <div class="flex items-start gap-3">

                    <div>
                        <h3 class="text-lg font-semibold text-gray-800" id="form-model-tital">Add New Supplier</h3>
                    </div>
                </div> -->
                <div class="flex items-center">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-3 rounded-lg mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-white">
                            <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                            <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                            <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                            <path d="M10 6h4"></path>
                            <path d="M10 10h4"></path>
                            <path d="M10 14h4"></path>
                            <path d="M10 18h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900" id="form-model-tital">Add New Supplier</h2>
                        <p class="text-gray-600 mt-1">Enter supplier details</p>
                    </div>
                </div>
                <button onclick="closeModal('AddSupplier')"
                    class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 space-y-6 overflow-y-auto ">

                {{ html()->form()->attributes([
                    'action' => route('admin.maintenance-management.suppliers.store'),
                    'method' => 'POST',
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'max-w-5xl',
                    'id'=>'supplierForm',
                     'enctype' => 'multipart/form-data'
                ])->open() }}

                <div class="bg-blue-50 p-4 rounded-xl">

                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="bg-blue-600 p-2 rounded-lg mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
                                <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                                <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                                <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                                <path d="M10 6h4"></path>
                                <path d="M10 10h4"></path>
                                <path d="M10 14h4"></path>
                                <path d="M10 18h4"></path>
                            </svg>
                        </div>Company Information
                    </h3>

                    <!--  Supplier Form -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 ">
                        <!-- Company Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">Company Name</label>

                            {!! html()->text('supplierCompany', old('supplierCompany'))
                            ->class([
                            'w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('supplierCompany'),
                            'border-gray-300' => !$errors->has('supplierCompany'),
                            ])
                            ->attributes([
                            'placeholder' => 'Enter company name',
                            'id' => 'supplierCompany',
                            'autocomplete' => 'off',
                            ])
                            ->required() !!}

                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>

                            {!! html()->email('supplierEmail', old('supplierEmail'))->class([
                            'w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('email'),
                            'border-gray-300' => !$errors->has('email'),
                            ])->attributes([
                            'placeholder' => 'company@example.com',
                            'id' => 'email',
                            'autocomplete' => 'off',
                            'name' => 'supplierEmail',
                            'data-parsley-type' => 'email',
                            'data-parsley-trigger' => 'change',
                            ]) !!}


                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>

                            {!! html()->text('supplierPhone', old('supplierPhone'))->class([
                            'masked-phone w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('supplierPhone'),
                            'border-gray-300' => !$errors->has('supplierPhone'),
                            ])->attributes([
                            'maxlength' => 14,
                            'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                            'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                            'placeholder' => '(xxx) xxx-xxxx',
                            'id' => 'supplierPhone',
                            'autocomplete' => 'tel',
                            ]) !!}
                        </div>

                        <!-- Website -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>

                            {!! html()->text('supplierWebsite', old('supplierWebsite'))
                            ->type('url')
                            ->class([
                            'w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('supplierWebsite'),
                            'border-gray-300' => !$errors->has('supplierWebsite'),
                            ])
                            ->attributes([
                            'placeholder' => 'https://www.company.com',
                            'id' => 'supplierWebsite',
                            'autocomplete' => 'off',
                             'data-parsley-type' => 'url',
      'data-parsley-trigger' => 'change focusout',

            'data-parsley-pattern' => '^(https?:\/\/)?([a-zA-Z0-9.-]+)\.[a-zA-Z]{2,}$',
'data-parsley-error-message' => 'Enter a valid website URL (company.com or https://company.com)',
                            ]) !!}

                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>

                            {!! html()->text('supplierAddress', old('supplierAddress'))
                            ->class('w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->attributes(['placeholder' => '123 Main Street', 'id' => 'supplierAddress']) !!}
                        </div>

                        <!-- City -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>

                            {!! html()->text('supplierCity', old('supplierCity'))
                            ->class('w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->attributes(['placeholder' => 'City name', 'id' => 'supplierCity']) !!}
                        </div>

                        <!-- State -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>

                            <!-- {!! html()
                            ->select('supplierState',
                            $states->mapWithKeys(fn ($state) => [$state->id => $state->name])->toArray(),
                            old('supplierState')
                            )
                            ->id('supplierState')
                            ->class([
                            'w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('supplierState'),
                            ])->required()
                            !!} -->

                             {!! html()
                    ->select(
                    'supplierState',
                    ['' => '-- Select State --'] + $states->pluck('name', 'id')->toArray(),
                    old('supplierState')
                    )
                    ->id('supplierState')
                    ->class([
                    'w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                    'border-red-500' => $errors->has('supplierState'),
                    ])

                    !!}

                        </div>

                        <!-- Zip -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>

                            {!! html()->text('supplierZip', old('supplierZip'))
                            ->class([
                            'w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('supplierZip'),
                            'border-gray-300' => !$errors->has('supplierZip'),
                            ])
                            ->attributes([
                            'placeholder' => '12345',
                            'id' => 'supplierZip',
                            'autocomplete' => 'off',
                            ]) !!}

                        </div>

                        <!-- Country -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>

                            {!! html()->select('supplierCountry', [

                            'USA' => 'USA',
                            // 'Canada' => 'Canada',
                            // 'UK' => 'UK',
                            // 'India' => 'India',
                            ], old('supplierCountry'))
                            ->class('w-full px-3 py-3 border bg-white rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->id('supplierCountry') !!}

                        </div>

                        <!-- Tax ID -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tax ID</label>

                            {!! html()->text('supplierTax', old('supplierTax'))
                            ->class('w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->attributes(['placeholder' => 'US123456789', 'id' => 'supplierTax']) !!}


                        </div>

                        <!-- Supplier Category -->
                        <div class="hidden">
                            <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier Category</label>
                             <button type="button" onclick="openCategoryModal()" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add
                                </button>
                            </div>
                            <select id="supplierCategorysform" name="supplierCategory" class="w-full px-3 py-3  bg-white  border rounded-md text-sm focus:ring-green-500 focus:border-green-500" >
                                <option value="">Select category</option>

                            </select>
                        </div>

                        <!-- Tags -->
                        <div>

                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                                <button type="button" onclick="openTagModal()" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    + New Tag
                                </button>
                            </div>

                            <select id="supplierTags" name="tags[]" multiple

                                class="choices-select w-full rounded-md border border-gray-300 text-sm">
                            </select>
                        </div>
                        <!-- Payment Terms -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>

                            {!! html()->select('supplierPaymentTerms', [
                            '' => 'Select Payment Terms',
                            'Net 15' => 'Net 15',
                            'Net 30' => 'Net 30',
                            'Net 45' => 'Net 45',
                            'Net 60' => 'Net 60',
                            'Net 75' => 'Net 75',
                            ], old('supplierPaymentTerms'))
                            ->class('w-full px-3 py-3 border bg-white rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->id('supplierPaymentTerms') !!}

                        </div>


                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>


                            {!! html()->select('supplierStatus', [

                            'Active' => 'Active',
                            'Inactive' => 'Inactive',
                            'Pending' => 'Pending',
                            ], old('supplierStatus'))
                            ->class('w-full px-3 py-3 border bg-white rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->id('supplierStatus')->required() !!}

                        </div>

<!-- Preferred Supplier -->
<input type="hidden" name="is_preferred_supplier" value="0">

<div class="flex items-center gap-3 mt-4">
    <input
        type="checkbox"
        id="isPreferredSupplier"
        name="is_preferred_supplier"
        value="1"
        class="h-4 w-4 text-green-600 border-gray-300 rounded
               focus:ring-green-500"
    />

    <label for="isPreferredSupplier"
           class="text-sm font-medium text-gray-700 cursor-pointer">
        Designate as Preferred Supplier
    </label>
</div>


                    </div>

                </div>

                <!-- Primary Contact -->
                <div class="bg-green-50 p-4 rounded-xl mt-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="bg-green-600 p-2 rounded-lg mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>Primary Contact
                    </h3>

                    <!-- 3 inputs in one row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Contact Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>

                            {!! html()->text('primaryContactName', old('primaryContactName'))
                            ->class('w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->attributes(['placeholder' => 'John Doe', 'id' => 'primaryContactName']) !!}

                        </div>

                        <!-- Contact Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>

                            {!! html()->email('primaryContactEmail', old('primaryContactEmail'))->class([
                            'w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('email'),
                            'border-gray-300' => !$errors->has('email'),
                            ])->attributes([
                            'placeholder' => 'john@company.com',
                            'id' => 'primaryContactEmail',
                            'autocomplete' => 'off',
                            'name' => 'primaryContactEmail',
                            'data-parsley-type' => 'email',
                            'data-parsley-trigger' => 'change',
                            ]) !!}


                        </div>

                        <!-- Contact Phone -->
                        <div>
                            <label class=" block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>

                            {!! html()->text('primaryContactPhone', old('primaryContactPhone'))->class([
                            'masked-phone w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('primaryContactPhone'),
                            'border-gray-300' => !$errors->has('primaryContactPhone'),
                            ])->attributes([
                            'maxlength' => 14,
                            'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                            'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                            'placeholder' => '(xxx) xxx-xxxx',
                            'id' => 'primaryContactPhone',
                            'autocomplete' => 'tel',
                            ]) !!}


                        </div>
                    </div>
                </div>

                <!-- Technical Contact -->
                <div class="bg-blue-50 p-4 rounded-xl mt-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="bg-blue-600 p-2 rounded-lg mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>Technical Contact
                        <!-- <span class="text-sm font-normal text-gray-500 ml-2">(Optional)</span> -->
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Contact Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                            <input class="w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" type="text" name="technicalSupportName" id="technicalSupportName" placeholder="Jane Smith">
                        </div>

                        <!-- Contact Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>

                            <input class="w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500 border-gray-300" type="email" name="technicalSupportEmail" id="technicalSupportEmail" placeholder="jane@company.com" autocomplete="off" data-parsley-type="email" data-parsley-trigger="change">
                        </div>

                        <!-- Contact Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>

                            <input class="masked-phone w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500 border-gray-300" type="text" name="technicalSupportPhone" id="technicalSupportPhone" maxlength="14" data-parsley-pattern="^\(\d{3}\)\s\d{3}-\d{4}$" data-parsley-error-message="Please enter phone number in format (xxx) xxx-xxxx" placeholder="(xxx) xxx-xxxx" autocomplete="tel">

                        </div>
                    </div>
                </div>

                <!-- insideSales (Optional) -->
                <div class="bg-orange-50 p-4 rounded-xl mt-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="bg-orange-600 p-2 rounded-lg mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>Parts Contact
                        <!-- <span class="text-sm font-normal text-gray-500 ml-2">(Optional)</span> -->
                    </h3>

                    <!-- 3 inputs in one row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Contact Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>

                            {!! html()->text('insideSalesName', old('insideSalesName'))
                            ->class('w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-orange-500 focus:border-orange-500')
                            ->attributes(['placeholder' => 'Mike Johnson', 'id' => 'insideSalesName']) !!}
                        </div>

                        <!-- Contact Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>

                            {!! html()->email('insideSalesEmail', old('insideSalesEmail'))->class([
                            'w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('email'),
                            'border-gray-300' => !$errors->has('email'),
                            ])->attributes([
                            'placeholder' => 'mike@company.com',
                            'id' => 'insideSalesEmail',
                            'autocomplete' => 'off',
                            'name' => 'insideSalesEmail',
                            'data-parsley-type' => 'email',
                            'data-parsley-trigger' => 'change',
                            ]) !!}
                        </div>

                        <!-- Contact Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>

                            {!! html()->text('insideSalesPhone', old('insideSalesPhone'))->class([
                            'masked-phone w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('insideSalesPhone'),
                            'border-gray-300' => !$errors->has('insideSalesPhone'),
                            ])->attributes([
                            'maxlength' => 14,
                            'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                            'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                            'placeholder' => '(xxx) xxx-xxxx',
                            'id' => 'insideSalesPhone',
                            'autocomplete' => 'tel',
                            ]) !!}

                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 p-4 rounded-xl mt-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="bg-purple-600 p-2 rounded-lg mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>Billing Contact
                        <!-- <span class="text-sm font-normal text-gray-500 ml-2">(Optional)</span> -->
                    </h3>

                    <!-- 3 inputs in one row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Contact Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>

                            <input class="w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" type="text" name="BillingName" id="BillingName" placeholder="Sarah Wilson">
                        </div>

                        <!-- Contact Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>

                            <input class="w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500 border-gray-300" type="email" name="BillingEmail" id="BillingEmail" placeholder="sarah@company.com" autocomplete="off" data-parsley-type="email" data-parsley-trigger="change">
                        </div>

                        <!-- Contact Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>

                            <input class="masked-phone w-full px-3 py-3 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500 border-gray-300" type="text" name="BillingPhone" id="BillingPhone" maxlength="14" data-parsley-pattern="^\(\d{3}\)\s\d{3}-\d{4}$" data-parsley-error-message="Please enter phone number in format (xxx) xxx-xxxx" placeholder="(xxx) xxx-xxxx" autocomplete="tel">

                        </div>
                    </div>
                </div>

                {{ html()->form()->close() }}
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 px-6 py-3 border-t bg-gray-50">
                <button type="button" onclick="closeModal('AddSupplier')"
                    class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white hover:bg-gray-100">
                    Cancel
                </button>
                <button type="submit" id="saveSupplierBtn"
                    class="px-6 py-3 text-md rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Add Supplier
                </button>
            </div>
        </div>
    </div>
</div>



<!-- tag module   -->
<div id="TagModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-sm flex flex-col max-h-full overflow-hidden border border-gray-200">

            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <h2 class="text-lg font-semibold text-gray-900">Add Tag</h2>
                </div>
                <button type="button" onclick="closeTagModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <!-- Scrollable Content -->
            <div class="px-6 overflow-y-auto max-h-[70vh] mt-5 mb-5">
                <div class="mb-4">
                    <label for="newTagInput2" class="block text-sm font-medium text-gray-700 mb-1 required"> New Tag</label>

                    <input class="w-full rounded-md border focus:outline-none px-3 py-3 text-sm shadow-sm border-gray-300 " type="text" name="newTagInput2" id="newTagInput2">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4 pb-4 px-4 border-t border-gray-200">
                <button type="button" onclick="closeTagModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white">Close</button>
                <button type="button" id="addTagBtn2" class="flex items-center justify-center gap-2 px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-all"> <svg id="addTagSpinner" xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 hidden animate-spin"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="addTagText">Add</span> </button>
            </div>
        </div>
    </div>
</div>



<!-- Category module   -->
<div id="CategoryModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-sm flex flex-col max-h-full overflow-hidden border border-gray-200">

            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <h2 class="text-lg font-semibold text-gray-900">Add Category</h2>
                </div>
                <button type="button" onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <!-- Scrollable Content -->
            <div class="px-6 overflow-y-auto max-h-[70vh] mt-5 mb-5">
                <div class="mb-4">
                    <label for="newCategoryInput2" class="block text-sm font-medium text-gray-700 mb-1 required"> New Category</label>

                    <input class="w-full rounded-md border focus:outline-none px-3 py-3 text-sm shadow-sm border-gray-300 " type="text" name="newCategoryInput2" id="newCategoryInput2">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4 pb-4 px-4 border-t border-gray-200">
                <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white">Close</button>
                <button type="button" id="addCategoryBtn2" class="flex items-center justify-center gap-2 px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-all"> <svg id="addCategorySpinner2" xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 hidden animate-spin"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="addCategoryText2">Add</span> </button>
            </div>
        </div>
    </div>
</div>


@push('js')
<!-- Parsley to validate -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById('supplierForm');
        const saveBtn = document.getElementById('saveSupplierBtn');

        saveBtn.addEventListener('click', function(e) {
            e.preventDefault(); // prevent default submit

            // Use Parsley to validate the form
            if ($(form).parsley().validate()) {
                // Form is valid, submit it
                form.submit();
            } else {
                // Form is invalid, Parsley will show errors automatically
                console.log("Form validation failed");
            }
        });
    });
</script>
<!-- Parsley to validate -->

<script>
    // === Open Add Modal (Default State) ===
    window.openAddSupplierModal = function() {
        resetSupplierForm(); // Always reset before showing
        setSupplierFormAction('add');

        document.getElementById('form-model-tital').textContent = 'Add New Supplier';
        document.getElementById('saveSupplierBtn').textContent = 'Add Supplier';
        // clearFile();
        openModal('AddSupplier');
    };

    // === Edit Supplier ===
    window.editSupplier = async function(id) {
        try {
            let viewUrl = `{{ route('admin.maintenance-management.suppliers.edit', ['id' => ':id']) }}`;
            viewUrl = viewUrl.replace(':id', id);

            const response = await fetch(viewUrl);
            if (!response.ok) throw new Error('Network response was not ok');

            const result = await response.json();
            // console.log('Supplier fetched:', result);

            const s = result.data;

            setSupplierFormAction('edit', s.id);
            // Set modal to EDIT mode
            document.getElementById('form-model-tital').textContent = 'Edit Supplier';
            document.getElementById('saveSupplierBtn').textContent = 'Update Supplier';

            // === Fill form fields ===
            setFieldValue('supplierCompany', s.name);
            setFieldValue('email', s.email);
            setFieldValue('supplierPhone', s.phone);
            setFieldValue('supplierWebsite', s.website);
            setFieldValue('supplierAddress', s.address);
            setFieldValue('supplierCity', s.city);
            setFieldValue('supplierZip', s.zip_code);
            setFieldValue('supplierTax', s.tax_id);
            setFieldValue('primaryContactName', s.primary_contact_name);
            setFieldValue('primaryContactEmail', s.primary_contact_email);
            setFieldValue('primaryContactPhone', s.primary_contact_phone);
            setFieldValue('insideSalesName', s.inside_sales_name);
            setFieldValue('insideSalesEmail', s.inside_sales_email);
            setFieldValue('insideSalesPhone', s.inside_sales_phone);
            setFieldValue('technicalSupportName', s.technical_support_name);
            setFieldValue('technicalSupportEmail', s.technical_support_email);
            setFieldValue('technicalSupportPhone', s.technical_support_phone);


            setFieldValue('BillingName', s.billing_contact_name);
            setFieldValue('BillingEmail', s.billing_contact_email);
            setFieldValue('BillingPhone', s.billing_contact_phone);


            // === Set SELECT dropdown values ===
            setSelectValue('supplierStatus', s.status);
            setSelectValue('supplierCountry', s.country);

            setSelectValue('supplierCategorysform', s.supplier_category_id);
            setSelectValue('supplierState', s.state_id);
            setSelectValue('supplierPaymentTerms', s.payment_terms);


            // === Preferred Supplier checkbox ===
            const preferredCheckbox = document.getElementById('isPreferredSupplier');
            if (preferredCheckbox) {
                preferredCheckbox.checked = Boolean(s.is_preferred_supplier);
            }

            // Inside your editSupplier function
            setSupplierTags(s.tag_objects);
       
            openModal('AddSupplier');

        } catch (error) {
            console.error('Fetch error:', error);
            notyf.error("Failed to fetch supplier details.");
        } finally {
            // === Reset form when modal closes ===
            const modal = document.getElementById('AddSupplier');
            const observer = new MutationObserver(() => {
                if (modal.classList.contains('hidden')) {
                    resetSupplierForm();
                    document.getElementById('form-model-tital').textContent = 'Add New Supplier';
                    document.getElementById('saveSupplierBtn').textContent = 'Add Supplier';
                    observer.disconnect();
                }
            });
            observer.observe(modal, {
                attributes: true,
                attributeFilter: ['class']
            });
        }
    };

    // set Existing Company Logo

    // function setExistingCompanyLogo(media) {
    //     const fileNameDisplay = document.getElementById("fileNameDisplay");
    //     const fileActions = document.getElementById("fileActions");
    //     const viewLink = document.getElementById("viewFileLink");
    //     const uploadUI = document.getElementById("uploadUI");
    //     const fileInput = document.getElementById("fileInput");

    //     if (!media || !media.url) {
    //         // No existing file
    //         clearFile();
    //         return;
    //     }

    //     // Display the existing file
    //     fileNameDisplay.textContent = media.original_file_name || "Company Logo";
    //     viewLink.href = media.url;
    //     fileActions.style.display = "flex";
    //     uploadUI.style.display = "none";

    //     // Optional: if you want to track the existing media id for backend
    //     fileInput.dataset.existingMediaId = media.id;
    // }

    function setSupplierFormAction(mode, supplierId = null) {
        const form = document.getElementById('supplierForm');
        if (!form) return;

        if (mode === 'add') {
            form.action = "{{ route('admin.maintenance-management.suppliers.store') }}";
            form.method = "POST";
        } else if (mode === 'edit' && supplierId) {
            let editUrl = `{{ route('admin.maintenance-management.suppliers.update', ['supplier' => ':id']) }}`;
            editUrl = editUrl.replace(':id', supplierId);
            form.action = editUrl;
            form.method = "POST"; // You may need to add a hidden `_method` field for PATCH
            // Ensure PATCH method for Laravel
            let methodInput = form.querySelector('input[name="_method"]');
            if (!methodInput) {
                methodInput = document.createElement('input');
                methodInput.type = "hidden";
                methodInput.name = "_method";
                form.appendChild(methodInput);
            }
            // methodInput.value = "PATCH";
        }
    }


    // === Helper: Reset Supplier Form ===
    function resetSupplierForm() {
        const form = document.getElementById('supplierForm');
        if (form) form.reset();

        // Manually clear text inputs if necessary
        const fields = [
            'supplierCompany', 'email', 'supplierPhone', 'supplierWebsite',
            'supplierAddress', 'supplierCity', 'supplierZip', 'supplierTax',
            'primaryContactName', 'primaryContactEmail', 'primaryContactPhone',
            'insideSalesName', 'insideSalesEmail', 'insideSalesPhone', 'technicalSupportName', 'BillingName','BillingEmail','BillingPhone','technicalSupportEmail', 'technicalSupportPhone'
        ];
        fields.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        // Reset selects
        ['supplierPaymentTerms', 'supplierCategorysform'].forEach(id => {
            const select = document.getElementById(id);
            if (select) select.value = '';
        });

    }

    // === Helper: Set Select Value (for single or multiple selects) ===
    function setSelectValue(selectId, value) {
        const select = document.getElementById(selectId);
        if (!select) return;

        if (Array.isArray(value)) {
            // Handle multi-selects (e.g., tags)
            for (let option of select.options) {
                option.selected = value.includes(option.value) || value.includes(Number(option.value));
            }
        } else {
            // Handle single selects
            select.value = value ?? '';
        }

    }

    function setFieldValue(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.value = value ?? '';
        } else {
            console.warn(`⚠️ Field not found: ${id}`);
        }
    }

    // Select tags in edit mode
    function setSupplierTags(tagObjects) {
        if (!window.tagChoices || !Array.isArray(tagObjects)) return;

        // Convert IDs to string
        const idsToSelect = tagObjects.map(t => String(t.id));

        // Select items safely
        idsToSelect.forEach(id => {
            window.tagChoices.setChoiceByValue(id);
        });
    }
</script>

<!-- -------- add tag --------  -->


<script>
    function openTagModal() {
        document.getElementById('TagModal').classList.remove('hidden');

    }

    function closeTagModal() {
        document.getElementById('TagModal').classList.add('hidden');
    }
</script>


<script>
    function openCategoryModal() {
        document.getElementById('CategoryModal').classList.remove('hidden');

    }

    function closeCategoryModal() {
        document.getElementById('CategoryModal').classList.add('hidden');
    }
</script>


<!-- this is for appear tags her form choice js  -->

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const addTagBtn = document.getElementById('addTagBtn2');
        const addTagText = document.getElementById('addTagText');
        const addTagSpinner = document.getElementById('addTagSpinner');
        const newTagInput = document.getElementById('newTagInput2');


        // Add new tag
        addTagBtn.addEventListener('click', () => {
            const name = newTagInput.value.trim();
            if (!name) {
                notyf.error("Tag cannot be empty!");
                return;
            }

            //  Start loading animation
            addTagBtn.disabled = true;
            addTagSpinner.classList.remove('hidden');
            addTagText.textContent = 'Saving...';

            fetch(`{{ route('admin.maintenance-management.suppliers.tag.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        name
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // console.log("✅ Tag created:", data.tag);

                        notyf.success(data.message || 'Tag added!');
                        newTagInput.value = '';

                        // console.log("🔄 Refreshing tags...");

                        fetchTags().then(() => {
                            // console.log("✅ Tags refreshed");

                            if (data.tag?.id && window.tagChoices) {
                                // console.log("🎯 Auto-selecting tag ID:", data.tag.id);
                                window.tagChoices.setChoiceByValue(String(data.tag.id));
                            } else {
                                // console.warn("⚠️ tagChoices or tag.id missing");
                            }
                        });

                    } else {
                        // console.error("❌ Tag creation failed:", data);
                        notyf.error(data.message || 'Failed to add tag');
                    }

                })
                .catch(() => notyf.error("Failed to add tag"))
                .finally(() => {
                    //  Stop loading animation
                    addTagBtn.disabled = false;
                    addTagSpinner.classList.add('hidden');
                    addTagText.textContent = 'Add';

                    closeTagModal()
                });
        });

    });
</script>


<!-- notes  -->


<script>
document.addEventListener('DOMContentLoaded', () => {

    const addCategoryBtn2 = document.getElementById('addCategoryBtn2');
    const addCategoryText2 = document.getElementById('addCategoryText2');
    const addCategorySpinner2 = document.getElementById('addCategorySpinner2');
    const newCategoryInput2 = document.getElementById('newCategoryInput2');
              // this is rerender the suppier form
            const select = document.getElementById('supplierCategorysform');

    function fetchCategories() {
    fetch(`{{ route('admin.maintenance-management.parts.category.fetch') }}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {

                // Save categories globally
                window.categories = data.categories;

                // Clear dropdown
                select.innerHTML = '<option value="">Select Category</option>';

                // Populate dropdown
                window.categories.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.id;
                    opt.textContent = cat.name;
                    select.appendChild(opt);
                });
            }
        })
        .catch(() => notyf.error("Failed to load categories"));
}


    addCategoryBtn2.addEventListener('click', () => {
        const name = newCategoryInput2.value.trim();
        if (!name) {
            notyf.error("Category cannot be empty!");
            return;
        }

        addCategoryBtn2.disabled = true;
        addCategorySpinner2.classList.remove('hidden');
        addCategoryText2.textContent = 'Saving...';

        fetch(`{{ route('admin.maintenance-management.parts.category.store') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ name })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                notyf.success(data.message || 'Category added!');
                newCategoryInput2.value = '';
                fetchCategories(); // refresh select dropdown
            } else {
                notyf.error(data.message || 'Failed to add category');
            }
        })
        .catch(() => notyf.error("Failed to add category"))
        .finally(() => {
            addCategoryBtn2.disabled = false;
            addCategorySpinner2.classList.add('hidden');
            addCategoryText2.textContent = 'Add';
            closeCategoryModal();
        });
    });
    fetchCategories();

});
</script>


@endpush
