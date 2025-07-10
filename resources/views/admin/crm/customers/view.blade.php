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
                class="inline-flex items-center gap-2 border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'dashboard' ? ' text-brand-500 border-brand-500  dark:text-brand-400 dark:border-brand-400' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'dashboard'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4 mr-2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                </svg>
                Dashboard
            </button>
            <button
                class="inline-flex items-center gap-2 border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'orders' ? ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'orders'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-4 h-4 mr-2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
                Orders
            </button>
            <button
                class="inline-flex items-center gap-2 border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'credit' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'credit'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4 mr-2"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                Credit Account
            </button>
            <button
                class="inline-flex items-center gap-2 border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'invoices' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700  dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'invoices'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4 mr-2"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line></svg>
                Invoices
            </button>
            <button
                class="inline-flex items-center gap-2 border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'account' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700  dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'account'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-4 h-4 mr-2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                Account
            </button>
        </nav>
    </div>

    <div class="pt-4 dark:border-gray-800">
        <div x-show="activeTab === 'dashboard'">
            <!-- <h3 class="mb-1 text-xl font-medium text-gray-800 dark:text-white/90">
                Dashboard
            </h3> -->
            <div class="bg-white rounded-md shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Left: Name and Account -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">John Doe</h2>
                    <p class="text-sm text-gray-600">Account: CUST-001 &bull; Acme Corp</p>
                </div>

                <!-- Center: Tax Status -->
                <div class="text-center md:text-left">
                    <p class="text-xs uppercase tracking-wide text-gray-500 font-medium mb-1">Tax Status</p>
                    <div class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                        <svg class="w-4 h-4 mr-1 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                        </svg>
                        Tax Exempt
                    </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Valid until Dec 31, 2027</p>
                </div>

                <!-- Right: Status + Button -->
                <div class="flex flex-col md:items-end gap-2">
                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                    Good Standing
                    </span>
                    <a href="#" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full bg-blue-600 text-white hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 13v6a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-6m16-4l-8-8-8 8m8-8v16" />
                    </svg>
                    Website Login
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
                <div class="bg-white rounded-md shadow-sm p-5">
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

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 max-w-7xl mx-auto mt-6 mb-6">
                <!-- Current Balance -->
                <div class="bg-white p-4 rounded-md shadow-sm flex items-center gap-4">
                    <div class="bg-indigo-100 text-indigo-600 rounded-md p-2">
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
                    <div class="bg-emerald-100 text-emerald-600 rounded-md p-2">
                    <!-- Calendar Icon -->
                    <x-heroicon-o-calendar class="w-5 h-5 text-green-500" />
                    </div>
                    <div>
                    <p class="text-sm text-gray-500">Last Payment</p>
                    <p class="text-lg font-semibold text-gray-900">Jan 18, 2025</p>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 py-6 bg-white rounded-md shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Recent Orders</h2>
                    <a href="#" class="text-sm text-blue-600 hover:underline">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                        <th class="px-4 py-3">Order ID</th>
                        <th class="px-4 py-3">Product Name</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Payment Methods</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">ORD-2025-001</td>
                        <td class="px-4 py-3">Premium Widget Set</td>
                        <td class="px-4 py-3">$1,249.95</td>
                        <td class="px-4 py-3">Credit / Debit</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">paid</span>
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
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">paid</span>
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
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Acct.</span>
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
            <h3 class="mb-1 text-xl font-medium text-gray-800 dark:text-white/90">
                Orders
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Notification ipsum dolor sit amet consectetur. Non vitae facilisis urna
                tortor placerat egestas donec. Faucibus diam gravida enim elit lacus a.
                Tincidunt fermentum condimentum quis et a et tempus. Tristique urna nisi
                nulla elit sit libero scelerisque ante.
            </p>
        </div>

        <div x-show="activeTab === 'credit'">
            <h3 class="mb-1 text-xl font-medium text-gray-800 dark:text-white/90">
                Credit
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Analytics ipsum dolor sit amet consectetur. Non vitae facilisis urna
                tortor placerat egestas donec. Faucibus diam gravida enim elit lacus a.
                Tincidunt fermentum condimentum quis et a et tempus. Tristique urna nisi
                nulla elit sit libero scelerisque ante.
            </p>
        </div>

        <div x-show="activeTab === 'invoices'">
            <h3 class="mb-1 text-xl font-medium text-gray-800 dark:text-white/90">
                Invoices
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Customers ipsum dolor sit amet consectetur. Non vitae facilisis urna
                tortor placerat egestas donec. Faucibus diam gravida enim elit lacus a.
                Tincidunt fermentum condimentum quis et a et tempus. Tristique urna nisi
                nulla elit sit libero scelerisque ante.
            </p>
        </div>

        <div x-show="activeTab === 'account'">
            <h3 class="mb-1 text-xl font-medium text-gray-800 dark:text-white/90">
                Account
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Customers ipsum dolor sit amet consectetur. Non vitae facilisis urna
                tortor placerat egestas donec. Faucibus diam gravida enim elit lacus a.
                Tincidunt fermentum condimentum quis et a et tempus. Tristique urna nisi
                nulla elit sit libero scelerisque ante.
            </p>
        </div>
    </div>
</div>


    @endsection

@push('js')


