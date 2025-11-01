<!-- View Supplier Modal -->
<div id="ViewSupplier"
    class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div
            class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-6xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full"
            onclick="event.stopPropagation()">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center gap-3">
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
                        <h3 class="text-3xl font-bold text-gray-900" id="viewSupplierName">-</h3>
                        <div class="gap-2 flex items-center mt-2">
                            <svg id="viewSuppliersvgicon"
                                xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="w-5 h-5 text-green-600">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>

                            <span id="viewSupplierStatus" class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 flex items-center gap-1">
                                Active
                            </span>
                            <span id="viewSupplierCategory" class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                Software / IT
                            </span>
                        </div>
                    </div>
                </div>

                <button onclick="closeModal('ViewSupplier')"
                    class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 overflow-y-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Left Side: Company Info -->
                    <div class="bg-blue-50 p-4 rounded-md border border-blue-100">
                        <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-5">
                            <div class="bg-blue-600 p-2 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white">
                                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                                    <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                                    <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                                    <path d="M10 6h4"></path>
                                    <path d="M10 10h4"></path>
                                    <path d="M10 14h4"></path>
                                    <path d="M10 18h4"></path>
                                </svg>
                            </div>
                            Company Information
                        </h1>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 required">Company Name</label>
                                <div class="text-sm text-gray-900" id="viewSupplierCompanyName">john Week</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <div>
                                    <p class="flex items-center gap-1 text-sm text-blue-600" id="viewSupplierEmail">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                        </svg>johneweek@test.com
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <div>
                                    <p class="text-blue-600 text-sm flex items-center gap-1" id="viewSupplierPhone">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                        </svg> (256) 544-5654
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                <div>
                                    <a id="viewSupplierWebsite" href="http://asdasdasd.net" class="text-blue-600 text-sm flex items-center gap-1">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"></path>
                                        </svg> http://asdasdasd.net
                                    </a>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <div id="viewSupplierAddress" class="static-view text-gray-900 text-sm" style="display: block;">
                                    john week<br>

                                    aaaaaaa, los angale<br>

                                    Hawaii 336652<br>

                                    (256) 544-5654
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tax ID</label>
                                <div>
                                    <p class="text-gray-900 flex items-center gap-1 text-sm" id="viewSupplierTaxId">
                                        ADT2541#@
                                    </p>
                                </div>
                            </div>


                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-gray-400 mr-2">
                                        <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                        <line x1="2" x2="22" y1="10" y2="10"></line>
                                    </svg>
                                    <span id="viewSupplierPaymentTerms" class="text-gray-900 text-sm ">Net 30</span>
                                </div>
                                <!-- <div>
                                    <p id="viewSupplierPaymentTerms" class="text-gray-900 flex items-center gap-1 text-sm">
                                        Net 30
                                    </p>
                                </div> -->
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                                <div class="flex flex-wrap gap-1" id="viewSupplierTags">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 mr-1">
                                            <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                                            <path d="M7 7h.01"></path>
                                        </svg>
                                        #Design
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 mr-1">
                                            <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                                            <path d="M7 7h.01"></path>
                                        </svg>
                                        #Client
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 mr-1">
                                            <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                                            <path d="M7 7h.01"></path>
                                        </svg>
                                        #Maintenance
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Side: Contacts -->
                    <div class="flex flex-col gap-6">
                        <!-- Primary Contact -->
                        <div class="bg-green-50 p-4 rounded-md border border-green-100 ">
                            <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-4">
                                <div class="p-2 border rounded-md bg-green-600 text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white">
                                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>

                                Primary Contact
                            </h1>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                    <div class="text-sm text-gray-900" id="PrimaryContactName">john Week</div>

                                </div>
                                <div class="">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <div>
                                        <p class="text-blue-600 flex items-center gap-1 text-sm" id="PrimaryContactEmail">
                                            <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                            </svg>johneweek@test.com
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <div>
                                        <p class="text-blue-600 text-sm flex items-center gap-1" id="PrimaryContactPhone">
                                            <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                            </svg> (256) 544-5654
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Contact -->
                        <div class="bg-orange-50 p-4 rounded-md border border-orange-100 ">
                            <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-4">
                                <div class="bg-orange-600 p-2 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                Inside Sales
                            </h1>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                    <div class="text-sm text-gray-900" id="SecondaryContactName">john Week</div>
                                </div>
                                <div class="">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <div>
                                        <p class="text-blue-600 flex items-center gap-1 text-sm" id="SecondaryContactEmail">
                                            <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                            </svg>johneweek@test.com
                                        </p>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <div>
                                        <p class="text-blue-600 flex items-center gap-1 text-sm" id="SecondaryContactPhone">
                                            <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                            </svg> (256) 544-5654
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Contact -->
                        <div class="bg-blue-50 p-4 rounded-md border border-orange-100 ">
                            <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-4">
                                <div class="bg-blue-600 p-2 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                Technical Support
                            </h1>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                    <div class="text-sm text-gray-900" id="technicalSupportName"></div>
                                </div>
                                <div class="">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <div>
                                        <p class="text-blue-600 flex items-center gap-1 text-sm" id="technicalSupportEmail">
                                            <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                            </svg>
                                        </p>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <div>
                                        <p class="text-blue-600 flex items-center gap-1 text-sm" id="technicalSupportPhone">
                                            <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                            </svg>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>


                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-5">

                    <!-- Left Side: Company Info -->
                    <div class="bg-purple-50 p-4 rounded-md">
                        <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-5">
                            <div class="bg-purple-600 p-2 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                    <line x1="16" x2="16" y1="2" y2="6"></line>
                                    <line x1="8" x2="8" y1="2" y2="6"></line>
                                    <line x1="3" x2="21" y1="10" y2="10"></line>
                                </svg>
                            </div>
                            Business Details
                        </h1>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Join Date</label>
                                <div class="text-sm text-gray-900" style="display: block;">May 15 , 2023</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Order</label>
                                <div class="text-sm text-gray-900" style="display: block;">June 15 , 2023</div>
                            </div>

                        </div>

                    </div>

                    <!-- Right Side: Contacts -->
                    <div class="flex flex-col gap-6">
                        <!-- Primary Contact -->
                        <div class="bg-gray-50 p-4 rounded-md border border-gray-100 ">
                            <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-4">
                                <div class="bg-gray-600 p-2 rounded-lg mr-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white">
                                        <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                                        <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                                        <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                                        <path d="M10 6h4"></path>
                                        <path d="M10 10h4"></path>
                                        <path d="M10 14h4"></path>
                                        <path d="M10 18h4"></path>
                                    </svg>
                                </div>
                                Parts Supplied (4)
                            </h1>

                            <div>
                                <ul class="space-y-2 max-h-[150px] overflow-y-auto pr-2">
                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="text-gray-900">Equipment Dealer</span></span></div>
                                    </li>
                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="text-gray-900">Equipment Dealer</span></span></div>
                                    </li>
                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="text-gray-900">Equipment Dealer</span></span></div>
                                    </li>

                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="text-gray-900">Equipment Dealer</span></span></div>
                                    </li>
                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="text-gray-900">Equipment Dealer</span></span></div>

                                    </li>
                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="text-gray-900">Equipment Dealer</span></span></div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 px-6 py-3 border-t bg-gray-50">
                <button type="button" onclick="closeModal('ViewSupplier')"
                    class="px-4 py-2 text-sm rounded border border-gray-300 bg-white hover:bg-gray-100">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

