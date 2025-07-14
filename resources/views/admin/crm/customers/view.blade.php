@extends('admin.layouts.app')

@section('title', 'View Customer')

@push('css')
@endpush

@section('content')

<div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Left Section -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <!-- Back to Customers -->
            <a href="{{ route('admin.crm.customers.index') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-1" />
                <span class="text-sm font-medium">Back to Customers</span>
            </a>

            <!-- Divider -->
            <div class="hidden sm:block h-6 border-l border-gray-300"></div>

            <!-- Customer Info -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $customer->full_name }}</h1>
                <p class="text-sm text-gray-500">{{ $customer->unique_id }}</p>
            </div>
        </div>

         <!-- Right Section: Buttons -->
        <div class="flex flex-wrap gap-2">
            <!-- Edit Button -->
            <a href="{{ route('admin.crm.customers.edit', $customer->unique_id) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
                <x-heroicon-o-pencil-square class="w-5 h-5 mr-2" />
                Edit Customer
            </a>

            <!-- Delete Button -->
            <form action="{{ route('admin.crm.customers.delete', $customer->unique_id) }}"
                method="POST" class="inline"
                onsubmit="return confirm('Are you sure you want to delete this Customer?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition">
                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<div class="rounded-xl dark:border-gray-800" x-data="{ activeTab: 'dashboard' }">
    <div class="border-b border-gray-200 dark:border-gray-800">
        <nav
        class="-mb-px flex space-x-2 overflow-x-auto [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 dark:[&::-webkit-scrollbar-thumb]:bg-gray-600 dark:[&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar]:h-1.5"
        >
            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'dashboard' ? ' text-brand-500 border-brand-500  dark:text-brand-400 dark:border-brand-400' : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'dashboard'" id="tab-dashboard" >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4 mr-2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                </svg>
                Dashboard
            </button>

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'orders' ? ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'orders'" id="tab-orders">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-4 h-4 mr-2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
                Orders
            </button>

            <button
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'credit' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'credit'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4 mr-2"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                Credit Account
            </button>

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'invoices' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700  dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'invoices'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4 mr-2"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line></svg>
                Invoices
            </button>

            <button
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'account' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700  dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'account'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-4 h-4 mr-2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                Account
            </button>
        </nav>
    </div>

    <div class="pt-4 dark:border-gray-800">
        <div x-show="activeTab === 'dashboard'">
            <div class="bg-white rounded-md shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Left: Name and Account -->
                <div class="text-left">
                    <h2 class="text-lg font-semibold text-gray-900">John Doe</h2>
                    <p class="text-sm text-gray-600">Account: CUST-001 &bull; Acme Corp</p>
                </div>

                <!-- Center: Tax Status (Responsive) -->
                <div class="text-left md:text-center w-full md:w-auto">
                    <p class="text-xs uppercase tracking-wide text-gray-500 font-medium mb-1">Tax Status</p>
                    <div class="inline-flex items-center gap-2">
                        <span class="inline-flex items-center gap-2 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                            <x-heroicon-o-document-text class="w-4 h-4 text-green-500" />
                            Tax Exempt
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Valid until Dec 31, 2027</p>
                </div>

                <!-- Right: Status + Button -->
               <div class="flex flex-col items-start md:items-end gap-2 text-left md:text-right w-auto">
                    <!-- Status Badge -->
                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 w-auto">
                        Good Standing
                    </span>

                    <!-- Visit Website Button -->
                    <a href="#" class="inline-flex items-center gap-2 px-3 py-1 text-xs font-medium rounded-full bg-blue-600 text-white hover:bg-blue-700 w-auto">
                        <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                        Visit Website
                    </a>
                </div>
            </div>

            <div class="mx-auto mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Contact Information -->
                <div class="bg-white rounded-md shadow-sm p-5">
                    <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-4">
                        <x-heroicon-o-user class="w-5 h-5 mr-2 text-gray-500" />
                        Contact Information
                    </h3>
                    <div class="space-y-4 text-sm text-gray-700">
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-envelope class="w-5 h-5 mr-2 text-gray-500" />
                            <div>
                                <p>john.doe@acmecorp.com</p>
                                <p class="text-xs text-gray-500">Email Address</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <x-heroicon-o-phone class="w-5 h-5 mr-2 text-gray-500" />
                            <div>
                                <p>(555) 012-3456</p>
                                <p class="text-xs text-gray-500">Personal Phone</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <x-heroicon-o-device-phone-mobile class="w-5 h-5 mr-2 text-gray-500" />
                            <div>
                                <p>(555) 012-0100</p>
                                <p class="text-xs text-gray-500">Company Phone</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="bg-white rounded-md shadow-sm p-5 h-[250px] overflow-y-scroll overflow-x-hidden">
                    <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-4">
                    <x-heroicon-o-map-pin class="w-5 h-5 mr-2 text-gray-500" />
                    Address Information
                    </h3>

                    <div class="text-sm text-gray-700 space-y-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Billing Address</p>
                        <p>123 Main St, New York, NY 10001</p>
                    </div>

                    <hr class="border-gray-200">

                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Delivery Address</p>
                        <p>456 Oak Ave, New York, NY 10002</p>
                    </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4  mx-auto mt-6 mb-6">
                <!-- Current Balance -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                        <!-- Dollar Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Current Balance</p>
                    <p class="text-lg font-semibold text-gray-900">$2,750.00</p>
                    </div>
                </div>

                <!-- Available Credit -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 rounded-md p-2">
                        <!-- Trending Up Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Available Credit</p>
                    <p class="text-lg font-semibold text-gray-900">$12,250.00</p>
                    </div>
                </div>

                <!-- Open Invoices -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
                        <!-- Document Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-4 h-4"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Open Invoices</p>
                    <p class="text-lg font-semibold text-gray-900">2</p>
                    </div>
                </div>

                <!-- Last Payment -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 rounded-md p-2">
                    <!-- Calendar Icon -->
                    <x-heroicon-o-calendar class="w-5 h-5 text-green-500" />
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Last Payment</p>
                    <p class="text-lg font-semibold text-gray-900">Jan 18, 2025</p>
                    </div>
                </div>
            </div>

            <div class=" mx-auto px-4 py-6 bg-white rounded-md shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800 mb-6">Recent Orders</h2>
                    <a href="#" class="text-sm text-blue-600 mb-6" id="viewAllOrdersLink">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="bg-gray-50 text-gray-500 text-sm border-b">
                            <tr>
                                <th class="px-4 py-3 font-medium uppercase">Order ID</th>
                                <th class="px-4 py-3 font-medium uppercase">Product Name</th>
                                <th class="px-4 py-3 font-medium uppercase">Amount</th>
                                <th class="px-4 py-3 font-medium uppercase">Payment Methods</th>
                                <th class="px-4 py-3 font-medium uppercase">Status</th>
                                <th class="px-4 py-3 font-medium uppercase">Created</th>
                                <th class="px-4 py-3 font-medium uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">ORD-2025-001</td>
                                <td class="px-4 py-3">Premium Widget Set</td>
                                <td class="px-4 py-3">$1,249.95</td>
                                <td class="px-4 py-3">Credit / Debit</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">paid</span>
                                </td>
                                <td class="px-4 py-3">Jan 15, 2025</td>
                                <td class="px-4 py-3 flex items-center gap-3 text-blue-600">
                                    <!-- View -->
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-5 h-5" />
                                    </button>
                                    <!-- Download -->
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600" />
                                    </button>
                                </td>
                            </tr>

                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">ORD-2025-002</td>
                                <td class="px-4 py-3">Standard Widget Pack</td>
                                <td class="px-4 py-3">$875.50</td>
                                <td class="px-4 py-3">COD</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">paid</span>
                                </td>
                                <td class="px-4 py-3">Jan 20, 2025</td>
                                <td class="px-4 py-3 flex items-center gap-3 text-blue-600">
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-5 h-5" />
                                    </button>
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600" />
                                    </button>
                                </td>
                            </tr>

                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">ORD-2025-003</td>
                                <td class="px-4 py-3">Widget Accessories Kit</td>
                                <td class="px-4 py-3">$624.50</td>
                                <td class="px-4 py-3">Account</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Acct.</span>
                                </td>
                                <td class="px-4 py-3">Jan 25, 2025</td>
                                <td class="px-4 py-3 flex items-center gap-3 text-blue-600">
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-5 h-5" />
                                    </button>
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'orders'">
            <div class="bg-white rounded-md shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Left: Name and Account -->
                <div class="text-left">
                    <h2 class="text-lg font-semibold text-gray-900">John Doe</h2>
                    <p class="text-sm text-gray-600">Account: CUST-001 &bull; Acme Corp</p>
                </div>

                <!-- Center: Tax Status (Responsive) -->
                <div class="text-left md:text-center w-full md:w-auto">
                    <p class="text-xs uppercase tracking-wide text-gray-500 font-medium mb-1">Tax Status</p>
                    <div class="inline-flex items-center gap-2">
                        <span class="inline-flex items-center gap-2 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                            <x-heroicon-o-document-text class="w-4 h-4 text-green-500" />
                            Tax Exempt
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Valid until Dec 31, 2027</p>
                </div>

                <!-- Right: Status + Button -->
               <div class="flex flex-col items-start md:items-end gap-2 text-left md:text-right w-auto">
                    <!-- Status Badge -->
                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 w-auto">
                        Good Standing
                    </span>

                    <!-- Visit Website Button -->
                    <a href="#" class="inline-flex items-center gap-2 px-3 py-1 text-xs font-medium rounded-full bg-blue-600 text-white hover:bg-blue-700 w-auto">
                        <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                        Website Login
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mx-auto mt-6 mb-6">
                <!-- Paid Sales -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 rounded-md p-2">
                        <!-- Dollar Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            width="20" height="20" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-check-circle w-4 h-4">
                            <path d="M9 12l2 2l4 -4"></path>
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Paid Sales</p>
                    <p class="text-lg font-semibold text-gray-900">$2,125.45</p>
                    </div>
                </div>

                <!-- Pending Sales -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            width="20" height="20" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-alert-circle w-4 h-4">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Pending Sales</p>
                    <p class="text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                </div>

                <!-- Account Balance -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                        <x-heroicon-o-calendar class="w-5 h-5 " />
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Account Balance</p>
                    <p class="text-lg font-semibold text-gray-900">$2,750.00</p>
                    </div>
                </div>

                <!-- Open Invoices -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-red-100 text-red-600 rounded-md p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            width="20" height="20" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-alert-circle w-4 h-4">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Open Invoices</p>
                    <p class="text-lg font-semibold text-gray-900">$1,500.00</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-md shadow-sm p-6">
                <!-- Header with Filter -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800  mb-4 ">All Customer Orders</h2>
                    <div class="mb-6">
                        <label for="statusFilter" class="text-sm font-medium text-gray-700 mr-2">Filter by Status:</label>
                        <select id="statusFilter" class="border border-gray-300 rounded-md px-3 py-1 text-sm">
                            <option value="all">All Orders</option>
                            <option value="paid">paid</option>
                            <option value="acct">Acct.</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="bg-gray-50 text-gray-500 text-sm border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 font-medium uppercase">Order ID</th>
                                <th class="px-4 py-3 font-medium uppercase">Product Name</th>
                                <th class="px-4 py-3 font-medium uppercase">Amount</th>
                                <th class="px-4 py-3 font-medium uppercase">Payment Methods</th>
                                <th class="px-4 py-3 font-medium uppercase">Status</th>
                                <th class="px-4 py-3 font-medium uppercase">Created</th>
                                <th class="px-4 py-3 font-medium uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTable" class="divide-y divide-gray-200">
                            <tr class="order-row" data-status="paid">
                                <td class="px-4 py-3 font-medium text-gray-900">ORD-2025-001</td>
                                <td class="px-4 py-3">Premium Widget Set</td>
                                <td class="px-4 py-3">$1,249.95</td>
                                <td class="px-4 py-3">Credit / Debit</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">paid</span>
                                </td>
                                <td class="px-4 py-3">Jan 15, 2025</td>
                                <td class="px-4 py-3 flex items-center gap-3 text-blue-600">
                                    <!-- View -->
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-5 h-5" />
                                    </button>
                                    <!-- Download -->
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600" />
                                    </button>
                                </td>
                            </tr>

                            <tr class="order-row" data-status="paid">
                                <td class="px-4 py-3 font-medium text-gray-900">ORD-2025-002</td>
                                <td class="px-4 py-3">Standard Widget Pack</td>
                                <td class="px-4 py-3">$875.50</td>
                                <td class="px-4 py-3">COD</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">paid</span>
                                </td>
                                <td class="px-4 py-3">Jan 20, 2025</td>
                                <td class="px-4 py-3 flex items-center gap-3 text-blue-600">
                                    <!-- View -->
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-5 h-5" />
                                    </button>
                                    <!-- Download -->
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600" />
                                    </button>
                                </td>
                            </tr>

                            <tr class="order-row" data-status="acct">
                                <td class="px-4 py-3 font-medium text-gray-900">ORD-2025-003</td>
                                <td class="px-4 py-3">Widget Accessories Kit</td>
                                <td class="px-4 py-3">$624.50</td>
                                <td class="px-4 py-3">Account</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Acct.</span>
                                </td>
                                <td class="px-4 py-3">Jan 25, 2025</td>
                                <td class="px-4 py-3 flex items-center gap-3 text-blue-600">
                                    <!-- View -->
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-5 h-5" />
                                    </button>
                                    <!-- Download -->
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-green-600" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'credit'">
            <div class="bg-white rounded-md shadow-sm p-6 md:flex-row md:items-center md:justify-between gap-4">
                <!-- Left: Name and Account -->
                <div class="text-left">
                    <h2 class="text-lg font-semibold text-gray-900">Customer Credit Account Management</h2>
                    <p class="text-sm text-gray-600">Manage customer credit account transactions and balance</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mx-auto mt-6 mb-6">
                <!-- Current Balance -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-blue-100 text-blue-600 rounded-md p-2">
                        <!-- Dollar Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Current Balance</p>
                    <p class="text-lg font-semibold text-gray-900">$2,750.00</p>
                    </div>
                </div>

                <!-- Available Credit -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-green-100 text-green-600 rounded-md p-2">
                        <!-- Trending Up Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Available Credit</p>
                    <p class="text-lg font-semibold text-gray-900">$12,250.00</p>
                    </div>
                </div>

                <!-- Credit Limit -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-yellow-100 text-yellow-600 rounded-md p-2">
                       <svg xmlns="http://www.w3.org/2000/svg" 
                            width="20" height="20" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-alert-circle w-4 h-4">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Credit Limit</p>
                    <p class="text-lg font-semibold text-gray-900">$15,000.00</p>
                    </div>
                </div>

                <!-- Last Payment -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-purple-100 text-purple-600 rounded-md p-2">
                        <!-- Calendar Icon -->
                        <x-heroicon-o-calendar class="w-5 h-5" />
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Last Payment Date</p>
                    <p class="text-lg font-semibold text-gray-900">Jan 18, 2025</p>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white rounded-md shadow-sm mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-base font-semibold text-gray-800">Credit Utilization</h2>
                </div>
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600 mb-2">
                        Available: <span id="availableAmount">$0.00</span>
                    </div>
                    <div class="text-sm text-gray-600 mb-2">
                        Used: <span id="usedAmount">$0.00</span>
                    </div>
                </div>
                <!-- Progress Bar Container -->
                <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                    <div id="progressBar" class="h-full bg-blue-500 transition-all duration-500" style="width: 0%"></div>
                </div>

                <p class="text-sm text-center text-gray-500 mt-2"> Credit Limit: <span id="limitAmount">$0.00</span> </p>
            </div>

            <div class=" mx-auto bg-white shadow rounded-md p-6">
                <!-- Header and Filter -->
                <div class="border-b border-gray-200">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        
                        <!-- Title -->
                        <h2 class="text-lg font-semibold text-gray-800">Account Transactions</h2>

                        <!-- Buttons and Filter -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full lg:w-auto">
                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-2">
                                <a href="javascript:void(0)" id="openTemplatesModal"
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-credit-card w-4 h-4 mr-2">
                                        <rect width="20" height="14" x="2" y="5" rx="2" />
                                        <line x1="2" x2="22" y1="10" y2="10" />
                                    </svg>
                                    Credit / Debit
                                </a>

                                <a href="javascript:void(0)" id="openRefundModal"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-trending-up w-4 h-4 mr-2">
                                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                        <polyline points="16 7 22 7 22 13" />
                                    </svg>
                                    Refund
                                </a>

                                <a href="javascript:void(0)" id="openDiscountModal" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-sm flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-award w-4 h-4 mr-2">
                                        <circle cx="12" cy="8" r="6" />
                                        <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                    </svg>
                                    Discount
                                </a>

                                <a href="javascript:void(0)" id="openChargeModal" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-plus w-4 h-4 mr-2">
                                        <path d="M5 12h14" />
                                        <path d="M12 5v14" />
                                    </svg>
                                    New Charge
                                </a>
                            </div>

                            <!-- Divider (only for large screens) -->
                            <div class="hidden lg:block border-l border-gray-300 h-8"></div>

                            <!-- Filter -->
                            <div class="flex items-center space-x-2">
                                <label for="typeFilters" class="text-sm text-gray-600">Filter by Type:</label>
                                <select id="typeFilters" class="border border-gray-300 rounded px-3 py-1 text-sm">
                                <option value="all">All Transactions</option>
                                <option value="charge">Charge</option>
                                <option value="payment">Payment</option>
                                <option value="discount">Discount</option>
                                <option value="refund">Refund</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="bg-gray-50 text-gray-500 text-sm border-b uppercase">
                            <tr>
                                <th class="px-3 py-2 font-medium">Date</th>
                                <th class="px-3 py-2 font-medium">Type</th>
                                <th class="px-3 py-2 font-medium">Description</th>
                                <th class="px-3 py-3 text-right font-medium">Amount</th>
                                <th class="px-3 py-3 text-right font-medium">Sales Tax</th>
                                <th class="px-3 py-3 text-right font-medium">Balance Change</th>
                                <th class="px-3 py-3 text-right font-medium">Running Balance</th>
                                <th class="px-3 py-3 text-center font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transactionTable">
                            <tr data-status="charge" class="border-b status-row">
                                <td class="px-3 py-3">Jan 15, 2025</td>
                                <td class="px-3 py-3 text-red-600 ">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-3 h-3"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                                        <span class="ml-1 capitalize">charge</span>
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">Sarah Johnson</div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <div class="max-w-xs truncate text-gray-700">Premium Widget Set - New Rental</div>
                                    <div class="text-xs text-gray-500">Ref: ORD-2025-001</div>
                                </td>
                                <td class="px-3 py-3 text-right">$999.95</td>
                                <td class="px-3 py-3 text-right">$97.49</td>
                                <td class="px-3 py-3 text-right text-red-600">+$1,097.44</td>
                                <td class="px-3 py-3 text-right">$2,750.00</td>
                                <td class="px-3 py-3 text-blue-600 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- View -->
                                        <button title="View">
                                            <x-heroicon-o-eye class="w-4 h-4 text-blue-600" />
                                        </button>

                                        <!-- Download -->
                                        <button title="Download">
                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600" />
                                        </button>

                                        <!-- Note -->
                                        <a href="javascript:void(0)" id="openNoteModal">
                                            <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr data-status="payment" class="border-b status-row">
                                <td class="px-3 py-3">Jan 18, 2025</td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4">
                                            <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                            <line x1="2" x2="22" y1="10" y2="10"></line>
                                        </svg>
                                        <span class="ml-1 capitalize">payment</span>
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">System</div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <div class="max-w-xs truncate">Credit Card Payment</div>
                                    <div class="text-xs text-gray-500">Ref: PAY-2025-001</div>
                                </td>
                                <td class="px-3 py-3 text-right">$1,250.00</td>
                                <td class="px-3 py-3 text-right">$0.00</td>
                                <td class="px-3 py-3 text-right text-green-600">-$1,250.00</td>
                                <td class="px-3 py-3 text-right">$1,500.00</td>
                                <td class="px-3 py-3 text-blue-600 text-center">
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                                    </button>
                                    <!-- Download -->
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600" />
                                    </button>
                                </td>
                            </tr>

                            <tr data-status="charge" class="border-b status-row">
                                <td class="px-3 py-3">Jan 15, 2025</td>
                                <td class="px-3 py-3 text-red-600 ">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-3 h-3"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                                        <span class="ml-1 capitalize">charge</span>
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">Michael Chen</div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <div class="max-w-xs truncate text-gray-700">Standard Widget Pack - Rental Extension</div>
                                    <div class="text-xs text-gray-500">Ref: ORD-2025-002</div>
                                </td>
                                <td class="px-3 py-3 text-right">$449.97</td>
                                <td class="px-3 py-3 text-right">$43.87</td>
                                <td class="px-3 py-3 text-right text-red-600">+$493.84</td>
                                <td class="px-3 py-3 text-right">$1,993.84</td>
                                <td class="px-3 py-3 text-blue-600 text-center">
                                    <!-- View -->
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                                    </button>
                                    <!-- Download -->
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />
                                    </button>
                                    <!-- Note -->
                                    <button title="note">
                                        <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                                    </button>
                                </td>
                            </tr>
                            
                            <tr data-status="discount" class="border-b status-row">
                                <td class="px-3 py-3">Jan 22, 2025</td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-4 h-4"><circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path></svg>
                                        <span class="ml-1 capitalize">discount</span>
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">Emily Rodriguez</div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <div class="max-w-xs truncate">Volume Discount - Repeat Customer</div>
                                    <div class="text-xs text-gray-500">Ref: DISC-2025-001</div>
                                </td>
                                <td class="px-3 py-3 text-right">$100.00</td>
                                <td class="px-3 py-3 text-right">$9.75</td>
                                <td class="px-3 py-3 text-right text-green-600">-$109.75</td>
                                <td class="px-3 py-3 text-right">$1,884.09</td>
                                <td class="px-3 py-3 text-blue-600 text-center">
                                    <!-- View -->
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                                    </button>
                                    <!-- Download -->
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />
                                    </button>
                                </td>
                            </tr>
                            
                            <tr data-status="charge" class="border-b status-row">
                                <td class="px-4 py-3">Jan 25, 2025</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-4 h-4">
                                        <path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                                        <span class="ml-1 capitalize">charge</span>
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">David Thompson</div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs truncate">Widget Accessories Kit - Product Purchase</div>
                                    <div class="text-xs text-gray-500">Ref: ORD-2025-003</div>
                                </td>
                                <td class="px-4 py-3 text-right">$199.98</td>
                                <td class="px-4 py-3 text-right">$19.50</td>
                                <td class="px-4 py-3 text-right text-red-600">+$219.48</td>
                                <td class="px-4 py-3 text-right">$2,103.57</td>
                                <td class="px-4 py-3 text-blue-600 text-center">
                                    <!-- View -->
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                                    </button>
                                    <!-- Download -->
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />
                                    </button>
                                </td>
                            </tr>

                            <tr data-status="refund" class="border-b status-row">
                                <td class="px-3 py-3">Jan 28, 2025</td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                                    <span class="ml-1 capitalize">refund</span>
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">Sarah Johnson</div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <div class="max-w-xs truncate">Damaged Item Refund</div>
                                    <div class="text-xs text-gray-500">Ref: REF-2025-001</div>
                                </td>
                                <td class="px-3 py-3 text-right">$50.00</td>
                                <td class="px-3 py-3 text-right">$4.88</td>
                                <td class="px-3 py-3 text-right text-green-600">-$54.88</td>
                                <td class="px-3 py-3 text-right">$2,048.69</td>
                                <td class="px-3 py-3 text-blue-600 text-center">
                                    <!-- View -->
                                    <button title="View">
                                        <x-heroicon-o-eye class="w-4 h-4 mr-1" />
                                    </button>
                                    <!-- Download -->
                                    <button title="Download">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />
                                    </button>
                                    <!-- Note -->
                                    <button title="note">
                                        <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'invoices'">
            <div class="bg-white p-6 rounded shadow-sm space-y-6 " >
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Customer Invoices Management</h2>
                        <p class="text-sm text-gray-600">Manage customer invoices, payments, and billing administration</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-md font-medium">
                            + Create Invoice
                        </button>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Current Balance</p>
                            <p class="text-lg font-bold text-gray-900">$2,750.00</p>
                        </div>
                    </div>
                </div>

            </div>
            
            <!-- Invoice Status Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6 mb-6">
                <!-- Paid -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-sm text-gray-600">Paid Invoices</p>
                        <p class="text-xl font-bold text-gray-900">$1,250.00</p>
                    </div>
                    <div class="bg-green-100 p-2 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            width="20" height="20" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            class="lucide lucide-check-circle w-6 h-6 text-green-500">
                            <path d="M9 12l2 2l4 -4"></path>
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                    </div>
                </div>

                <!-- Pending -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-sm text-gray-600">Pending Invoices</p>
                        <p class="text-xl font-bold text-gray-900">$624.50</p>
                    </div>
                    <div class="bg-yellow-100 p-2 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Overdue -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between shadow-sm ">
                    <div>
                        <p class="text-sm text-gray-600">Overdue Invoices</p>
                        <p class="text-xl font-bold text-gray-900">$875.50</p>
                    </div>
                    <div class="bg-red-100 p-2 rounded-md  text-red-500">
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
                </div>
            </div>

            <div class="bg-white rounded-md shadow-sm p-6">
                <!-- Header with Filter -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Invoices</h2>
                    <div class="mb-6">
                        <label for="invoiceFilter" class="text-sm font-medium text-gray-700 mr-2">Filter by Status:</label>
                        <select id="invoiceFilter" class="border border-gray-300 rounded-md px-3 py-1 text-sm">
                            <option value="all">All Orders</option>
                            <option value="paid">paid</option>
                            <option value="overdue">overdue</option>
                            <option value="pending">pending</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="bg-gray-50 text-gray-500 text-sm border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 font-medium uppercase">Invoice</th>
                                <th class="px-4 py-3 font-medium uppercase">Customer</th>
                                <th class="px-4 py-3 font-medium uppercase">Date</th>
                                <th class="px-4 py-3 font-medium uppercase">Due Date</th>
                                <th class="px-4 py-3 font-medium uppercase">Amount</th>
                                <th class="px-4 py-3 font-medium uppercase">Status</th>
                                <th class="px-4 py-3 font-medium uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceTable" class="divide-y divide-gray-200">
                            <tr class="invoice-row" data-status="paid">
                                <td class="px-4 py-3 font-medium text-gray-900">INV-2025-001</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm"><div class="font-medium text-gray-900">Acme Corp</div>
                                    <div class="text-gray-500">John Doe</div></div>
                                </td>
                                <td class="px-4 py-3">Jan 15, 2025</td>
                                <td class="px-4 py-3">Feb 14, 2025</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-500">$1,250.00</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">paid</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                                        <x-heroicon-o-eye class="w-4 h-4 mr-1" />View
                                    </button>
                                    <button class="text-green-600 hover:text-green-900 inline-flex items-center">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />Download
                                    </button>
                                    <button class="text-green-600 hover:text-green-900 inline-flex items-center">
                                        <x-heroicon-o-pencil-square class="w-4 h-4 text-green-600 mr-1" />Edit
                                    </button>
                                    <button class="text-purple-600 hover:text-purple-900 inline-flex items-center">
                                        <x-heroicon-o-envelope class="w-4 h-4 text-gray-500 mr-1" /> Send
                                    </button>
                                </td>
                            </tr>

                            <tr class="invoice-row" data-status="overdue">
                                <td class="px-4 py-3 font-medium text-gray-900">INV-2025-002</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm"><div class="font-medium text-gray-900">Acme Corp</div>
                                    <div class="text-gray-500">John Doe</div></div>
                                </td>
                                <td class="px-4 py-3">Jan 20, 2025</td>
                                <td class="px-4 py-3">Feb 19, 2025</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-500">$875.50</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">overdue</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                                        <x-heroicon-o-eye class="w-4 h-4 mr-1" />View
                                    </button>
                                    <button class="text-green-600 hover:text-green-900 inline-flex items-center">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />Download
                                    </button>
                                    <button class="text-green-600 hover:text-green-900 inline-flex items-center">
                                        <x-heroicon-o-pencil-square class="w-4 h-4 text-green-600 mr-1" />Edit
                                    </button>
                                    <button class="text-purple-600 hover:text-purple-900 inline-flex items-center">
                                        <x-heroicon-o-envelope class="w-4 h-4 text-gray-500 mr-1" /> Send
                                    </button>
                                </td>
                            </tr>

                            <tr class="invoice-row" data-status="pending">
                                <td class="px-4 py-3 font-medium text-gray-900">INV-2025-003</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm"><div class="font-medium text-gray-900">Acme Corp</div>
                                    <div class="text-gray-500">John Doe</div></div>
                                </td>
                                <td class="px-4 py-3">Jan 25, 2025</td>
                                <td class="px-4 py-3">Feb 24, 2025</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-500">$624.50</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">pending</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                                        <x-heroicon-o-eye class="w-4 h-4 mr-1" />View
                                    </button>
                                    <button class="text-green-600 hover:text-green-900 inline-flex items-center">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-green-600 mr-1" />Download
                                    </button>
                                    <button class="text-green-600 hover:text-green-900 inline-flex items-center">
                                        <x-heroicon-o-pencil-square class="w-4 h-4 text-green-600 mr-1" />Edit
                                    </button>
                                    <button class="text-purple-600 hover:text-purple-900 inline-flex items-center">
                                        <x-heroicon-o-envelope class="w-4 h-4 text-gray-500 mr-1" /> Send
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div x-show="activeTab === 'account'">
            <div class="bg-white p-6 rounded shadow mb-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Title and Description -->
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Customer Account Management</h2>
                        <p class="text-sm text-gray-500">Manage customer account information and administrative settings</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:gap-2">
                        <span class="text-sm text-gray-700 mr-2 sm:mr-0 sm:mb-1">Admin Actions:</span>
                        <div class="flex items-center gap-2">
                            <button class="bg-red-600 text-white px-3 py-1 rounded text-sm">Suspend Account</button>
                            <button class="bg-yellow-500 text-white px-3 py-1 rounded text-sm">Reset Password</button>
                            <button id="editBtn" class="bg-blue-600 inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-md hover:bg-green-700 transition"> Edit Information</button>
                            <button id="saveBtn" style="display:none;" class="bg-green-600 text-white px-3 py-1 rounded text-sm">Save Changes</button>
                            <button id="cancelBtn" style="display:none;" class="bg-gray-700 text-white px-3 py-1 rounded text-sm">Cancel</button>
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-700">
                        <div>
                            <label class="text-gray-700 mb-1">First Name</label>
                            <div class="static-view">John</div>
                             <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="John" />
                        </div>

                        <div>
                            <label class="text-gray-700 mb-1">Last Name</label>
                            <div class="static-view">Doe</div>
                            <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="Doe" />
                        </div>

                        <div class="col-span-2">
                            <label class="text-gray-700 text-sm mb-1 block">Email Address</label>
                            <div class=" static-view">
                                <p class="text-gray-900 flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-4 h-4 mr-2 text-gray-500" />john.doe@acmecorp.com
                                </p>
                            </div>
                            <!-- Edit View -->
                            <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" type="email" value="john.doe@acmecorp.com" />
                        </div>

                        <div class="col-span-2">
                            <label class="text-gray-700 text-sm mb-1 block">Phone Number</label>
                            <div class=" static-view">
                                <p class="text-gray-900 flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 mr-2 text-gray-500" />(555) 012-3456
                                </p>
                            </div>
                            <!-- Edit View -->
                            <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="(555) 012-3456" />
                        </div>

                    </div>
                </div>

                <!-- Company Information -->
                <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200">
                    <h3 class="text-md font-semibold mb-4 flex items-center gap-2">
                        <x-heroicon-o-building-office class="w-5 h-5 text-gray-500" /> Company Information
                    </h3>
                    <div class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                        <div>
                            <label class="text-sm text-gray-700 mb-1">Company Name</label>
                            <div class="static-view">Acme Corp</div>
                            <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="Acme Corp" />
                        </div>
                        
                        <div class="col-span-2">
                            <label class="text-gray-700 text-sm mb-1 block">Company Phone</label>
                            <div class=" static-view">
                                <p class="text-gray-900 flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 mr-2 text-gray-500" />(555) 012-3456
                                </p>
                            </div>
                            <!-- Edit View -->
                            <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="(555) 012-3456" />
                        </div>

                        <div class="col-span-2">
                            <label class="text-gray-700 text-sm mb-1 block">Website</label>
                            <div class=" static-view">
                                <p class="text-gray-900 flex items-center gap-2">
                                      <x-heroicon-o-globe-alt class="w-4 h-4 mr-2 text-gray-500" /><a class="text-blue-600" href="https://acme-corp.com">https://acme-corp.com</a>
                                </p>
                            </div>
                            <input class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" value="https://acme-corp.com" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                <!-- Billing Address -->
                <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 ">
                    <h3 class="text-base font-semibold mb-4 flex items-center gap-2">
                        <x-heroicon-o-map-pin class="w-5 h-5 mr-2 text-gray-500" />
                        Billing Address
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm text-gray-700 mb-1 edit-view">Address</label>
                            <div class="static-view text-gray-900 whitespace-pre-line text-sm">123 Main St, New York, NY 10001</div>
                            <input type="text" value="123 Main St" class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="edit-view">
                                <label class="text-sm text-gray-700 mb-1">City</label>
                                <input type="text" value="New York" class="pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" />
                            </div>
                            <div class="edit-view">
                                <label class="text-sm text-gray-700 mb-1">Zip Code</label>
                                <input type="text" value="10001" class="pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" />
                            </div>
                        </div>
                        <div class="edit-view">
                            <label class="text-sm text-gray-700 mb-1">State</label>
                            <select class="pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm">
                                <option selected>New York</option>
                                <option>California</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200">
                    <h3 class="text-base font-semibold mb-4 flex items-center gap-2">
                        <x-heroicon-o-map-pin class="w-5 h-5 mr-2 text-gray-500" />
                        Delivery Address
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm text-gray-700 mb-1 edit-view">Address</label> 
                            <div class="static-view text-gray-900 whitespace-pre-line text-sm">456 Oak Ave, New York, NY 10002</div>
                            <input type="text" value="456 Oak Ave" class="edit-view pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="edit-view">
                                <label class="text-sm text-gray-700 mb-1">City</label>
                                <input type="text" value="New York" class="pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" />
                            </div>
                            <div class="edit-view">
                                <label class="text-sm text-gray-700 mb-1">Zip Code</label>
                                <input type="text" value="10002" class="pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm" />
                            </div>
                        </div>
                        <div class="edit-view">
                            <label class="text-sm text-gray-700 mb-1">State</label>
                            <select class="pl-2 pr-2 py-2 w-full border border-gray-300 rounded-md text-sm">
                                <option selected>New York</option>
                                <option>California</option>
                            </select>
                        </div>
                    </div>
                </div>
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
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">Good Standing</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Account Approved:</span>
                            <span class="text-green-600 font-medium flex items-center gap-1 text-xs">
                                <x-heroicon-o-check-circle class="w-4 h-4 text-green-600" />
                                Approved
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Customer Since:</span>
                            <span class="text-xs">Jan 15, 2024</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Customer ID:</span>
                            <span class="text-xs">CUST-001</span>
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
                                <span class="text-gray-900 font-semibold">$15,000.00</span>
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
                            <span class="text-green-700 bg-green-100 px-2 py-1 rounded-full text-xs font-medium">Tax Exempt</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Valid Until:</span>
                            <span class="text-sm">Dec 31, 2027</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700 mb-1">Uploaded:</span>
                            <span class="text-sm">Mar 3, 2025</span>
                        </div>
                    </div>

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
                                <div class="font-medium text-gray-800 truncate">acme_corp_tax_exempt_2025.pdf</div>
                                <div class="text-gray-500 text-xs">
                                Status: <span class="text-green-600 font-medium">Approved</span>
                                </div>
                            </div>
                            </div>

                            <!-- Right Side: Actions -->
                            <div class="flex gap-4 text-sm justify-end sm:justify-start">
                                <a href="#" class="text-blue-600">View</a>
                                <a href="#" class="text-green-600">Approve</a>
                                <a href="#" class="text-red-600">Reject</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Record Payment Wrapper -->
