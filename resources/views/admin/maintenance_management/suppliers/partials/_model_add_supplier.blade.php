<!-- Supplier Modal -->
<div id="AddSupplier" class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div
            class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-5xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full"
            onclick="event.stopPropagation()">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-start gap-3">

                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Add New Supplier</h3>
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

                <div class="bg-blue-50 p-4">

                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="bg-blue-600 p-1 rounded-lg mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
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
                            'w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
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
                            'w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('email'),
                            'border-gray-300' => !$errors->has('email'),
                            ])->attributes([
                            'placeholder' => 'Enter Email',
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
                            'masked-phone w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
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
                            'w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('supplierWebsite'),
                            'border-gray-300' => !$errors->has('supplierWebsite'),
                            ])
                            ->attributes([
                            'placeholder' => 'https://www.example.com',
                            'id' => 'supplierWebsite',
                            'autocomplete' => 'off',
                            ]) !!}

                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>

                            {!! html()->text('supplierAddress', old('supplierAddress'))
                            ->class('w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->attributes(['placeholder' => 'Enter address', 'id' => 'supplierAddress']) !!}
                        </div>

                        <!-- City -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>

                            {!! html()->text('supplierCity', old('supplierCity'))
                            ->class('w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->attributes(['placeholder' => 'Enter city', 'id' => 'supplierCity']) !!}
                        </div>

                        <!-- State -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>

                            {!! html()
                            ->select('supplierState',
                            $states->mapWithKeys(fn ($state) => [$state->id => $state->name])->toArray(),
                            old('supplierState')
                            )
                            ->id('supplierState')
                            ->class([
                            'w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('supplierState'),
                            ])->required()
                            !!}

                        </div>

                        <!-- Zip -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>

                            {!! html()->text('supplierZip', old('supplierZip'))
                            ->class([
                            'w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('supplierZip'),
                            'border-gray-300' => !$errors->has('supplierZip'),
                            ])
                            ->attributes([
                            'placeholder' => 'Enter ZIP code',
                            'id' => 'supplierZip',
                            'autocomplete' => 'off',
                            ]) !!}

                        </div>

                        <!-- Country -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>

                            {!! html()->select('supplierCountry', [
                            '' => 'Select country',
                            'USA' => 'USA',
                            'Canada' => 'Canada',
                            'UK' => 'UK',
                            'India' => 'India',
                            ], old('supplierCountry'))
                            ->class('w-full px-3 py-2 border bg-white rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->id('supplierCountry') !!}

                        </div>

                        <!-- Tax ID -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tax ID</label>

                            {!! html()->text('supplierTax', old('supplierTax'))
                            ->class('w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->attributes(['placeholder' => 'Enter tax ID', 'id' => 'supplierTax']) !!}


                        </div>

                        <!-- Supplier Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier Category</label>
                            <select id="supplierCategorysform" name="supplierCategory" class="w-full px-3 py-2  bg-white  border rounded-md text-sm focus:ring-green-500 focus:border-green-500" required>
                                <option value="">Select category</option>
                                <!-- dynamically insert from categories list -->
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>


                            {!! html()->select('supplierStatus', [
                            '' => 'Select Status',
                            'Active' => 'Active',
                            'Inactive' => 'Inactive',
                            'Pending' => 'Pending',
                            ], old('supplierStatus'))
                            ->class('w-full px-3 py-2 border bg-white rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->id('supplierStatus')->required() !!}

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
                            ->class('w-full px-3 py-2 border bg-white rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->id('supplierPaymentTerms')->required() !!}

                        </div>

                        <!-- Tags -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                            <select id="supplierTags" name="tags[]" multiple

                                class="choices-select w-full rounded-md border border-gray-300 text-sm">
                            </select>
                        </div>


                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Company Logo</label>

                            <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5     border border-gray-200 overflow-hidden flex flex-col max-h-full">

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
                                                    <input type="file" id="fileInput" name="upload_company_logo" class="hidden" accept=".pdf,.png,.jpg,.jpeg" onchange="handleFileChange(event)" />
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

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Primary Contact -->
                <div class="bg-green-50 p-4 rounded-md mt-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="bg-green-600 p-1 rounded-lg mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
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
                            ->class('w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500')
                            ->attributes(['placeholder' => 'Full name', 'id' => 'primaryContactName']) !!}

                        </div>

                        <!-- Contact Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>

                            {!! html()->email('primaryContactEmail', old('primaryContactEmail'))->class([
                            'w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('email'),
                            'border-gray-300' => !$errors->has('email'),
                            ])->attributes([
                            'placeholder' => 'Enter Email',
                            'id' => 'email',
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
                            'masked-phone w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
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

                <!-- Secondary Contact (Optional) -->
                <div class="bg-orange-50 p-4 rounded-md mt-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <div class="bg-orange-600 p-1 rounded-lg mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>Secondary Contact
                        <span class="text-sm font-normal text-gray-500 ml-2">(Optional)</span>
                    </h3>

                    <!-- 3 inputs in one row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Contact Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                            <!-- <input type="text" id="secondaryContactName" name="secondaryContactName" class="w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Full name"> -->
                            {!! html()->text('secondaryContactName', old('secondaryContactName'))
                            ->class('w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-orange-500 focus:border-orange-500')
                            ->attributes(['placeholder' => 'Full name', 'id' => 'secondaryContactName']) !!}
                        </div>

                        <!-- Contact Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>

                            {!! html()->email('secondaryContactEmail', old('secondaryContactEmail'))->class([
                            'w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('email'),
                            'border-gray-300' => !$errors->has('email'),
                            ])->attributes([
                            'placeholder' => 'Enter Email',
                            'id' => 'email',
                            'autocomplete' => 'off',
                            'name' => 'secondaryContactEmail',
                            'data-parsley-type' => 'email',
                            'data-parsley-trigger' => 'change',
                            ]) !!}
                        </div>

                        <!-- Contact Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>

                            {!! html()->text('secondaryContactPhone', old('secondaryContactPhone'))->class([
                            'masked-phone w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500',
                            'border-red-500' => $errors->has('secondaryContactPhone'),
                            'border-gray-300' => !$errors->has('secondaryContactPhone'),
                            ])->attributes([
                            'maxlength' => 14,
                            'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                            'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                            'placeholder' => '(xxx) xxx-xxxx',
                            'id' => 'secondaryContactPhone',
                            'autocomplete' => 'tel',
                            ]) !!}

                        </div>
                    </div>
                </div>

                {{ html()->form()->close() }}
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 px-6 py-3 border-t bg-gray-50">
                <button type="button" onclick="closeModal('AddSupplier')"
                    class="px-4 py-2 text-sm rounded border border-gray-300 bg-white hover:bg-gray-100">
                    Cancel
                </button>
                <button type="submit" id="saveSupplierBtn"
                    class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">
                    Add Supplier
                </button>
            </div>
        </div>
    </div>
</div>


@push('js')
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


<script>
    function handleFileChange(event) {
        const file = event.target.files[0];
        const fileNameDisplay = document.getElementById("fileNameDisplay");
        const fileActions = document.getElementById("fileActions");
        const viewLink = document.getElementById("viewFileLink");
        const uploadUI = document.getElementById("uploadUI");

        if (file) {
            // Allowed file extensions
            const allowedExtensions = ["jpg", "jpeg", "png"];
            const fileExtension = file.name.split(".").pop().toLowerCase();

            // Max file size (10 MB)
            const maxSize = 10 * 1024 * 1024; // 10 MB in bytes

            // Validation: format
            if (!allowedExtensions.includes(fileExtension)) {

                notyf.error("Invalid file format. Allowed: JPG, JPEG, PNG.");

                event.target.value = ""; // Reset file input
                return;
            }

            // Validation: size
            if (file.size > maxSize) {
                notyf.error("File is too large. Maximum size allowed is 10 MB.");

                event.target.value = ""; // Reset file input
                return;
            }

            //  Passed validation → show UI
            fileNameDisplay.textContent = file.name;
            fileActions.style.display = "flex";
            uploadUI.style.display = "none";

            // Temporary blob link for preview
            const objectUrl = URL.createObjectURL(file);
            viewLink.href = objectUrl;
        }
    }

    function clearFile() {
        const fileInput = document.getElementById("fileInput");
        const fileActions = document.getElementById("fileActions");
        const uploadUI = document.getElementById("uploadUI");
        const fileNameDisplay = document.getElementById("fileNameDisplay");

        fileInput.value = "";
        fileActions.style.display = "none";
        uploadUI.style.display = "flex";
        fileNameDisplay.textContent = "";
    }
</script>


@endpush
