<!-- View Supplier Modal -->
<div id="ViewSupplier"
    class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div
            class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-6xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full"
            onclick="event.stopPropagation()">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-start gap-3">
                    <div class="p-2 border rounded-md bg-blue-100 text-purple-600">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">TechFlow Solutions</h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 flex items-center gap-1">
                                <svg class="w-4 h-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Active
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
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
                        <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2 mb-5">
                            <div class="p-2 border rounded-md bg-purple-600 text-white">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            Company Information
                        </h1>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 required">Company Name</label>
                                <div class="text-sm text-gray-900" style="display: block;">john Week</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <div>
                                    <p class="text-gray-900 flex items-center gap-1 text-sm">
                                        <svg class="w-4 h-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                        </svg>johneweek@test.com
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <div>
                                    <p class="text-gray-900 flex items-center gap-1 text-gray-900">
                                        <svg class="w-4 h-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                        </svg> (256) 544-5654
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                <div>
                                    <a href="http://asdasdasd.net" class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"></path>
                                        </svg> http://asdasdasd.net
                                    </a>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <div class="static-view text-gray-900 text-sm" style="display: block;">
                                    john week<br>

                                    aaaaaaa, los angale<br>

                                    Hawaii 336652<br>

                                    (256) 544-5654
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tax ID</label>
                                <div>
                                    <p class="text-gray-900 flex items-center gap-1 text-sm">
                                        ADT2541#@
                                    </p>
                                </div>
                            </div>


                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                                <div>
                                    <p class="text-gray-900 flex items-center gap-1 text-sm">
                                        Net 30
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                                <div class="flex flex-wrap gap-1">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                        #Design
                                    </span>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                        #Client
                                    </span>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
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
                            <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2 mb-4">


                                <div class="p-2 border rounded-md bg-green-600 text-white">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                </div>

                                Primary Contact
                            </h1>

                            <div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                    <div class="text-sm text-gray-900">john Week</div>

                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <div>
                                        <p class="text-gray-900 flex items-center gap-1 text-sm">
                                            <svg class="w-4 h-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                            </svg>johneweek@test.com
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <div>
                                        <p class="text-gray-900 flex items-center gap-1 text-gray-900">
                                            <svg class="w-4 h-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                            </svg> (256) 544-5654
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Contact -->
                        <div class="bg-orange-50 p-4 rounded-md border border-orange-100 ">
                            <h1 class="text-xl font-bold text-orange-800 flex items-center gap-2 mb-4">


                                <div class="p-2 border rounded-md bg-orange-600 text-white">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"></path>
                                    </svg>
                                </div>
                                Secondary Contact (Optional)
                            </h1>

                            <div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                    <div class="text-sm text-gray-900">john Week</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <div>
                                        <p class="text-gray-900 flex items-center gap-1 text-sm">
                                            <svg class="w-4 h-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"></path>
                                            </svg>johneweek@test.com
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <div>
                                        <p class="text-gray-900 flex items-center gap-1 text-gray-900">
                                            <svg class="w-4 h-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                                            </svg> (256) 544-5654
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-5">

                    <!-- Left Side: Company Info -->
                    <div class="bg-blue-50 p-4 rounded-md border border-blue-100">
                        <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2 mb-5">


                            <div class="p-2 border rounded-md bg-blue-600 text-white">
                                <svg class="h-5 w-5 " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"></path>
                                </svg>
                            </div>
                            Business Details
                        </h1>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 required">Join Date</label>
                                <div class="text-sm text-gray-900" style="display: block;">May 15 , 2023</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 required">Last Order</label>
                                <div class="text-sm text-gray-900" style="display: block;">June 15 , 2023</div>
                            </div>

                        </div>

                    </div>

                    <!-- Right Side: Contacts -->
                    <div class="flex flex-col gap-6">
                        <!-- Primary Contact -->
                        <div class="bg-gray-50 p-4 rounded-md border border-gray-100 ">
                            <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2 mb-4">


                                <div class="p-2 border rounded-md bg-gray-600 text-white">
                                    <svg class="h-5 w-5 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                Parts Supplied (4)
                            </h1>

                            <div>
                                <ul class="space-y-2 max-h-[150px] overflow-y-auto pr-2">
                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border border-blue-200 bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="font-medium">Equipment Dealer</span></span></div>

                                    </li>
                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border border-blue-200 bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="font-medium">Equipment Dealer</span></span></div>

                                    </li>
                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border border-blue-200 bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="font-medium">Equipment Dealer</span></span></div>

                                    </li>

                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border border-blue-200 bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="font-medium">Equipment Dealer</span></span></div>

                                    </li>
                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border border-blue-200 bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="font-medium">Equipment Dealer</span></span></div>

                                    </li>
                                    <li class="flex items-center justify-between px-4 py-2 rounded-md border border-blue-200 bg-white text-gray-700">
                                        <div class="flex gap-2 items-center"><span class="font-medium">Equipment Dealer</span></span></div>

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
