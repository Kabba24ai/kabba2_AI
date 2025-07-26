@extends('admin.layouts.app')

@section('title', 'View Customer')

@push('css')
@endpush

@section('content')
<!-- <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> -->
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
            <!-- <a href="{{ route('admin.crm.customers.edit', $customer->unique_id) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
                <x-heroicon-o-pencil-square class="w-5 h-5 mr-2" />
                Edit Customer
            </a> -->

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

                @include('flash::message')
                @include('admin.partials.formErrors')

<div class="rounded-xl dark:border-gray-800"  x-data="{ activeTab: '{{ session('active_tab', 'dashboard') }}' }" >
    <div class="border-b border-gray-200 dark:border-gray-800">
        <nav
        class="-mb-px flex space-x-2 overflow-x-auto [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 dark:[&::-webkit-scrollbar-thumb]:bg-gray-600 dark:[&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar]:h-1.5"
        >
            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'dashboard' ? ' text-brand-500 border-brand-500  dark:text-brand-400 dark:border-brand-400' : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'dashboard'" id="tab-dashboard" >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4 mr-1"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                </svg>
                Dashboard
            </button>

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'orders' ? ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'orders'" id="tab-orders">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-4 h-4 mr-1"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
                Orders
            </button>

            <button
                class="whitespace-nowrap inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'credit' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'credit'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-4 h-4"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                Credit Account
            </button>

            <button
                class="inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'invoices' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700  dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'invoices'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4 mr-1"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line></svg>
                Invoices
            </button>

            <button
                class="inline-flex items-center border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'account' ? ' text-brand-500 border-brand-500   dark:text-brand-500' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700  dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'account'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-4 h-4 mr-1"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                Account
            </button>

            <button
                class="whitespace-nowrap inline-flex items-center  border-b-2 px-2.5 py-2 text-sm font-medium transition-colors duration-200 ease-in-out"
                x-bind:class="activeTab === 'billing' ? ' text-brand-500 border-brand-500  dark:border-brand-400  dark:text-brand-400' : 'bg-transparent text-gray-500 border-transparent  hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="activeTab = 'billing'" id="tab-billing">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-4 h-4 mr-1"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
                Billing Summary
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
        <div x-show="activeTab === 'billing'">

             @include('admin.crm.customers.partials._tab_billing')
        </div>
    </div>
</div>



@endsection

@push('js')


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
document.addEventListener('DOMContentLoaded', function () {
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
    saveBtn.addEventListener('click', function () {
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
    form.addEventListener('submit', function (e) {
        if (!allowSubmit) {
            e.preventDefault(); // Block submission if not allowed
        }
        allowSubmit = false; // Reset after every attempt
    });

    // Define custom async validator outside of any event
    window.Parsley.addAsyncValidator('customemailcheck', function (xhr) {
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
      fileNameDisplay.textContent = file.name;
      fileActions.style.display = "flex";
      uploadUI.style.display = "none";

      // Show file in new tab (temporary blob link)
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



<script>
  document.addEventListener('DOMContentLoaded', function () {
      const creditUsed = {{ $customer->total_account_order_amount ?? 0 }};
      const creditLimit = {{ $customer->credit_limit ?? 0 }} ;
      const available = creditLimit - creditUsed;
      const percentUsed = (creditUsed / creditLimit) * 100;

      document.getElementById("usedAmount").textContent = `{{ config('app.currency.code') }}${creditUsed.toLocaleString()}`;
      document.getElementById("limitAmount").textContent = `{{ config('app.currency.code') }}${creditLimit.toLocaleString()}`;
      document.getElementById("availableAmount").textContent = `{{ config('app.currency.code') }}${available.toLocaleString()}`;

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
        option.textContent = `All (${rows.length})`;
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

            // Show modal
            modal.style.display = 'flex';

            // Fill shared form fields
            if (modal.querySelector('input[name="amount"]')) {
                modal.querySelector('input[name="amount"]').value = button.dataset.amount || '';
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
            const form = modal.querySelector('form');
            if (form) {
                form.action = button.dataset.action || '#';
                const methodInput = modal.querySelector('input[name="_method"]');
                if (methodInput) {
                    methodInput.value = 'PUT';
                } else {
                    const hiddenMethod = document.createElement('input');
                    hiddenMethod.setAttribute('type', 'hidden');
                    hiddenMethod.setAttribute('name', '_method');
                    hiddenMethod.setAttribute('value', 'PUT');
                    form.appendChild(hiddenMethod);
                }

                //  Create or update 'type' field dynamically
                let typeInput = form.querySelector('input[name="type"]');
                if (!typeInput) {
                    typeInput = document.createElement('input');
                    typeInput.setAttribute('type', 'hidden');
                    typeInput.setAttribute('name', 'type');
                    form.appendChild(typeInput);
                }
                typeInput.value = type;

            }
        });
    });
});
</script>

<!-- edit  -->



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

    modalWrapper.addEventListener('click', e => {
        if (e.target === modalWrapper) closeModal();
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
    const transactionId = document.getElementById('noteTransactionId').value;


    fetch(`{{ route('admin.crm.customers.customeraccount.update_note') }}`, {
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