<div id="taxdocModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Upload Tax Exempt Document</h2>
                </div>
                <button id="closetaxdocBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
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
                                <input type="file" id="fileInput" class="hidden" accept=".pdf,.png,.jpg,.jpeg" onchange="handleFileChange(event)" />
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
                                <button onclick="clearFile()" class="px-3 py-1 text-sm rounded bg-red-600 text-white">✕</button>
                            </div>
                        </div>
                    </div>

                    <!-- Document Type Dropdown -->
                    <div class="mt-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                        <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option>Tax Exempt Certificate</option>
                        <option>Resale Certificate</option>
                        <option>Non-Profit Exemption</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-5 flex justify-end gap-2">
                        <button id="cancelBtntaxdoc" class="px-4 py-2 border border-gray-300 rounded text-sm">Cancel</button>
                        <button class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-teal-700">Upload Document</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Record Payment Wrapper -->
<div id="templatesModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 mr-2 text-green-600">
                        <rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line>
                    </svg>
                    <h2 class="text-lg font-medium text-gray-900">Record Payment</h2>
                </div>
                <button id="closeModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" p-6 overflow-y-auto">
                <form>
                    <!-- Payment Amount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount</label>
                        <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" placeholder="0.00"
                                class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/>
                        </div>
                    </div>
                    <!-- Payment Method -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700  ">
                            <option>Select payment method</option>
                            <option>Credit / Debit Card</option>
                            <option>Cash</option>
                            <option>Check</option>
                            <option>Bank Transfer</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 ">
                        <option>Select person responsible</option>
                        <option>John Doe</option>
                        <option>Jane Smith</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea rows="3" placeholder="Enter any additional notes..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pb-4">
                        <button type="button" id="canceltempBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitTemplatesBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                            Record Payment
                        </button>
                    </div>
                </form>
            
            </div>
            <!-- <div class="flex justify-end gap-2 px-6 pb-4">
                <button type="button" id="cancelBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">Cancel</button>
                <button type="button" id="submitTemplatesBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-blue-700"> Record Payment</button>
            </div> -->
        </div>
    </div>
