@extends('admin.layouts.app')

@section('title', 'Customers')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Customers</h3>
        <a href="{{ route('admin.crm.customers.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Add Customer
        </a>
    </div>

    
    <div class="flex flex-wrap items-end gap-4 w-full mb-6">

        <!-- Search by name -->
        <div class="relative w-full sm:w-48">
            <input type="text" name="search_name" placeholder="Customer name" value="{{ request('search_name') }}"
                class="w-full h-10 rounded-md border border-gray-300 bg-white pl-3 pr-10 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
            </div>
        </div>

         <!-- Search by company -->
        <div class="relative w-full sm:w-48">
            <input type="text" name="search_company_name" placeholder="Customer company" value="{{ request('search_company_name') }}"
                class="w-full h-10 rounded-md border border-gray-300 bg-white pl-3 pr-10 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
            </div>
        </div>

        <!-- Search by phone -->
        <div class="relative w-full sm:w-48">
            <input type="text" name="search_phone" placeholder="(xxx) xxx-xxxx" value="{{ request('search_phone') }}"
                class="masked-phone w-full h-10 rounded-md border border-gray-300 bg-white pl-3 pr-10 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 
                            19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.63A2 2 0 0 1 
                            4.11 2h3a2 2 0 0 1 2 1.72 12.44 12.44 0 0 0 .7 2.81 2 2 0 0 1-.45 
                            2.11L8 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 
                            12.44 12.44 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
            </div>
        </div>

        <div class="w-full sm:w-48">
            <select
                class="w-full h-10 border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring focus:border-blue-500 border-gray-300"
                name="tax_status"
                id="tax_status">
                <option value="All" {{ request('status') === 'All' ? 'selected' : '' }}>All</option>
                <option value="Exempt" {{ request('status') === 'Exempt' ? 'selected' : '' }}>Exempt</option>
                <option value="Taxable" {{ request('status') === 'Taxable' ? 'selected' : '' }}>Taxable</option>
            </select>
        </div>

    
       
        

        <!-- Total count -->
        <div class="w-full sm:w-auto h-10 px-4 py-2 rounded-md border border-gray-300 text-sm text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 text-center sm:text-left">
      Total: <span id="customer-total-count">{{ $customers->total() }}</span>
        </div>

        <div class="w-full sm:w-auto">
            <button type="button" id="delete-selected-btn"
                class="flex items-center h-10 gap-2 bg-gray-300 text-gray-500 cursor-not-allowed px-4 py-2 rounded-md text-sm font-medium transition w-full sm:w-auto"
                disabled>
                <x-heroicon-o-trash class="w-4 h-4" />
                Delete Selected (<span id="delete-selected-count">0</span>)
            </button>
        </div>
    </div>

    


    <div id="customer-table-wrapper">
   
        @include('admin.crm.customers.partials._table', ['customers' => $customers])
        
    </div>
@endsection

@push('js')
<script>

document.addEventListener("DOMContentLoaded", function () {
    let nameInput = document.querySelector('input[name="search_name"]');
    let phoneInput = document.querySelector('input[name="search_phone"]');
    let company_name = document.querySelector('input[name="search_company_name"]');
    let statusSelect = document.querySelector('select[name="tax_status"]');
    let wrapper = document.querySelector('#customer-table-wrapper');
    
    let loader = document.querySelector('#customer-loader');
    let timeout = null;

    function fetchCustomers() {
        const name = nameInput.value;
        const phone = phoneInput.value;
        const company = company_name.value;
        const tax_status = statusSelect.value;

        const params = new URLSearchParams();
        if (name.length >= 3 || name.length === 0) params.append('search_name', name);
        if (phone.length >= 3 || phone.length === 0) params.append('search_phone', phone);
        if (company.length >= 3 || company.length === 0) params.append('search_company_name', company);
        if (tax_status !== 'All') params.append('tax_status', tax_status);

         // Show loader
        // document.querySelector('#customer-loader').classList.remove('hidden');
    // document.querySelector('#customer-table-wrapper').classList.add('hidden');


      loader.classList.remove('hidden');
                wrapper.classList.add('opacity-50', 'pointer-events-none');


        fetch("{{ route('admin.crm.customers.index') }}?" + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
       .then(response => response.json())
        .then(data => {
            document.querySelector('#customer-table-wrapper').innerHTML = data.html;
            document.querySelector('#customer-total-count').textContent = data.total;
        })

        .catch(err => {
            wrapper.innerHTML = '<div class="text-red-500 p-4">Error loading customers.</div>';
            console.error(err);
        }) .finally(() => {
        // Hide loader
            loader.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');

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

    // Immediate filter for tax_status
    statusSelect.addEventListener('change', function () {
        fetchCustomers(); // no timeout
    });
});
</script>

@endpush
