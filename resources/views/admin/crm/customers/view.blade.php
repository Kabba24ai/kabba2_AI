@extends('admin.layouts.app')

@section('title', 'View Customer')

@push('css')
@endpush

@section('content')

<div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Left Section -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            @php
            $previousUrl = url()->previous();
            $previousRouteName = null;

            try {
            $request = Illuminate\Http\Request::create($previousUrl);
            $matchedRoute = app('router')->getRoutes()->match($request);
            $previousRouteName = $matchedRoute->getName();
            } catch (Exception $e) {
            // No matching route
            $previousRouteName = null;
            }
            @endphp

            @if ($previousRouteName === 'admin.crm.customers.index')
            <!-- Back to Customers -->
            <a href="{{ route('admin.crm.customers.index') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-1" />
                <span class="text-sm font-medium">Back to Customers</span>
            </a>
            @elseif ($previousRouteName === 'admin.crm.billing-summary.index')
            <!-- Back to Billing Summary -->
            <a href="{{ route('admin.crm.billing-summary.index') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-1" />
                <span class="text-sm font-medium">Back to Billing Summary</span>
            </a>

            @else
            <!-- Back to Customers -->
            <a href="{{ route('admin.crm.customers.index') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-1" />
                <span class="text-sm font-medium">Back to Customers</span>
            </a>


            @endif

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

            <!-- Delete Form -->
            <form action="{{ route('admin.crm.customers.delete', $customer->unique_id) }}"
                method="POST"
                class="inline delete-customer-form"
                data-customer-name="{{ $customer->full_name }}">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition">
                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                    Delete
                </button>
            </form>



        </div>
    </div>
</div>

@include('flash::message')
@include('admin.partials.formErrors')

<div class="rounded-xl dark:border-gray-800" x-data="{ activeTab: '{{ session('active_tab', 'dashboard') }}' }">
    <div class="border-b border-gray-200 dark:border-gray-800">
        <nav
            class="-mb-px flex space-x-2 overflow-x-auto [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 dark:[&::-webkit-scrollbar-thumb]:bg-gray-600 dark:[&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar]:h-1.5">
            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'dashboard' ? ' text-brand-500 border-brand-500  dark:text-brand-400 dark:border-brand-400' : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'dashboard'" id="tab-dashboard">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4 mr-1">
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                    <polyline points="16 7 22 7 22 13"></polyline>
                </svg>
                </svg>
                Dashboard
            </button>

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'orders' ? ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'orders'" id="tab-orders">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-4 h-4 mr-1">
                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                    <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                    <path d="M10 9H8"></path>
                    <path d="M16 13H8"></path>
                    <path d="M16 17H8"></path>
                </svg>
                Orders
            </button>

            <button
                class="whitespace-nowrap inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'credit' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'credit'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4">
                    <line x1="12" x2="12" y1="2" y2="22"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Credit Account
            </button>

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'invoices' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700  dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'invoices'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4 mr-1">
                    <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                    <line x1="2" x2="22" y1="10" y2="10"></line>
                </svg>
                Invoices
            </button>

            <button
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'account' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700  dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'account'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-4 h-4 mr-1">
                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                Account
            </button>



        </nav>
    </div>

    <div class="dark:border-gray-800">
        <div x-show="activeTab === 'dashboard'">
            @include('admin.crm.customers.partials._tab_dashboard')

        </div>

        <div x-show="activeTab === 'orders'">

            @include('admin.crm.customers.partials._tab_orders')

        </div>

        <div x-show="activeTab === 'credit'">

            @include('admin.crm.customers.partials._tab_credit')

        </div>

        <div x-show="activeTab === 'invoices'">

            @include('admin.crm.customers.partials._tab_invoices')

        </div>

        <div x-show="activeTab === 'account'">

            @include('admin.crm.customers.partials._tab_account')
        </div>

    </div>
</div>



@endsection

@push('js')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-customer-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // stop immediate form submission

                let customerName = form.getAttribute('data-customer-name') || 'this customer';

                window.showConfirm(
                    `Delete ${customerName}? This action cannot be undone!`,
                    'Delete Customer'
                ).then((result) => {
                    if (result.isConfirmed) {
                        form.submit(); // submit the original form
                    }
                });
            });
        });
    });
</script>