@push('js')

<script>
    // === New Function: Fetch and View Supplier ===
    window.viewSupplier = async function(id) {
        try {
            let viewUrl = `{{ route('admin.maintenance-management.suppliers.view', ['id' => ':id']) }}`;
            viewUrl = viewUrl.replace(':id', id);

            const response = await fetch(viewUrl);
            const result = await response.json();


            console.log(result);

            if (result.status) {
                const s = result.data;


                // Fill header info
                document.getElementById('viewSupplierName').textContent = s.name || 'N/A';

                document.getElementById('viewSupplierCompanyName').textContent = s.name || 'N/A';

                document.getElementById('viewSupplierCategory').textContent = s.category?.name || 'N/A';


                // Status badge + icon update
                const statusEl = document.getElementById('viewSupplierStatus');
                const iconEl = document.getElementById('viewSuppliersvgicon');

                if (!statusEl || !iconEl) return; // safety

                if (s.status === 'Active') {
                    //  Active
                    statusEl.textContent = 'Active';
                    statusEl.className = 'px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 flex items-center gap-1';
                    iconEl.outerHTML = `
        <svg id="viewSuppliersvgicon" xmlns="http://www.w3.org/2000/svg"
             width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             class="w-5 h-5 text-green-600">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>`;
                } else if (s.status === 'Pending') {
                    //  Pending
                    statusEl.textContent = 'Pending';
                    statusEl.className = 'px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 flex items-center gap-1';
                    iconEl.outerHTML = `
        <svg id="viewSuppliersvgicon" xmlns="http://www.w3.org/2000/svg"
             width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             class="w-5 h-5 text-yellow-600">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </svg>`;
                } else {
                    //  Inactive or any other
                    statusEl.textContent = s.status || 'Inactive';
                    statusEl.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 flex items-center gap-1';
                    iconEl.outerHTML = `
        <svg id="viewSuppliersvgicon" xmlns="http://www.w3.org/2000/svg"
             width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             class="w-5 h-5 text-red-600">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="m15 9-6 6"></path>
            <path d="m9 9 6 6"></path>
        </svg>`;
                }



                // Contact info
                document.getElementById('viewSupplierEmail').innerHTML = `
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                        </svg> ${s.email || 'N/A'}
            `;

                document.getElementById('viewSupplierPhone').innerHTML = `
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                        </svg> ${s.phone || 'N/A'}
            `;

                const websiteEl = document.getElementById('viewSupplierWebsite');
                websiteEl.href = s.website || '#';
                websiteEl.innerHTML = `
                 <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"></path>
                                        </svg> ${s.website || 'N/A'}
            `;

                // Address
                const addr = `${s.address_line_1 || ''}<br>${s.city || ''}, ${s.state?.name || ''}<br>${s.country || ''} ${s.zip || ''}`;
                document.getElementById('viewSupplierAddress').innerHTML = addr.trim() || 'N/A';

                // Tax ID + Payment Terms
                document.getElementById('viewSupplierTaxId').textContent = s.tax_id || '—';
                document.getElementById('viewSupplierPaymentTerms').textContent = s.payment_terms || '—';

                // Tags

                const tagsContainer = document.getElementById('viewSupplierTags');
                tagsContainer.innerHTML = '';

                // Ensure tags is always an array
                let tags = s.tag_objects;


                if (tags.length > 0) {
                    tags.forEach(tag => {
                        const span = document.createElement('span');
                        span.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700';

                        // Create the SVG icon
                        const icon = `
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="14" height="14" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 class="w-3 h-3 mr-1 text-gray-500">
                <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                <path d="M7 7h.01"></path>
            </svg>
        `;

                        // Add icon + text
                        span.innerHTML = `${icon} #${tag.name}`;
                        tagsContainer.appendChild(span);
                    });
                } else {
                    tagsContainer.innerHTML = '<span class="text-gray-400 text-xs italic">No tags</span>';
                }

                document.getElementById('PrimaryContactName').textContent = s.primary_contact_name || 'N/A';
                document.getElementById('SecondaryContactName').textContent = s.inside_sales_name || 'N/A';
                document.getElementById('technicalSupportName').textContent = s.technical_support_name || 'N/A';



                document.getElementById('PrimaryContactEmail').innerHTML = `
               <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                            </svg> ${s.primary_contact_email || 'N/A'}
            `;

                document.getElementById('SecondaryContactEmail').innerHTML = `
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                            </svg> ${s.inside_sales_email || 'N/A'}
            `;
                document.getElementById('technicalSupportEmail').innerHTML = `
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                            </svg> ${s.technical_support_email || 'N/A'}
            `;

                document.getElementById('PrimaryContactPhone').innerHTML = `
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                            </svg> ${s.primary_contact_phone || 'N/A'}
            `;

                document.getElementById('technicalSupportPhone').innerHTML = `
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                            </svg> ${s.technical_support_phone || 'N/A'}
            `;


                document.getElementById('SecondaryContactPhone').innerHTML = `
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                            </svg> ${s.inside_sales_phone || 'N/A'}
            `;

                openModal('ViewSupplier');
            } else {

                notyf.error("Supplier not found.");
            }

        } catch (error) {
            console.error('Fetch error:', error);
            notyf.error("Failed to fetch supplier details.");

        }
    };
</script>

@endpush