</div>

<!-- Process Refund Wrapper -->
<div id="refundModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-blue-600 rounded-md p-2">
                        <!-- Trending Up Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Process Refund</h2>
                </div>
                <button id="closeRefundModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" p-6 overflow-y-auto">
                <form>
                    <!-- Payment Amount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Refund Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input type="number" placeholder="0.00" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/>
                        </div>
                    </div>
                    <!-- Payment Method -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Refund Reason</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700  ">
                            <option value="">Select refund reason</option>
                            <option value="damaged-item">Damaged Item</option>
                            <option value="wrong-item">Wrong Item Shipped</option>
                            <option value="customer-cancellation">Customer Cancellation</option>
                            <option value="overcharge">Billing Overcharge</option>
                            <option value="duplicate-charge">Duplicate Charge</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 ">
                        <option>Select person responsible</option>
                        <option>John Doe</option>
                        <option>Jane Smith</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea rows="3" placeholder="Describe the reason for this refund..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pb-4">
                        <button type="button" id="cancelRefundBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitRefundBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                            Process Refund
                        </button>
                    </div>
                </form>
            
            </div>
            <!-- <div class="flex justify-end gap-2 px-6 pb-4">
                <button type="button" id="cancelBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">Cancel</button>
                <button type="button" id="submitTemplatesBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-blue-700"> Record Payment</button>
            </div> -->
        </div>
    </div>
