@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <div class="pt-100 section-padding">
        <div class="rounded-xl dark:border-gray-800" x-data="{ activeTab: '{{ session('active_tab', 'dashboard') }}' }">
            <div class="container">

                @include('flash::message')
                @include('admin.partials.formErrors')

                <div class="border-b border-gray-200 dark:border-gray-800">
                    <nav
                        class="-mb-px flex space-x-2 overflow-x-auto [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 dark:[&::-webkit-scrollbar-thumb]:bg-gray-600 dark:[&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar]:h-1.5">
                        <button
                            class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                            x-bind:class="activeTab === 'dashboard' ?
                                ' text-brand-500 border-brand-500  dark:text-brand-400 dark:border-brand-400' :
                                'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                            x-on:click="activeTab = 'dashboard'" id="tab-dashboard">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4 mr-1">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                <polyline points="16 7 22 7 22 13"></polyline>
                            </svg>
                            </svg>
                            Dashboard
                        </button>

                        <button
                            class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                            x-bind:class="activeTab === 'orders' ?
                                ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' :
                                'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                            x-on:click="activeTab = 'orders'" id="tab-orders">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-file-text w-4 h-4 mr-1">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                <path d="M10 9H8"></path>
                                <path d="M16 13H8"></path>
                                <path d="M16 17H8"></path>
                            </svg>
                            Orders
                        </button>

                        <button
                            class="inline-flex items-center border-b-2 whitespace-nowrap px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                            x-bind:class="activeTab === 'credit' ? ' text-brand-500 border-brand-500   dark:text-brand-500' :
                                'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                            x-on:click="activeTab = 'credit'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4 mr-1">
                                <line x1="12" x2="12" y1="2" y2="22"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            Credit Account
                        </button>

                        <button
                            class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                            x-bind:class="activeTab === 'invoices' ? ' text-brand-500 border-brand-500   dark:text-brand-500' :
                                'bg-transparent text-gray-500 border-transparent  hover:text-gray-700  dark:text-gray-400 dark:hover:text-gray-200'"
                            x-on:click="activeTab = 'invoices'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4 mr-1">
                                <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                <line x1="2" x2="22" y1="10" y2="10"></line>
                            </svg>
                            Invoices
                        </button>

                        <button
                            class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                            x-bind:class="activeTab === 'account' ? ' text-brand-500 border-brand-500   dark:text-brand-500' :
                                'bg-transparent text-gray-500 border-transparent  hover:text-gray-700  dark:text-gray-400 dark:hover:text-gray-200'"
                            x-on:click="activeTab = 'account'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-settings w-4 h-4 mr-1">
                                <path
                                    d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z">
                                </path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            Account
                        </button>
                    </nav>
                </div>

                <div class=" dark:border-gray-800">
                    <div x-show="activeTab === 'dashboard'">

                        @include('front.customer.dashboard.partials._tab_dashboard')

                    </div>

                    <div x-show="activeTab === 'orders'">

                        @include('front.customer.dashboard.partials._tab_orders')

                    </div>

                    <div x-show="activeTab === 'credit'">

                        @include('front.customer.dashboard.partials._tab_credit')


                    </div>

                    <div x-show="activeTab === 'invoices'">

                        @include('front.customer.dashboard.partials._tab_invoices')


                    </div>

                    <div x-show="activeTab === 'account'">


                        @include('front.customer.dashboard.partials._tab_account')


                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Wrapper -->
    <div id="orderWrapper" style="display: none;"
        class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
        <div class="modal-scrollable w-full mx-auto">
            <div
                class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
                <div class="flex justify-between items-center px-6 pt-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-medium text-gray-900">Order Details</h2>
                    </div>
                    <button id="closeOrderModalBtn"
                        class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
                </div>
                <div class="px-6 overflow-y-auto">
                    <div class="mx-auto bg-white rounded-md text-sm text-gray-800">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 sm:gap-x-10">

                            <!-- Order Number -->
                            <div class="mt-3">
                                <p class="text-gray-500 mb-2">Order Number</p>
                                <p class="font-semibold">ORD-2025-001</p>
                            </div>

                            <!-- Order Date -->
                            <div class="mt-3">
                                <p class="text-gray-500 mb-2">Order Date</p>
                                <p>Jan 15, 2025</p>
                            </div>

                            <!-- Status -->
                            <div class="mt-3">
                                <p class="text-gray-500 mb-2">Status</p>
                                <span
                                    class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    delivered
                                </span>
                            </div>

                            <!-- Payment Method -->
                            <div class="mt-3">
                                <p class="text-gray-500 mb-2">Payment Method</p>
                                <p>Credit / Debit</p>
                            </div>

                            <!-- Total Amount -->
                            <div class="mt-3">
                                <p class="text-gray-500 mb-2">Total Amount</p>
                                <p class="font-semibold">$1,249.95</p>
                            </div>

                            <!-- Tracking Number -->
                            <div class="mt-3">
                                <p class="text-gray-500 mb-2">Tracking Number</p>
                                <p>TRK123456789</p>
                            </div>

                            <!-- Primary Product -->
                            <div class="sm:col-span-2 mt-3">
                                <p class="text-gray-500 mb-2">Primary Product</p>
                                <p class="font-medium text-gray-900">Premium Widget Set</p>
                                <p class="text-gray-600 text-sm mt-1">+5 additional items</p>
                            </div>

                        </div>
                    </div>
                    <!-- Footer -->
                    <div id="actionButtons" class="flex justify-end gap-2 py-4">
                        <button id="cancelOrderBtn"
                            class="px-4 py-2 border border-gray-300 rounded text-sm">Cancel</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalWrapper = document.getElementById('orderWrapper');
            const closeBtn = document.getElementById('closeOrderModalBtn');
            const cancelBtn = document.getElementById('cancelOrderBtn');
            const openButtons = document.querySelectorAll('.openOrderModalBtn');

            const closeModal = () => {
                modalWrapper.style.display = 'none';
            };

            openButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Get data from button
                    const orderNumber = btn.getAttribute('data-order');
                    const orderDate = btn.getAttribute('data-date');
                    const status = btn.getAttribute('data-status');
                    const paymentMethod = btn.getAttribute('data-method');
                    const totalAmount = btn.getAttribute('data-total');
                    const product = btn.getAttribute('data-product');

                    // Inject data into modal
                    document.querySelector('#orderWrapper .modal-scrollable').innerHTML = `
                    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
                        <div class="flex justify-between items-center px-6 pt-4">
                            <h2 class="text-lg font-medium text-gray-900">Order Details</h2>
                            <button id="closeOrderModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
                        </div>
                        <div class="px-6 overflow-y-auto">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 sm:gap-x-10">
                                <div><p class="text-gray-500 mb-2">Order Number</p><p class="font-semibold">${orderNumber}</p></div>
                                <div><p class="text-gray-500 mb-2">Order Date</p><p>${orderDate}</p></div>
                                <div><p class="text-gray-500 mb-2">Status</p>
                                ${status}

                                </div>
                                <div><p class="text-gray-500 mb-2">Payment Method</p><p>${paymentMethod}</p></div>
                                <div><p class="text-gray-500 mb-2">Total Amount</p><p class="font-semibold">${totalAmount}</p></div>
                                <div class="sm:col-span-2"><p class="text-gray-500 mb-2">Primary Product</p><p class="font-medium text-gray-900">${product}</p></div>
                            </div>
                            <div class="flex justify-end gap-2 py-4">
                                <button id="cancelOrderBtn" class="px-4 py-2 border border-gray-300 rounded text-sm">Cancel</button>
                            </div>
                        </div>
                    </div>
                `;

                    // Re-bind close buttons inside the injected modal
                    document.getElementById('closeOrderModalBtn').addEventListener('click',
                        closeModal);
                    document.getElementById('cancelOrderBtn').addEventListener('click', closeModal);

                    modalWrapper.style.display = 'flex';
                });
            });

            modalWrapper.addEventListener('click', (e) => {
                if (e.target === modalWrapper) {
                    closeModal();
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
            const allowedExtensions = ["pdf", "jpg", "jpeg", "png"];
            const fileExtension = file.name.split(".").pop().toLowerCase();

            // Max file size (10 MB)
            const maxSize = 10 * 1024 * 1024; // 10 MB in bytes

            // Validation: format
            if (!allowedExtensions.includes(fileExtension)) {

                 notyf.error("Invalid file format. Allowed: PDF, JPG, JPEG, PNG.");

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
            tab.addEventListener('click', function() {
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
        document.addEventListener("DOMContentLoaded", function() {
            const viewAllLink = document.getElementById("viewAllOrdersLink");
            const ordersTabBtn = document.getElementById("tab-orders");

            viewAllLink?.addEventListener("click", function(e) {
                e.preventDefault();
                if (ordersTabBtn) {
                    ordersTabBtn.click(); // Simulate tab button click
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const creditUsed = {{ $customer->available_credit_balance ?? 0 }};
            const creditLimit = {{ $customer->credit_limit ?? 0 }};
            const available = {{ \App\Helpers\CustomHelper::getAvailableCredit($customer) }};
            const percentUsed = (creditUsed / creditLimit) * 100;

            document.getElementById("usedAmount").textContent =
                `{{ config('app.currency.code') }}${creditUsed.toLocaleString()}`;
            document.getElementById("limitAmount").textContent =
                `{{ config('app.currency.code') }}${creditLimit.toLocaleString()}`;
            document.getElementById("availableAmount").textContent =
                `{{ config('app.currency.code') }}${available.toLocaleString()}`;

            document.getElementById("progressBar").style.width = `${percentUsed}%`;
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                    option.textContent = `All  (${rows.length})`;
                } else if (statusCounts[value] !== undefined) {
                    option.textContent =
                        `${value.charAt(0).toUpperCase() + value.slice(1)} (${statusCounts[value]})`;
                }
            });

            // Filter rows on change
            filter.addEventListener('change', function() {
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
@endpush
