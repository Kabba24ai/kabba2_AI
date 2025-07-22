            <div class="bg-white rounded-md p-6 shadow-sm border border-gray-200 mt-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Left: Title & Description -->
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900">Account Billing Summary</h2>
                        <p class="text-sm text-gray-600">Consolidated view of all customer accounts and payment status</p>
                    </div>

                    <!-- Right: Action Buttons -->
                    <div class="flex items-center gap-3">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded flex items-center gap-2 text-sm font-medium">
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-white" />
                            Export
                        </button>
                        
                        <button class="bg-gray-700 text-white px-4 py-2 rounded flex items-center gap-2 text-sm font-medium">
                             <x-heroicon-o-arrow-path class="w-5 h-5 text-white" />
                            Refresh
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mx-auto  mt-6">
                <!-- Total Outstanding -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm  text-gray-500">Total Outstanding</p>
                        <p class="text-xl font-semibold text-gray-900">$28,751.50</p>
                    </div>
                </div>

                <!-- Overdue Amount -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-red-100 text-red-600 rounded-md p-2">
                       <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500" />
                    </div>
                    <div>
                        <p class="text-sm  text-gray-500">Overdue Amount</p>
                        <p class="text-xl font-semibold text-gray-900">$13,025.50</p>
                    </div>
                </div>

                <!-- Overdue Accounts -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
                       <svg xmlns="http://www.w3.org/2000/svg" 
                            width="20" height="20" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-alert-circle w-6 h-6">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Overdue Accounts</p>
                    <p class="text-xl font-semibold text-gray-900"> 3 </p>
                    </div>
                </div>

                <!-- Last Payment -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 rounded-md p-2">
                        <x-heroicon-o-user class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Customers</p>
                        <p class="text-xl font-semibold text-gray-900">7 </p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 mt-6 w-full">
                <div class="flex flex-wrap items-start gap-4 text-sm">
                    
                    <!-- Customer Name -->
                    <div class="flex flex-col billing-summary-w-16">
                        <label class="text-sm text-gray-500 mb-1">Customer Name</label>
                        <div class="relative">
                            <input type="text" placeholder="Search customers..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-full " />
                            <svg class="absolute w-4 h-4 text-gray-400 left-3 top-1/2 transform -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Company Name -->
                    <div class="flex flex-col billing-summary-w-16" >
                        <label class="text-sm text-gray-500 mb-1">Company Name</label>
                        <div class="relative">
                            <input type="text" placeholder="Search companies..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-full " />
                            <svg class="absolute w-4 h-4 text-gray-400 left-3 top-1/2 transform -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="flex flex-col billing-summary-w-16" >
                        <label class="text-sm text-gray-500 mb-1">Phone</label>
                        <div class="relative">
                            <input type="text" placeholder="(555) 123-4567" class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-full " />
                            <svg class="absolute w-4 h-4 text-gray-400 left-3 top-1/2 transform -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <div class="flex flex-col billing-summary-w-15" >
                        <label class="text-sm text-gray-500 mb-1">Alerts</label>
                        <select class="py-2 px-3 border border-gray-300 rounded-md w-full ">
                            <option>All Accounts</option>
                            <option>Alert Only</option>
                        </select>
                    </div>

                    <!-- Credit Type -->
                    <div class="flex flex-col billing-summary-w-13">
                        <label class="text-sm text-gray-500 mb-1">Credit Type</label>
                        <div class="flex flex-col space-y-1">
                            <label class="inline-flex items-center text-gray-700">
                                <input type="checkbox" class="mr-2" /> Credit - Approved
                            </label>
                            <label class="inline-flex items-center text-gray-700">
                                <input type="checkbox" class="mr-2" /> Credit - None
                            </label>
                            <p class="text-xs text-gray-500">Showing: All / Both</p>
                        </div>
                    </div>

                    <!-- Balance Sort -->
                    <div class="flex flex-col billing-summary-w-16">
                        <label class="text-sm text-gray-500 mb-1">Balance Sort</label>
                        <select class="py-2 px-3 border border-gray-300 rounded-md w-full ">
                            <option>Highest to Lowest</option>
                            <option>Lowest to Highest</option>
                        </select>
                    </div>

                </div>
            </div>




            
            <!-- <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 mt-6 w-full">
                <div class="flex flex-wrap items-start w-full gap-4 text-sm flex-row justify-between">
                    
                    <div class="flex flex-col">
                        <label class="text-sm text-gray-500 mb-2">Customer Name</label>
                        <div class="relative">
                            <input type="text" placeholder="Search customers..." class="w-full md:w-48 pl-9 pr-3 py-2 border border-gray-300 rounded-md" />
                            <div class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="text-sm text-gray-500 mb-2">Company Name</label>
                        <div class="relative">
                            <input type="text" placeholder="Search companies..." class="w-full md:w-48 pl-9 pr-3 py-2 border border-gray-300 rounded-md" />
                            <div class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="text-sm text-gray-500 mb-2">Phone</label>
                        <div class="relative">
                            <input type="text" placeholder="(555) 123-4567" class="w-full md:w-40 pl-9 pr-3 py-2 border border-gray-300 rounded-md" />
                            <div class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012 6.11 2 2 0 014.11 4h3a2 2 0 012 1.72c.13.96.4 1.9.7 2.81a2 2 0 01-.45 2.11L8 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.3 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="text-sm text-gray-500 mb-2">Alerts</label>
                        <select class="w-full md:w-36 py-2 px-3 border border-gray-300 rounded-md">
                            <option>All Accounts</option>
                            <option>Alert Only</option>
                        </select>
                    </div>

                    <div class="flex flex-col w-full md:w-40">
                        <label class="text-sm text-gray-500 mb-2">Credit Type</label>
                        <div class="flex flex-col space-y-1">
                            <label class="inline-flex items-center text-gray-700">
                                <input type="checkbox" class="mr-2" /> Credit - Approved
                            </label>
                            <label class="inline-flex items-center text-gray-700">
                                <input type="checkbox" class="mr-2" /> Credit - None
                            </label>
                            <p class="text-xs text-gray-500">Showing: All / Both</p>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="text-sm text-gray-500 mb-2">Balance Sort</label>
                        <select class="w-full md:w-40 py-2 px-3 border border-gray-300 rounded-md">
                            <option>Highest to Lowest</option>
                            <option>Lowest to Highest</option>
                        </select>
                    </div>
                </div>
            </div> -->

            <!-- <div class="bg-white border border-gray-200 rounded-md p-4 mt-6 w-full">
                <div class="flex flex-wrap gap-4 text-sm w-full">
                    
                    <div class="flex flex-col flex-1 min-w-[200px]">
                        <label class="text-gray-700 font-medium mb-1">Customer Name</label>
                        <div class="relative">
                            <input type="text" placeholder="Search customers..." class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md" />
                            <div class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col flex-1 min-w-[200px]">
                        <label class="text-gray-700 font-medium mb-1">Company Name</label>
                        <div class="relative">
                            <input type="text" placeholder="Search companies..." class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md" />
                            <div class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col flex-1 min-w-[200px]">
                        <label class="text-gray-700 font-medium mb-1">Phone</label>
                        <div class="relative">
                            <input type="text" placeholder="(555) 123-4567" class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md" />
                            <div class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M22 16.92v3a2 2 0 01-2.18 2..."/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col flex-1 min-w-[200px]">
                        <label class="text-gray-700 font-medium mb-1">Alerts</label>
                        <select class="w-full py-2 px-3 border border-gray-300 rounded-md">
                            <option>All Accounts</option>
                            <option>Alert Only</option>
                        </select>
                    </div>

                    <div class="flex flex-col flex-1 min-w-[200px]">
                        <label class="text-gray-700 font-medium mb-1">Credit Type</label>
                        <div class="flex flex-col space-y-1">
                            <label class="inline-flex items-center text-gray-700">
                                <input type="checkbox" class="mr-2" /> Credit - Approved
                            </label>
                            <label class="inline-flex items-center text-gray-700">
                                <input type="checkbox" class="mr-2" /> Credit - None
                            </label>
                            <p class="text-xs text-gray-500">Showing: All / Both</p>
                        </div>
                    </div>

                    <div class="flex flex-col flex-1 min-w-[200px]">
                        <label class="text-gray-700 font-medium mb-1">Balance Sort</label>
                        <select class="w-full py-2 px-3 border border-gray-300 rounded-md">
                            <option>Highest to Lowest</option>
                            <option>Lowest to Highest</option>
                        </select>
                    </div>
                </div>
            </div> -->


            

            <div class="bg-white rounded-md shadow-sm mt-6 mb-0">
                <!-- Header with Filter -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 p-4">
                    <h2 class="text-base font-semibold text-gray-800">Customer Accounts (7)</h2>
                </div>

                <!-- Table -->
                <div class="max-w-full overflow-x-auto">
                    <table id="customerTable" class="min-w-full bg-white border border-gray-200 rounded-md overflow-hidden shadow-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs border-b uppercase">
                            <tr>
                                <th class="px-4 py-3 font-medium text-left cursor-pointer whitespace-nowrap" onclick="sortTable(0, 'string')">Customer Name <span id="icon-0" class="ml-1"></span></th>
                                <th class="px-4 py-3 font-medium text-left cursor-pointer whitespace-nowrap" onclick="sortTable(1, 'string')">Company Name <span id="icon-1" class="ml-1"></span></th>
                                <th class="px-4 py-3 font-medium text-left whitespace-nowrap">Phone</th>
                                <th class="px-4 py-3 font-medium text-left whitespace-nowrap">Phone - Company</th>
                                <th class="px-4 py-3 font-medium text-left cursor-pointer whitespace-nowrap" onclick="sortTable(2, 'number')">Balance <span id="icon-2" class="ml-1"></span></th>
                                <th class="px-4 py-3 font-medium text-left whitespace-nowrap">Last Payment</th>
                                <th class="px-4 py-3 font-medium text-left cursor-pointer whitespace-nowrap" onclick="sortTable(3, 'date')">Last Payment Date <span id="icon-3" class="ml-1"></span></th>
                                <th class="px-4 py-3 font-medium text-left whitespace-nowrap">Payment Due</th>
                                <th class="px-4 py-3 font-medium text-left cursor-pointer whitespace-nowrap" onclick="sortTable(4, 'days')">Days Aging <span id="icon-4" class="ml-1"></span></th>
                                <th class="px-4 py-3 font-medium text-left whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            <tr class="border-t hover:bg-gray-50">
                                <td  class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-4 h-4 text-gray-400 mr-2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <div class="text-sm font-medium text-gray-900">David Thompson</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path>
                                    </svg>
                                    <div class="text-sm text-gray-900">Thompson Enterprises</div></div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-4 h-4 text-gray-400 mr-2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                        <div class="text-sm text-gray-700">(555) 567-8901</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>
                                        <div class="text-sm text-gray-700">(555) 567-0500</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-gray-900">$950.00</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm text-gray-700">$1,500.00</div></td>
                                <td class="px-4 py-4 whitespace-nowrap" data-date="2025-01-25"><div class="text-sm text-gray-700">Jan 25, 2025</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-red-600">-</div></td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                                        <span class="ml-1">5 days</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <button class="text-blue-600  space-x-1">
                                        <x-heroicon-o-eye class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr class="border-t hover:bg-gray-50">
                                <td  class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-4 h-4 text-gray-400 mr-2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <div class="text-sm font-medium text-gray-900">Michael Chen</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path>
                                    </svg>
                                    <div class="text-sm text-gray-700">Global Manufacturing</div></div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-4 h-4 text-gray-400 mr-2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                        <div class="text-sm text-gray-700">(555) 345-6789</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>
                                        <div class="text-sm text-gray-700">(555) 345-0300</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-gray-900">$1,850.25</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm text-gray-700">$2,100.00</div></td>
                                <td class="px-4 py-4 whitespace-nowrap" data-date="2025-01-22"><div class="text-sm text-gray-700">Jan 22, 2025</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-red-600">-</div></td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                                        <span class="ml-1">8 days</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <button class="text-blue-600 space-x-1">
                                        <x-heroicon-o-eye class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr class="border-t hover:bg-gray-50">
                                <td  class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-4 h-4 text-gray-400 mr-2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <div class="text-sm font-medium text-gray-900">John Doe</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path>
                                    </svg>
                                    <div class="text-sm text-gray-700">Acme Corp</div></div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-4 h-4 text-gray-400 mr-2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                        <div class="text-sm text-gray-700">(555) 012-3456</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>
                                        <div class="text-sm text-gray-700">(555) 012-0100</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-gray-900">$2,750.00</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm text-gray-700">$1,250.00</div></td>
                                <td class="px-4 py-4 whitespace-nowrap" data-date="2025-01-18"><div class="text-sm text-gray-700">Jan 18, 2025</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-red-600">$875.50</div></td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                                        <span class="ml-1">15 days</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium  text-center">
                                    <button class="text-blue-600 space-x-1">
                                        <x-heroicon-o-eye class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr class="border-t hover:bg-gray-50">
                                <td  class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-4 h-4 text-gray-400 mr-2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <div class="text-sm font-medium text-gray-900">Sarah Johnson</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path>
                                    </svg>
                                    <div class="text-sm text-gray-700">Tech Solutions Inc</div></div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-4 h-4 text-gray-400 mr-2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                        <div class="text-sm text-gray-700">(555) 234-5678</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>
                                        <div class="text-sm text-gray-700">(555) 234-0200</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-gray-900">$4,250.75</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm text-gray-700">$800.00</div></td>
                                <td class="px-4 py-4 whitespace-nowrap" data-date="2025-01-10"><div class="text-sm text-gray-700">Jan 10, 2025</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-red-600">$1,200.00</div></td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-4 h-4"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
                                        <span class="ml-1"> 45 days</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <button class="text-blue-600 space-x-1">
                                        <x-heroicon-o-eye class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr class="border-t hover:bg-gray-50">
                                <td  class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-4 h-4 text-gray-400 mr-2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <div class="text-sm font-medium text-gray-900">Emily Rodriguez</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path>
                                    </svg>
                                    <div class="text-sm text-gray-700">Creative Designs LLC</div></div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-4 h-4 text-gray-400 mr-2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                        <div class="text-sm text-gray-700">(555) 456-7890</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-4 h-4 text-gray-400 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>
                                        <div class="text-sm text-gray-700">(555) 456-0400</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-gray-900">$6,750.00</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm text-gray-700">$500.00</div></td>
                                <td class="px-4 py-4 whitespace-nowrap" data-date="2024-12-15"><div class="text-sm text-gray-700">Dec 15, 2024</div></td>
                                <td class="px-4 py-4 whitespace-nowrap"><div class="text-sm font-semibold text-red-600">$3,200.00</div></td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-4 h-4"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
                                        <span class="ml-1">75 days</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <button class="text-blue-600 space-x-1">
                                        <x-heroicon-o-eye class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>


                        </tbody>
                    </table>
                </div>
            </div>