</div>

<!-- Apply Discount Wrapper -->
<div id="discountModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-purple-600 rounded-md p-2">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-award w-4 h-4 mr-2">
                                <circle cx="12" cy="8" r="6" />
                                <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                            </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Apply Discount</h2>
                </div>
                <button id="closeDiscountModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" p-6 overflow-y-auto">
                <form>
                    <!-- Discount Amount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input type="number" placeholder="0.00" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/>
                        </div>
                    </div>
                    <!-- Discount Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Reason</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700  ">
                            <option value="">Select discount reason</option>
                            <option value="volume-discount">Volume Discount</option>
                            <option value="repeat-customer">Repeat Customer Discount</option>
                            <option value="damage-waiver">Damage Waiver Protection</option>
                            <option value="misc-management">Misc. Management Discount</option>
                        </select>
                    </div>
                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 ">
                        <option>Select person responsible</option>
                        <option>John Doe</option>
                        <option>Jane Smith</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea rows="3" placeholder="Enter any additional notes about this discount..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pb-4">
                        <button type="button" id="cancelDiscountBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitDiscountBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                            Apply Discount
                        </button>
                    </div>
                </form>
            
            </div>
        </div>
    </div>
</div>

<!-- New Charge Wrapper -->
<div id="chargeModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-red-600 rounded-md p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-plus w-4 h-4 mr-2">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">New Charge</h2>
                </div>
                <button id="closeChargeModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class=" p-6 overflow-y-auto">
                <form>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Amount</label>
                         <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input type="number" placeholder="0.00" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"/>
                        </div>
                    </div>
                    <!-- Sales Tax Treatment -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sales Tax Treatment</label>
                        <div class="space-y-2 text-sm text-gray-700">
                            <label class="flex items-start gap-2">
                                <input type="radio" name="tax" value="add" class="mt-1.5 text-blue-600 focus:ring-blue-500" checked>
                                <div>
                                    <p class="font-medium">Add Sales Tax</p>
                                    <p class="text-gray-500">Add 9.75% sales tax to the entered amount</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2">
                                <input type="radio" name="tax" value="free" class="mt-1.5 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <p class="font-medium">Tax Free</p>
                                    <p class="text-gray-500">No sales tax applied to this charge</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2">
                                <input type="radio" name="tax" value="reverse" class="mt-1.5 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <p class="font-medium">Reverse Sales Tax</p>
                                    <p class="text-gray-500">Split entered amount proportionally between base amount and tax</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Charge Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Reason</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select charge reason</option>
                            <option value="new-rental">New Rental</option>
                            <option value="rental-extension">Rental Extension</option>
                            <option value="damages">Damages</option>
                            <option value="fuel-charge">Fuel Charge</option>
                            <option value="cleaning-charge">Cleaning Charge</option>
                            <option value="missing-items">Missing Items</option>
                            <option value="product-purchase">Product Purchase</option>
                        </select>
                    </div>

                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible</label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 ">
                        <option>Select person responsible</option>
                        <option>John Doe</option>
                        <option>Jane Smith</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea rows="3" placeholder="Enter any additional notes about this charge..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pb-4">
                        <button type="button" id="cancelChargeBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitChargeBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                            Add Charge
                        </button>
                    </div>
                </form>
            
            </div>
        </div>
    </div>
