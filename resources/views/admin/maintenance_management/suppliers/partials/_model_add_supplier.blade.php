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

                <form id="supplierForm">
                    <div class="bg-blue-50 p-4">

                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <div class="bg-blue-600 p-1 rounded-lg mr-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>
                            </div>Company Information
                        </h3>

                        <!--  Supplier Form -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 ">
                            <!-- Company Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 required">Company Name</label>
                                <input type="text" id="supplierCompany" class="w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="Enter company name">
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="supplierEmail" class="w-full px-3 py-2  bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="Enter email address">
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="text" id="supplierPhone" class=" masked-phone w-full px-3 py-2 bg-white  border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="(xxx) xxx-xxxx">
                            </div>

                            <!-- Website -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                <input type="url" id="supplierWebsite" class="w-full px-3 py-2  bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="https://example.com">
                            </div>

                            <!-- Address -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <input type="text" id="supplierAddress" class="w-full px-3 py-2  bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="Enter address">
                            </div>

                            <!-- City -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                <input type="text" id="supplierCity" class="w-full px-3 py-2  bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="Enter city">
                            </div>

                            <!-- State -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                <select id="supplierState" class="w-full px-3 py-2  bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="">Select state</option>
                                    <option>California</option>
                                    <option>New York</option>
                                    <option>Texas</option>
                                    <option>Florida</option>
                                </select>
                            </div>

                            <!-- Zip -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>
                                <input type="text" id="supplierZip" class="w-full px-3 py-2  bg-white  border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="Enter ZIP code">
                            </div>

                            <!-- Country -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                <select id="supplierCountry" class="w-full px-3 py-2 border  bg-white  rounded-md text-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="">Select country</option>
                                    <option>United States</option>
                                    <option>Canada</option>
                                    <option>United Kingdom</option>
                                    <option>India</option>
                                </select>
                            </div>

                            <!-- Tax ID -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tax ID</label>
                                <input type="text" id="supplierTax" class="w-full px-3 py-2  bg-white  border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="Enter tax ID">
                            </div>

                            <!-- Supplier Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Supplier Category</label>
                                <select id="supplierCategory" class="w-full px-3 py-2  bg-white  border rounded-md text-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="">Select category</option>
                                    <!-- dynamically insert from categories list -->
                                </select>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="supplierStatus" class="w-full px-3 py-2 border  bg-white  rounded-md text-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="">Select Status</option>
                                    <option>Active</option>
                                    <option>Inactive</option>
                                    <option>Pending</option>
                                </select>
                            </div>

                            <!-- Payment Terms -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                                <select id="supplierPaymentTerms" class="w-full px-3 py-2 border  bg-white  rounded-md text-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="">Select Payment Terms</option>
                                    <option>Net 15</option>
                                    <option>Net 30</option>
                                    <option>Net 45</option>
                                    <option>Net 60</option>
                                    <option>Net 75</option>

                                </select>
                            </div>

                            <!-- Tags -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                                <select id="supplierTags" name="tags[]" multiple
                                    data-placeholder="Select tags"
                                    class="choices-select w-full rounded-md border border-gray-300 text-sm">
                                </select>
                            </div>

                        </div>

                    </div>

                    <!-- Primary Contact -->
                    <div class="bg-green-50 p-4 rounded-md mt-5">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <div class="bg-green-600 p-1 rounded-lg mr-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </div>Primary Contact
                        </h3>

                        <!-- 3 inputs in one row -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Contact Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                <input type="text" id="primaryContactName" class="w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="Full name">
                            </div>

                            <!-- Contact Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                                <input type="email" id="primaryContactEmail" class="w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="Email address">
                            </div>

                            <!-- Contact Phone -->
                            <div>
                                <label class=" block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                                <input type="text" id="primaryContactPhone" class="masked-phone w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-green-500 focus:border-green-500" placeholder="(xxx) xxx-xxxx">
                            </div>
                        </div>
                    </div>

                    <!-- Secondary Contact (Optional) -->
                    <div class="bg-orange-50 p-4 rounded-md mt-5">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <div class="bg-orange-600 p-1 rounded-lg mr-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>Secondary Contact 
                            <span class="text-sm font-normal text-gray-500 ml-2">(Optional)</span>
                        </h3>

                        <!-- 3 inputs in one row -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Contact Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                <input type="text" id="secondaryContactName" class="w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Full name">
                            </div>

                            <!-- Contact Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                                <input type="email" id="secondaryContactEmail" class="w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Email address">
                            </div>

                            <!-- Contact Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                                <input type="text" id="secondaryContactPhone" class="masked-phone w-full px-3 py-2 bg-white border rounded-md text-sm focus:ring-orange-500 focus:border-orange-500" placeholder="(xxx) xxx-xxxx">
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 px-6 py-3 border-t bg-gray-50">
                <button type="button" onclick="closeModal('AddSupplier')"
                    class="px-4 py-2 text-sm rounded border border-gray-300 bg-white hover:bg-gray-100">
                    Cancel
                </button>
                <button type="button" id="saveSupplierBtn"
                    class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">
                    Add Supplier
                </button>
            </div>
        </div>
    </div>
</div>


@push('js')

@endpush