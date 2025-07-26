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
                        <p class="text-xl font-semibold text-gray-900"> $13,025.50</p>
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
                        <p class="text-xl font-semibold text-gray-900">{{ $customers->total() }} </p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 shadow-sm rounded-md p-4 mt-6 w-full">
                <div class="flex flex-wrap items-start gap-4 text-sm">
                    
                    <!-- Customer Name -->
                    <div class="flex flex-col billing-summary-w-16">
                        <label class="text-sm text-gray-500 mb-1">Customer Name</label>
                        <div class="relative">
                            <input type="text" name="b_customers_name" value="{{ request('customers_name') }}" placeholder="Search customers..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-full " />
                            <svg class="absolute w-4 h-4 text-gray-400 left-3 top-1/2 transform -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Company Name -->
                    <div class="flex flex-col billing-summary-w-16" >
                        <label class="text-sm text-gray-500 mb-1">Company Name</label>
                        <div class="relative">
                            <input name="b_company_name" value="{{ request('b_company_name') }}" type="text" placeholder="Search companies..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-md w-full " />
                            <svg class="absolute w-4 h-4 text-gray-400 left-3 top-1/2 transform -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="flex flex-col billing-summary-w-16" >
                        <label class="text-sm text-gray-500 mb-1">Phone</label>
                        <div class="relative">
                            <input type="text" name="b_search_phone" placeholder="(xxx) xxx-xxxx" value="{{ request('b_search_phone') }}" class="masked-phone pl-9 pr-3 py-2 border border-gray-300 rounded-md w-full " />
                            <svg class="absolute w-4 h-4 text-gray-400 left-3 top-1/2 transform -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <div class="flex flex-col billing-summary-w-13" >
                        <label class="text-sm text-gray-500 mb-1">Alerts</label>
                        <select name="alert_status"  class="py-2 px-3 border border-gray-300 rounded-md w-full ">
                            <option value="" selected>All Accounts</option>
                            <option value="warning">Alert Only</option>
                        </select>
                    </div>

                  
                    <!-- Credit Type -->
                        <div class="flex flex-col billing-summary-w-13">
                            <label class="text-sm text-gray-500 mb-1">Credit Type</label>
                            <div class="flex flex-col space-y-1">
                                <label class="inline-flex items-center text-gray-700">
                                    <input type="checkbox" name="credit_types[]" value="approved" class="mr-2" /> Credit - Approved
                                </label>
                                <label class="inline-flex items-center text-gray-700">
                                    <input type="checkbox" name="credit_types[]" value="none" class="mr-2" /> Credit - None
                                </label>
                                <p class="text-xs text-gray-500">Showing: All / Both</p>
                            </div>
                        </div>


                    <!-- Balance Sort -->
                    <div class="flex flex-col billing-summary-w-16">
                        <label class="text-sm text-gray-500 mb-1">Balance Sort</label>
                        <select id="balanceSortSelect" class="py-2 px-3 border border-gray-300 rounded-md w-full ">
                              <option value="">Select Sort</option>
                            <option value="desc" >Highest to Lowest</option>
                            <option  value="asc" >Lowest to Highest</option>
                        </select>
                    </div>

                </div>
            </div>

            <div id="customer-loader" class="hidden text-center py-4">
                <svg class="animate-spin h-6 w-6 text-brand-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <p class="text-sm text-gray-500 mt-2">Loading customers...</p>
            </div>

            <div id="customer-table-wrapper">

                @include('admin.crm.customers.partials._table_billingsummary', ['customers' => $customers])

            </div>

            
@push('js')

<script>

document.addEventListener("DOMContentLoaded", function () {
    let nameInput = document.querySelector('input[name="b_customers_name"]');
    let phoneInput = document.querySelector('input[name="b_search_phone"]');
    let company_name = document.querySelector('input[name="b_company_name"]');
    // let statusSelect = document.querySelector('select[name="tax_status"]');
const balanceSort = document.getElementById('balanceSortSelect').value;

    let alertStatusSelect = document.querySelector('select[name="alert_status"]');

    let creditCheckboxes = document.querySelectorAll('input[name="credit_types[]"]');


    let wrapper = document.querySelector('#customer-table-wrapper');

    let timeout = null;

    function fetchCustomers() {
        const name = nameInput.value;
        const phone = phoneInput.value;
        const company = company_name.value;
        // const tax_status = statusSelect.value;
            const alert_status = alertStatusSelect.value;

        // Get selected credit types
           



        const params = new URLSearchParams();
        if (name.length >= 3 || name.length === 0) params.append('search_name', name);
        if (phone.length >= 3 || phone.length === 0) params.append('search_phone', phone);
        if (company.length >= 3 || company.length === 0) params.append('search_company_name', company);
        if (alert_status.length > 0) params.append('alert_status', alert_status);
            creditCheckboxes.forEach(cb => {
                            if (cb.checked) {
                                params.append('credit_types[]', cb.value);
                            }
        });
        

        // if (tax_status !== 'All') params.append('tax_status', tax_status);

         // Show loader
        document.querySelector('#customer-loader').classList.remove('hidden');
        document.querySelector('#customer-table-wrapper').classList.add('hidden');

        fetch("{{ route('admin.crm.billingsummary.index') }}?" + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
       .then(response => response.json())
        .then(data => {
            document.querySelector('#customer-table-wrapper').innerHTML = data.html;
            document.querySelector('#customer-total-count').textContent = data.total;

            applyBalanceSort();
        })

        .catch(err => {
            wrapper.innerHTML = '<div class="text-red-500 p-4">Error loading customers.</div>';
            console.error(err);
        }) .finally(() => {
        // Hide loader
            document.querySelector('#customer-loader').classList.add('hidden');
                    document.querySelector('#customer-table-wrapper').classList.remove('hidden');

        });
        
    }

    // Delayed filters
    nameInput.addEventListener('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(fetchCustomers, 400);
    });

    phoneInput.addEventListener('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(fetchCustomers, 400);
    });

    company_name.addEventListener('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(fetchCustomers, 400);
    });

    alertStatusSelect.addEventListener('change', function () {
    fetchCustomers();
    });

    creditCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            fetchCustomers();
        });
    });



});
</script>
<script>
    function applyBalanceSort() {
        const table = document.getElementById('customerTable');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const balanceSortSelect = document.getElementById('balanceSortSelect');
        if (!balanceSortSelect) return;

        const originalRows = Array.from(tbody.querySelectorAll('tr')).map(row => row.cloneNode(true));

        balanceSortSelect.addEventListener('change', function () {
            const order = this.value;

            if (!order) {
                tbody.innerHTML = '';
                originalRows.forEach(row => tbody.appendChild(row.cloneNode(true)));
                return;
            }

            const rows = Array.from(tbody.querySelectorAll('tr'));
            const extractBalance = row => {
                const balanceCell = row.children[4];
                const text = balanceCell.textContent.replace(/[^\d.-]/g, '');
                return parseFloat(text) || 0;
            };

            rows.sort((a, b) => {
                const aVal = extractBalance(a);
                const bVal = extractBalance(b);
                return order === 'asc' ? aVal - bVal : bVal - aVal;
            });

            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        applyBalanceSort(); // Call on initial page load
    });
</script>


@endpush