</div>

<!-- Transaction Note Wrapper -->
<div id="noteModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                     <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                    <h2 class="text-lg font-medium text-gray-900">Transaction Note</h2>
                </div>
                <button id="closeNoteModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto">
                <div class="bg-gray-50 rounded-lg mb-4 text-sm space-y-1">
                    <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Transaction:</span>
                    <span class="text-gray-700">ORD-2025-001</span>
                    </div>
                    <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Date:</span>
                    <span class="text-gray-700">Jan 15, 2025</span>
                    </div>
                    <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Amount:</span>
                    <span class="text-gray-700">$1,097.44</span>
                    </div>
                </div>

                 <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700 block mb-1">Note</label>
                    <div id="noteContainer" class="border border-gray-300 rounded-md p-3 text-sm text-gray-700 bg-white">
                        Customer requested expedited processing due to urgent project deadline. Approved by management for priority handling.
                    </div>
                </div>

                 <!-- Footer -->
                <div id="actionButtons" class="flex justify-end gap-2 py-4 border-t">
                    <button onclick="closeModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Close</button>
                    <button onclick="enableEdit()" class="px-4 py-2 text-sm rounded bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">Edit Note</button>
                </div>

            </div>
        </div>
    </div>
</div>


@endsection

<script>
  function handleFileChange(event) {
    const file = event.target.files[0];
    const fileNameDisplay = document.getElementById("fileNameDisplay");
    const fileActions = document.getElementById("fileActions");
    const viewLink = document.getElementById("viewFileLink");
    const uploadUI = document.getElementById("uploadUI");

    if (file) {
      fileNameDisplay.textContent = file.name;
      fileActions.style.display = "flex";
      uploadUI.style.display = "none";

      // Show file in new tab (temporary blob link)
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
    
<script>
  function initializeEditUI() {
    const editBtn = document.getElementById("editBtn");
    const saveBtn = document.getElementById("saveBtn");
    const cancelBtn = document.getElementById("cancelBtn");
    const staticFields = document.querySelectorAll(".static-view");
    const editFields = document.querySelectorAll(".edit-view");

    // Always start in view mode
    staticFields.forEach(el => el.style.display = "block");
    editFields.forEach(el => el.style.display = "none");
    editBtn.style.display = "inline-block";
    saveBtn.style.display = "none";
    cancelBtn.style.display = "none";

    if (editBtn && cancelBtn && saveBtn) {
      editBtn.onclick = () => {
        staticFields.forEach(el => el.style.display = "none");
        editFields.forEach(el => el.style.display = "block");
        editBtn.style.display = "none";
        saveBtn.style.display = "inline-block";
        cancelBtn.style.display = "inline-block";
      };

      cancelBtn.onclick = () => {
        staticFields.forEach(el => el.style.display = "block");
        editFields.forEach(el => el.style.display = "none");
        editBtn.style.display = "inline-block";
        saveBtn.style.display = "none";
        cancelBtn.style.display = "none";
      };
    }
  }

  document.addEventListener("DOMContentLoaded", initializeEditUI);

  // Also re-run when the tab becomes visible (you must trigger this yourself)
  document.querySelectorAll('[data-tab]').forEach(tab => {
    tab.addEventListener('click', function () {
      setTimeout(initializeEditUI, 100); // slight delay to allow DOM to render tab
    });
  });
</script>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('taxdocModalWrapper');
        const openBtn = document.getElementById('opentaxdocModal');
        const closeBtn = document.getElementById('closetaxdocBtn');
        const cancelBtn = document.getElementById('cancelBtntaxdoc');

        openBtn.addEventListener('click', () => {
            modalWrapper.style.display = 'flex';
        });

        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Optional: Close when clicking outside the modal
        modalWrapper.addEventListener('click', (e) => {
            if (e.target === modalWrapper) {
                closeModal();
            }
        });
    });
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const viewAllLink = document.getElementById("viewAllOrdersLink");
    const ordersTabBtn = document.getElementById("tab-orders");

    viewAllLink?.addEventListener("click", function (e) {
        e.preventDefault();
        if (ordersTabBtn) {
            ordersTabBtn.click(); // Simulate tab button click
        }
    });
});
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById('invoiceFilter');
    const rows = document.querySelectorAll('.invoice-row');

    // Count statuses
    const statusCounts = {};

    rows.forEach(row => {
      const status = row.getAttribute('data-status');
      statusCounts[status] = (statusCounts[status] || 0) + 1;
    });

    // Add counters to select options
    Array.from(filter.options).forEach(option => {
      const value = option.value;
      if (value === 'all') {
        option.textContent = `All Orders (${rows.length})`;
      } else if (statusCounts[value] !== undefined) {
        option.textContent = `${value.charAt(0).toUpperCase() + value.slice(1)} (${statusCounts[value]})`;
      }
    });

    // Filter rows on change
    filter.addEventListener('change', function () {
      const value = this.value;

      rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (value === 'all' || rowStatus === value) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('.order-row');

    // Count statuses
    const statusCounts = {};

    rows.forEach(row => {
      const status = row.getAttribute('data-status');
      statusCounts[status] = (statusCounts[status] || 0) + 1;
    });

    // Add counters to select options
    Array.from(filter.options).forEach(option => {
      const value = option.value;
      if (value === 'all') {
        option.textContent = `All Orders (${rows.length})`;
      } else if (statusCounts[value] !== undefined) {
        option.textContent = `${value.charAt(0).toUpperCase() + value.slice(1)} (${statusCounts[value]})`;
      }
    });

    // Filter rows on change
    filter.addEventListener('change', function () {
      const value = this.value;

      rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (value === 'all' || rowStatus === value) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });
</script>


<!-- <script>
  document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('.order-row');

    filter.addEventListener('change', function () {
      const value = this.value;

      rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (value === 'all' || rowStatus === value) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });
</script> -->


<script>
document.addEventListener('DOMContentLoaded', function () {
    const creditUsed = 5050;
    const creditLimit = 15000;
    const available = creditLimit - creditUsed;
    const percentUsed = (creditUsed / creditLimit) * 100;

    document.getElementById("usedAmount").textContent = `$${creditUsed.toLocaleString()}`;
    document.getElementById("limitAmount").textContent = `$${creditLimit.toLocaleString()}`;
    document.getElementById("availableAmount").textContent = `$${available.toLocaleString()}`;

    document.getElementById("progressBar").style.width = `${percentUsed}%`;
});
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById('typeFilters');
    const rows = document.querySelectorAll('.status-row');

    // Count statuses
    const statusCounts = {};

    rows.forEach(row => {
      const status = row.getAttribute('data-status');
      statusCounts[status] = (statusCounts[status] || 0) + 1;
    });

    // Add counters to select options
    Array.from(filter.options).forEach(option => {
      const value = option.value;
      if (value === 'all') {
        option.textContent = `All Orders (${rows.length})`;
      } else if (statusCounts[value] !== undefined) {
        option.textContent = `${value.charAt(0).toUpperCase() + value.slice(1)} (${statusCounts[value]})`;
      }
    });

    // Filter rows on change
    filter.addEventListener('change', function () {
      const value = this.value;

      rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (value === 'all' || rowStatus === value) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });
</script>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('templatesModalWrapper');
        const openBtn = document.getElementById('openTemplatesModal');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('canceltempBtn');

        openBtn.addEventListener('click', () => {
            modalWrapper.style.display = 'flex';
        });

        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Optional: Close when clicking outside the modal
        modalWrapper.addEventListener('click', (e) => {
            if (e.target === modalWrapper) {
                closeModal();
            }
        });
    });
