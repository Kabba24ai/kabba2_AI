@extends('admin.layouts.app')

@section('title', 'Rental Options Lists')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Rental Options</h3>
        <div class="flex items-center gap-3">
            <div class="relative">
                <input type="text" id="options-search" name="search" value="{{ request('search') }}"
                    placeholder="Search options..." autocomplete="off"
                    class="w-56 rounded-lg border border-gray-300 bg-white px-4 py-2 pr-9 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
                <div class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                </div>
            </div>
            <a href="{{ route('admin.product-management.options.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                + Create Options
            </a>
        </div>
    </div>

    @include('flash::message')
    {{-- Main Container --}}
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- Table Card --}}
        <div id="options-table-wrapper"
            class="flex-1 overflow-x-auto rounded-lg border border-gray-200 bg-white dark:bg-gray-900 shadow-sm transition-opacity duration-200">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">Name
                        </th>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">Type
                        </th>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">
                            Description</th>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">
                            Options Count</th>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">
                            Status</th>
                        <th class="whitespace-nowrap px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300">
                            Actions</th>
                    </tr>
                </thead>
                <tbody id="options-table-body" class="bg-white dark:bg-gray-950">
                    @include('admin.product_management.options.partials._rows')
                </tbody>
            </table>
        </div>

        {{-- Sidebar Quick Stats --}}
        <div
            class="w-full lg:w-1/4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-white mb-4">Quick Stats</h4>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li class="flex justify-between">
                    <span>Total Lists:</span>
                    <span class="font-medium">{{ $totalOptions }}</span>
                </li>
                <li class="flex justify-between">
                    <span>Active Lists:</span>
                    <span class="font-medium">{{ $activeOptions }}</span>
                </li>
                <li class="flex justify-between">
                    <span>Inactive Lists:</span>
                    <span class="font-medium">{{ $inactiveOptions }}</span>
                </li>
            </ul>
        </div>
    </div>

    @push('js')
        <script>
            (function() {
                const searchInput = document.getElementById('options-search');
                const tableBody = document.getElementById('options-table-body');
                const wrapper = document.getElementById('options-table-wrapper');
                const rowsUrl = @json(route('admin.product-management.options.index'));
                let debounceTimer;

                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => fetchOptions(searchInput.value.trim()), 400);
                });

                function fetchOptions(search) {
                    const params = new URLSearchParams();
                    if (search) params.append('search', search);

                    wrapper.classList.add('opacity-50', 'pointer-events-none');

                    fetch(`${rowsUrl}?${params.toString()}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.text())
                        .then(html => {
                            tableBody.innerHTML = html;
                        })
                        .catch(error => {
                            tableBody.innerHTML =
                                '<tr><td colspan="6" class="text-center px-6 py-10 text-red-500">Something went wrong loading the data.</td></tr>';
                            console.error('Error fetching options:', error);
                        })
                        .finally(() => {
                            wrapper.classList.remove('opacity-50', 'pointer-events-none');
                        });
                }
            })();
        </script>
    @endpush
@endsection
