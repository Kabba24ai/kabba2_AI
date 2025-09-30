<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-cube class="h-5 w-5 text-blue-600" aria-hidden="true" />
        <h3 class="text-lg font-bold text-gray-900">Rate Settings</h3>
    </div>

    <div class="grid grid-cols-1 gap-3">
        <div class="space-y-6 bg-blue-50 p-4 rounded-xl border border-blue-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                            d="M3 6h10M3 10h10M3 14h7M17 8l4 4-4 4" />
                    </svg>
                    <h2 class="text-lg font-semibold text-slate-800">Prepaid Fuel Rates</h2>
                </div>
                <button type="button" data-add="fuel"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Fuel Rate
                </button>
            </div>

            <div id="fuel-list" class="space-y-4"></div>
        </div>
    </div>
</div>


<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-cube class="h-5 w-5 text-blue-600" aria-hidden="true" />
        <h3 class="text-lg font-bold text-gray-900">Rate Settings</h3>
    </div>

    <div class="grid grid-cols-1 gap-3">
        <div class="space-y-6 bg-green-50 p-4 rounded-xl border border-green-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                            d="M3 12l3 3 5-6m5 1l2 2 4-5" />
                    </svg>
                    <h2 class="text-lg font-semibold text-slate-800">Prepaid Cleaning Rates</h2>
                </div>
                <button type="button" data-add="clean"
                    class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Cleaning Rate
                </button>
            </div>

            <div id="clean-list" class="space-y-4"></div>
        </div>
    </div>
</div>

<!-- Row Template -->
<template id="rate-row">
    <div class="flex items-center gap-4 p-4 bg-white rounded-xl border transition-all duration-200 hover:shadow-md">
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Description</label>
                <input type="text" placeholder="e.g., Full Tank Prepaid" name="description[]"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-slate-700 placeholder-slate-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Amount</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 select-none">$</span>
                    <input type="text" inputmode="decimal" placeholder="0.00" name="rate[]"
                        class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 text-slate-700 placeholder-slate-400 js-money">
                </div>
            </div>
        </div>
        <button type="button" data-remove
            class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-200"
            title="Remove">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fuelList = document.getElementById('fuel-list');
            const cleanList = document.getElementById('clean-list');
            const rowTemplate = document.getElementById('rate-row').content;

            // ---- 1) Bring Laravel settings into JS (safe even if missing) ----
            const storedFuelRaw  = @json($settings['Product Settings']['prepaid_fuel_rates']['setting_value'] ?? []);
            const storedCleanRaw = @json($settings['Product Settings']['prepaid_cleaning_rates']['setting_value'] ?? []);

            storedCleanRaw.forEach(item => addRateRow(cleanList, item));
            storedFuelRaw.forEach(item => addRateRow(fuelList, item));

            // Function to add a new rate row
            function addRateRow(list, item = {description: '', rate: ''}) {
                const newRow = rowTemplate.cloneNode(true);
                // Determine current index based on existing rows
                const index = list.children.length;
                const inputs = newRow.querySelectorAll('input');
                if (list.id === 'fuel-list') {
                    inputs[0].name = `fuel[${index}][description]`;
                    inputs[1].name = `fuel[${index}][rate]`;
                    inputs[0].value = item.description || '';
                    inputs[1].value = item.rate || '';
                } else if (list.id === 'clean-list') {
                    inputs[0].name = `clean[${index}][description]`;
                    inputs[1].name = `clean[${index}][rate]`;
                    inputs[0].value = item.description || '';
                    inputs[1].value = item.rate || '';
                }
                list.appendChild(newRow);
            }

            // Event delegation for adding rows
            document.body.addEventListener('click', function(event) {
                if (event.target.closest('[data-add="fuel"]')) {
                    addRateRow(fuelList);
                } else if (event.target.closest('[data-add="clean"]')) {
                    addRateRow(cleanList);
                } else if (event.target.closest('[data-remove]')) {
                    const row = event.target.closest('div.flex.items-center');
                    if (row) {
                        row.remove();
                    }
                }
            });
        });
    </script>
@endpush