<script>
    let sortDirections = {};

    function resetIcons() {
        for (let i = 0; i <= 4; i++) {
            const icon = document.getElementById('icon-' + i);
            if (icon) icon.innerHTML = '';
        }
    }

    function sortTable(colIndex, type) {
        const table = document.getElementById("customerTable");
        const tbody = table.tBodies[0];
        const rows = Array.from(tbody.rows);

        sortDirections[colIndex] = !sortDirections[colIndex];
        resetIcons();

        rows.sort((a, b) => {
            let valA, valB;

            if (type === "number") {
                valA = parseFloat(a.cells[colIndex].innerText.replace(/[^0-9.-]+/g, '')) || 0;
                valB = parseFloat(b.cells[colIndex].innerText.replace(/[^0-9.-]+/g, '')) || 0;
            } else if (type === "date") {
                valA = Date.parse(a.cells[colIndex].getAttribute("data-date")) || 0;
                valB = Date.parse(b.cells[colIndex].getAttribute("data-date")) || 0;
            } else if (type === "days") {
                valA = parseInt(a.cells[colIndex].innerText.replace(/\D/g, '')) || 0;
                valB = parseInt(b.cells[colIndex].innerText.replace(/\D/g, '')) || 0;
            } else {
                valA = a.cells[colIndex].innerText.trim().toLowerCase();
                valB = b.cells[colIndex].innerText.trim().toLowerCase();
                return sortDirections[colIndex] ? valA.localeCompare(valB) : valB.localeCompare(valA);
            }

            return sortDirections[colIndex] ? valA - valB : valB - valA;
        });

        rows.forEach(row => tbody.appendChild(row));

        const sortIcon = sortDirections[colIndex] ? '˄' : '˅';
        const iconElement = document.getElementById('icon-' + colIndex);
        if (iconElement) iconElement.innerHTML = sortIcon;
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('customerForm');
        const saveBtn = document.querySelector('.saveBtn');
        const addressListInput = document.getElementById('alladdresslist');

        let allowSubmit = false;

        // Function to collect address data
        function collectAddresses() {
            const addressBlocks = document.querySelectorAll('.address-block');
            const data = [];



            addressBlocks.forEach((block, index) => {
                data.push({
                    first_name: block.querySelector('.first_name')?.value || null,
                    last_name: block.querySelector('.last_name')?.value || null,
                    phone: block.querySelector('.phone')?.value || null,
                    address_id: block.querySelector('.address_id')?.value || null,
                    type: block.querySelector('.type')?.value || null,
                    address: block.querySelector('.address')?.value || '',
                    city: block.querySelector('.city')?.value || '',
                    zip_code: block.querySelector('.zip_code')?.value || '',
                    state_id: block.querySelector('.state_id')?.value || '',
                });
            });

            addressListInput.value = JSON.stringify(data);
        }

        // Triggered only when save button is clicked
        saveBtn.addEventListener('click', function() {
            allowSubmit = true;

            // Collect address list first
            collectAddresses();

            // Validate form before allowing submission
            if (!form.parsley().isValid()) {
                form.parsley().validate();
                allowSubmit = false; // Block if validation fails
            }
        });

        // Intercept form submission
        form.addEventListener('submit', function(e) {
            if (!allowSubmit) {
                e.preventDefault(); // Block submission if not allowed
            }
            allowSubmit = false; // Reset after every attempt
        });

        // Define custom async validator outside of any event
        window.Parsley.addAsyncValidator('customemailcheck', function(xhr) {
            const response = xhr.responseJSON || {};
            return response.valid === true;
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
        const fileInput = document.getElementById("tax_document");
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const availableCredit = {
            {
                \
                App\ Helpers\ CustomHelper::getAvailableCredit($customer)
            }
        };
        const creditLimit = {
            {
                $customer - > credit_limit ?? 0
            }
        };
        const usedCredit = creditLimit - availableCredit;

        const percentUsed = creditLimit > 0 ? Math.min((usedCredit / creditLimit) * 100, 100) : 0;

        document.getElementById("usedAmount").textContent = `{{ config('app.currency.code') }}${usedCredit.toLocaleString()}`;
        document.getElementById("limitAmount").textContent = `{{ config('app.currency.code') }}${creditLimit.toLocaleString()}`;
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
                option.textContent = `All (${rows.length})`;
            } else if (statusCounts[value] !== undefined) {
                option.textContent = `${value.charAt(0).toUpperCase() + value.slice(1)} (${statusCounts[value]})`;
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

<!-- edit  -->

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const typeToModalId = {
            payment: 'templatesModalWrapper',
            refund: 'refundModalWrapper',
            discount: 'discountModalWrapper',
            charge: 'chargeModalWrapper'
        };

        document.querySelectorAll('.openEditPaymentModalBtn').forEach(button => {
            button.addEventListener('click', () => {
                const type = button.dataset.type;
                const modalId = typeToModalId[type];
                const modal = document.getElementById(modalId);
                if (!modal) return;

                const form = modal.querySelector('form');

                //  Reset the form when opening (so "Add" always starts fresh)
                if (form) {
                    form.reset();

                    // also clear hidden fields manually if needed
                    form.querySelectorAll('input[type="hidden"]').forEach(h => {
                        if (h.name !== "_token" && h.name !== "_method") {
                            h.value = "";
                        }
                    });
                }

                // Show modal
                modal.style.display = 'flex';

                const amountInput = modal.querySelector('input[name="amount"]');
                const paymentTypeSelect = modal.querySelector('select[name="payment_type"]');
                const cardOptionSelect = modal.querySelector('#cardOption');
                const cardOnFileDropdown = modal.querySelector('#cardOnFileDropdown');
                const existingCardSelect = modal.querySelector('select[name="existing_card_id"]');


                // At the start of modal open (before filling data)
                if (amountInput) amountInput.readOnly = false;
                if (paymentTypeSelect) paymentTypeSelect.disabled = false;

                if (cardOptionSelect) {
                    cardOptionSelect.disabled = false;
                    cardOptionSelect.value = ""; // reset to default
                }

                if (cardOnFileDropdown) cardOnFileDropdown.classList.add('hidden');

                if (existingCardSelect) {
                    existingCardSelect.disabled = false;
                    existingCardSelect.style.display = "";

                    // Remove masked <p> if previously added
                    const nextSibling = existingCardSelect.nextElementSibling;
                    if (nextSibling && nextSibling.tagName === "P" && nextSibling.textContent.includes("****")) {
                        nextSibling.remove();
                    }
                }


                // If it’s EDIT → fill with values
                if (button.dataset.action) {
                    if (modal.querySelector('input[name="amount"]')) {

                        const value = button.dataset.amount || '0.00';

                        // Set input value
                        amountInput.value = parseFloat(value).toFixed(2);

                        //  Sync digits for your custom input handler
                        amountInput.digits = value.replace(/\D/g, '');

                        // Trigger input event to update Parsley/other listeners
                        const event = new Event('input', {
                            bubbles: true
                        });
                        amountInput.dispatchEvent(event);
                    }

                    if (modal.querySelector('select[name="payment_type"]')) {
                        modal.querySelector('select[name="payment_type"]').value = button.dataset.payment_type || '';
                    }
                    if (modal.querySelector('select[name="reason"]')) {
                        modal.querySelector('select[name="reason"]').value = button.dataset.reason || '';
                    }
                    if (modal.querySelector('select[name="responsible_person"]')) {
                        modal.querySelector('select[name="responsible_person"]').value = button.dataset.responsible_person || '';
                    }
                    if (modal.querySelector('textarea[name="notes"]')) {
                        modal.querySelector('textarea[name="notes"]').value = button.dataset.notes || '';
                    }

                    // Set sales_tax radio
                    if (button.dataset.sales_tax_type) {
                        const salesTaxInputs = modal.querySelectorAll('input[name="sales_tax"]');
                        salesTaxInputs.forEach(input => {
                            input.checked = input.value === button.dataset.sales_tax_type;
                        });
                    }

                    // Update form action/method
                    if (form) {
                        form.action = button.dataset.action || '#';
                        let methodInput = form.querySelector('input[name="_method"]');
                        if (!methodInput) {
                            methodInput = document.createElement('input');
                            methodInput.setAttribute('type', 'hidden');
                            methodInput.setAttribute('name', '_method');
                            form.appendChild(methodInput);
                        }
                        methodInput.value = 'PUT';

                        // Update hidden type input
                        let typeInput = form.querySelector('input[name="type"]');
                        if (!typeInput) {
                            typeInput = document.createElement('input');
                            typeInput.setAttribute('type', 'hidden');
                            typeInput.setAttribute('name', 'type');
                            form.appendChild(typeInput);
                        }
                        typeInput.value = type;
                    }
                    // SPECIAL CASE: lock CreditCard payments
                    if (type === "payment") {
                        if (button.dataset.payment_type === "CreditCard" && button.dataset.card_id) {
                            // Lock amount + payment type
                            if (amountInput) amountInput.readOnly = true;
                            if (paymentTypeSelect) paymentTypeSelect.disabled = true;

                            // Force Card Option = CardOnFile
                            if (cardOptionSelect) {
                                cardOptionSelect.value = "CardOnFile";
                                cardOptionSelect.disabled = true;
                            }

                            // Show card-on-file dropdown
                            if (cardOnFileDropdown) cardOnFileDropdown.classList.remove('hidden');

                            if (existingCardSelect) {
                                existingCardSelect.value = button.dataset.card_id;
                                existingCardSelect.disabled = true;
                                existingCardSelect.style.display = "";
                            }
                        } else if (button.dataset.payment_type !== "CreditCard") {
                            // Remove/hide CreditCard option so user cannot switch to it
                            if (paymentTypeSelect) {
                                const creditCardOption = paymentTypeSelect.querySelector('option[value="CreditCard"]');
                                if (creditCardOption) {
                                    creditCardOption.hidden = true; // hide it

                                    // creditCardOption.disabled = true; // disables but keeps it visible
                                    //    creditCardOption.remove();    // completely removes it
                                }
                            }
                        }
                    }


                } else {
                    //  If ADD → set default form action/method
                    if (form) {
                        form.action = form.dataset.createAction || '#';
                        let methodInput = form.querySelector('input[name="_method"]');
                        if (methodInput) methodInput.remove();
                    }
                }
            });
        });
    });
</script>

<!-- edit  -->

<script>
    document.addEventListener('DOMContentLoaded', () => {

        //  reset and re-sync form fields inside a modal
        function resetForm(modalWrapper) {
            const form = modalWrapper.querySelector('form');

            const amountInput2 = document.querySelector('input[name="amount"]');

            const paymentTypeSelect2 = document.querySelector('select[name="payment_type"]');
            const cardOptionSelect2 = document.querySelector('#cardOption');
            const cardOnFileDropdown2 = document.querySelector('#cardOnFileDropdown');
            const existingCardSelect2 = document.querySelector('select[name="existing_card_id"]');

            // Re-enable CreditCard option if it exists
            const paymentType2 = modalWrapper.querySelector('select[name="payment_type"]');
            if (paymentType2) {
                const creditCardOption = paymentType2.querySelector('option[value="CreditCard"]');
                if (creditCardOption) {
                    creditCardOption.hidden = false;
                }
            }



            // At the start of modal open (before filling data)
            if (amountInput2) amountInput2.readOnly = false;
            if (paymentTypeSelect2) paymentTypeSelect2.disabled = false;

            if (cardOptionSelect2) {
                cardOptionSelect2.disabled = false;
                cardOptionSelect2.value = ""; // reset to default
            }

            if (cardOnFileDropdown2) cardOnFileDropdown2.classList.add('hidden');

            if (existingCardSelect2) {
                existingCardSelect2.disabled = false;
                existingCardSelect2.style.display = "";

                // Remove masked <p> if previously added
                const nextSibling2 = existingCardSelect2.nextElementSibling;
                if (nextSibling2 && nextSibling2.tagName === "P" && nextSibling2.textContent.includes("****")) {
                    nextSibling2.remove();
                }
            }


            if (form) form.reset(); // clear all normal inputs




            // Reset custom digit inputs properly
            const digitInputs = modalWrapper.querySelectorAll("input[data-digit-input='true']");
            digitInputs.forEach(input => {
                // Force display to 0.00
                input.value = "0.00";

                // Also reset internal digits variable if exists
                if (input.hasOwnProperty('digits')) input.digits = "";

                // Trigger input event so Parsley updates validation
                const event = new Event('input', {
                    bubbles: true
                });
                input.dispatchEvent(event);
            });

            // hide optional sections back to defaults
            const creditCardOptions = modalWrapper.querySelector('#creditCardOptions');
            const newCardFields = modalWrapper.querySelector('#newCardFields');
            const cardOnFileDropdown = modalWrapper.querySelector('#cardOnFileDropdown');
            [creditCardOptions, newCardFields, cardOnFileDropdown].forEach(el => {
                if (el) el.classList.add('hidden');
            });

            // Reset payment type and card option
            const paymentType = modalWrapper.querySelector('#payment_type');
            if (paymentType) paymentType.value = '';
            const cardOption = modalWrapper.querySelector('#cardOption');
            if (cardOption) cardOption.value = 'NewCard';
        }

        // Universal modal handler
        function setupModal(openBtnId, modalWrapperId, closeBtnId, cancelBtnId) {
            const modalWrapper = document.getElementById(modalWrapperId);
            const openBtn = document.getElementById(openBtnId);
            const closeBtn = document.getElementById(closeBtnId);
            const cancelBtn = document.getElementById(cancelBtnId);
            if (!modalWrapper || !openBtn || !closeBtn || !cancelBtn) return;

            // Open
            openBtn.addEventListener('click', () => {
                resetForm(modalWrapper); // Reset everything on open
                modalWrapper.style.display = 'flex';
            });

            // Close
            const closeModal = () => modalWrapper.style.display = 'none';
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            // modalWrapper.addEventListener('click', (e) => {
            //     if (e.target === modalWrapper) closeModal();
            // });
        }

        // Setup all modals
        setupModal('openTemplatesModal', 'templatesModalWrapper', 'closeModalBtn', 'canceltempBtn');
        setupModal('openRefundModal', 'refundModalWrapper', 'closeRefundModalBtn', 'cancelRefundBtn');
        setupModal('openDiscountModal', 'discountModalWrapper', 'closeDiscountModalBtn', 'cancelDiscountBtn');
        setupModal('openChargeModal', 'chargeModalWrapper', 'closeChargeModalBtn', 'cancelChargeBtn');
    });
</script>




<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('noteModalWrapper');
        const closeBtn = document.getElementById('closeNoteModalBtn');
        const cancelBtn = document.getElementById('cancelNoteBtn');
        const noteContainer = document.getElementById('noteContainer');

        const transactionIdInput = document.getElementById('noteTransactionId');


        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        // Add listener to each note button
        document.querySelectorAll('.openNoteModalBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                const note = btn.getAttribute('data-note') || '—';
                const id = btn.getAttribute('data-id') || '';
                const date = btn.getAttribute('data-date') || '';
                const amount = parseFloat(btn.getAttribute('data-amount')).toFixed(2);

                transactionIdInput.value = id;

                noteContainer.innerText = note;
                modalWrapper.querySelector('[data-note-field="id"]').innerText = id;
                modalWrapper.querySelector('[data-note-field="date"]').innerText = date;
                modalWrapper.querySelector('[data-note-field="amount"]').innerText = `$${amount}`;

                modalWrapper.style.display = 'flex';
            });
        });

        closeBtn.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);

        // modalWrapper.addEventListener('click', e => {
        //     if (e.target === modalWrapper) closeModal();
        // });
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
        const transactionId = document.getElementById('noteTransactionId').value;



        fetch(`{{ route('admin.crm.customers.customer-account.update_note') }}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    id: transactionId,
                    note: newNote
                }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    notyf.success(data.message || "Note updated successfully.");
                    document.getElementById('noteContainer').innerText = newNote;

                    // Update the table cell for this transaction row
                    const noteCell = document.querySelector(`.transaction-note-cell-${transactionId}`);


                    if (noteCell) {
                        noteCell.textContent = newNote || 'N/A';
                    }

                    // Update the button's data-note so next time modal opens it has fresh value
                    const noteBtn = document.querySelector(`.openNoteModalBtn[data-id="${transactionId}"]`);
                    if (noteBtn) {
                        noteBtn.setAttribute("data-note", newNote);
                    }


                    // also update the button's data-* attributes so the next time modal opens, it shows fresh values
                    let editBtn = document.querySelector(`.openEditPaymentModalBtn[data-id="${data.id}"]`);
                    if (editBtn) {
                        editBtn.dataset.notes = newNote ?? "";
                    }


                    restoreButtons();
                } else {
                    notyf.error(data.message || "Failed to update note.");
                }
            })
            .catch(error => {
                console.error(error);
            });
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


@endpush