</script>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('refundModalWrapper');
        const openBtn = document.getElementById('openRefundModal');
        const closeBtn = document.getElementById('closeRefundModalBtn');
        const cancelBtn = document.getElementById('cancelRefundBtn');

        openBtn.addEventListener('click', () => {
            modalWrapper.style.display = 'flex';
        });

        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Optional: Close when clicking outside the modal
        modalWrapper.addEventListener('click', (e) => {
            if (e.target === modalWrapper) {
                closeModal();
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('discountModalWrapper');
        const openBtn = document.getElementById('openDiscountModal');
        const closeBtn = document.getElementById('closeDiscountModalBtn');
        const cancelBtn = document.getElementById('cancelDiscountBtn');

        openBtn.addEventListener('click', () => {
            modalWrapper.style.display = 'flex';
        });

        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Optional: Close when clicking outside the modal
        modalWrapper.addEventListener('click', (e) => {
            if (e.target === modalWrapper) {
                closeModal();
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('chargeModalWrapper');
        const openBtn = document.getElementById('openChargeModal');
        const closeBtn = document.getElementById('closeChargeModalBtn');
        const cancelBtn = document.getElementById('cancelChargeBtn');

        openBtn.addEventListener('click', () => {
            modalWrapper.style.display = 'flex';
        });

        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Optional: Close when clicking outside the modal
        modalWrapper.addEventListener('click', (e) => {
            if (e.target === modalWrapper) {
                closeModal();
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('noteModalWrapper');
        const openBtn = document.getElementById('openNoteModal');
        const closeBtn = document.getElementById('closeNoteModalBtn');
        const cancelBtn = document.getElementById('cancelNoteBtn');

        openBtn.addEventListener('click', () => {
            modalWrapper.style.display = 'flex';
        });

        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Optional: Close when clicking outside the modal
        modalWrapper.addEventListener('click', (e) => {
            if (e.target === modalWrapper) {
                closeModal();
            }
        });
    });
</script>

<script>
  let originalNote = '';

  function openModal() {
    document.getElementById('noteModal').classList.remove('hidden');
  }

  function closeModal() {
    document.getElementById('noteModal').classList.add('hidden');
    cancelEdit(); // reset state if in edit mode
  }

  function enableEdit() {
    const noteContainer = document.getElementById('noteContainer');
    originalNote = noteContainer.innerText.trim();
    noteContainer.classList.add('p-0', 'border-0');

    noteContainer.innerHTML = `
      <textarea id="noteTextarea" class="w-full h-32 border border-gray-300 rounded-md p-2 text-sm focus:ring-blue-500 focus:border-blue-500">${originalNote}</textarea>
    `;

    document.getElementById('actionButtons').innerHTML = `
      <button onclick="closeModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Close</button>
      <button onclick="cancelEdit()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Cancel</button>
      <button onclick="saveNote()" class="px-4 py-2 text-sm rounded bg-green-600 text-white hover:bg-green-700">Save Note</button>
    `;
  }

  function cancelEdit() {
    const noteContainer = document.getElementById('noteContainer');
    noteContainer.innerText = originalNote;
    noteContainer.classList.remove('p-0', 'border-0');

    restoreButtons();
  }

  function saveNote() {
    const noteContainer = document.getElementById('noteContainer');
    const newNote = document.getElementById('noteTextarea').value;

    noteContainer.innerText = newNote;
    noteContainer.classList.remove('p-0', 'border-0');

    restoreButtons();
  }

  function restoreButtons() {
    document.getElementById('actionButtons').innerHTML = `
      <button onclick="closeModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Close</button>
      <button onclick="enableEdit()" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Edit Note</button>
    `;
  }
</script>

<script>
    function closeModal() {
    document.getElementById('noteModalWrapper').style.display = 'none';
    cancelEdit(); // reset state if in edit mode
    }
</script>

<!-- <script>
  document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById('typeFilters');
    const rows = document.querySelectorAll('.status-row');

    filter.addEventListener('change', function () {
      const value = this.value;

      rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (value === 'all' || rowStatus === value) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });
</script> -->

@push('js')


