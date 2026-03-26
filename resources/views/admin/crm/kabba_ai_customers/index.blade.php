@extends('admin.layouts.app')

@section('title', 'kabba.ai Customers')

@push('css')
@endpush

@section('content')
    @include('flash::message')

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-semibold flex items-center gap-2">
            <x-heroicon-o-user-group class="w-6 h-6 text-blue-600" />
            kabba.ai Customers
        </h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.crm.kabba-ai-customers.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium text-md flex items-center gap-2">
                <x-heroicon-o-plus class="w-5 h-5" />
                Create Customer
            </a>
            <a href="{{ route('admin.crm.kabba-ai-customers.index') }}"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium text-md flex items-center gap-2">
                <x-heroicon-o-arrow-path class="w-5 h-5" />
                Reload
            </a>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-4 sm:space-y-0 mb-6">
        <div class="flex flex-wrap items-end gap-4 w-full">
            <div class="w-full sm:w-auto">
                <button type="button" id="clear-filters"
                    class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    Clear
                </button>
            </div>

            <div class="w-full sm:w-72">
                <div class="relative bg-white">
                    <input type="text" id="search" name="search" placeholder="Name, email, phone, business, unique ID"
                        value="{{ request('search') }}"
                        class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass
                        class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div
                class="w-full sm:w-auto px-4 py-3 rounded-md border border-gray-300 text-sm text-gray-900 shadow-sm text-center sm:text-left">
                Total: <span id="submission-total-count">0</span>
            </div>
        </div>
    </div>

    <div id="submission-table-wrapper" aria-live="polite">
        @include('admin.crm.kabba_ai_customers.partials._table', ['submissions' => []])
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            const wrapper = document.querySelector('#submission-table-wrapper');
            const totalCount = document.querySelector('#submission-total-count');
            let timeout = null;

            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location
                    .search)
                .get('per_page') || null;
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;

            const screenKey = "kabba_ai_customers_filters";
            const fieldMap = {
                'search': searchInput,
            };

            document.getElementById('clear-filters').addEventListener('click', function() {
                window.clearFilters(fieldMap, screenKey);
                fetchSubmissions();
            });

            FilterFreezer.loadFilters(screenKey, fieldMap);

            function fetchSubmissions(page = 1, perPage = 30) {
                const search = searchInput.value;

                const params = new URLSearchParams();
                if (search.length >= 3 || search.length === 0) params.append('search', search);
                if (perPage) params.append('per_page', perPage);
                params.set('page', page);

                FilterFreezer.saveFilters(screenKey, fieldMap);
                wrapper.classList.add('opacity-50', 'pointer-events-none');

                apiFetch("{{ route('admin.crm.kabba-ai-customers.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }, {
                        loaderSelector: '#kabba-ai-customers-loading',
                        containerSelector: '#submission-table-wrapper'
                    })
                    .then(response => {
                        wrapper.innerHTML = response.html;
                        totalCount.textContent = response.total ?? 0;
                        initSingleDeleteButtons();
                    })
                    .finally(() => {
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }

            fetchSubmissions(pageParam, perPageParam);

            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchSubmissions
            });

            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchSubmissions, 400);
            });

            function initSingleDeleteButtons() {
                document.querySelectorAll('.delete-customer-btn').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const uniqueId = this.dataset.uniqueId;
                        const url = this.dataset.url;

                        window.showConfirm(
                            'Delete this customer? This action cannot be undone!',
                            'Delete Customer'
                        ).then(function(result) {
                            if (result.isConfirmed) {
                                fetch(url, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    }
                                })
                                .then(res => res.json())
                                .then(function(data) {
                                    if (data.success) {
                                        notyf.success(data.message, 'Deleted!');
                                        const row = document.getElementById('submission-row-' + uniqueId);
                                        if (row) row.remove();
                                        const count = parseInt(totalCount.textContent, 10);
                                        if (!isNaN(count)) totalCount.textContent = Math.max(0, count - 1);
                                    } else {
                                        notyf.error(data.message || 'Failed to delete customer.');
                                    }
                                });
                            }
                        });
                    });
                });
            }
        });
    </script>
@endpush
