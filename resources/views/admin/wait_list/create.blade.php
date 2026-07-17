@extends('admin.layouts.app')

@section('title', 'New Wait List')

@push('css')
<style> main { background-color: #f8fafc; flex: 1 1 auto; } </style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\WaitList\WaitListReason;
        use App\Enums\WaitList\WaitListStorePreference;
        $inputClass = 'w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white focus:ring focus:border-blue-400 outline-none disabled:bg-gray-50 disabled:text-gray-400';
        $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
        $helpClass  = 'text-xs text-gray-400 mt-1';
    @endphp

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">New Wait List Record</h1>
                <p class="text-sm text-gray-500 mt-1">
                    One record = one customer need in one category. Check every equipment product the customer would accept.
                </p>
            </div>
            <a href="{{ route('admin.wait-list.index') }}"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
                <x-heroicon-o-arrow-left class="w-4 h-4" /> Wait List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.wait-list.store') }}" id="wl-form">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6 space-y-5">

            {{-- Row 1: customer / category / priority / store preference --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                <div>
                    <label class="{{ $labelClass }} required">CRM Customer</label>
                    <select name="customer_id" id="wl-customer" required class="{{ $inputClass }}">
                        <option value="">Search customers by name, company, phone…</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                {{ trim($customer->first_name . ' ' . $customer->last_name) }}
                                @if ($customer->company_name) — {{ $customer->company_name }} @endif
                                @if ($customer->phone) ({{ $customer->phone }}) @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="{{ $helpClass }}">Type at least 3 letters to search.</p>
                    @error('customer_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }} required">Equipment Category</label>
                    <select name="product_category_id" id="wl-category" required class="{{ $inputClass }}">
                        <option value="">— Select category —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('product_category_id') == $category->id)>{{ $category->title }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $helpClass }}">One category per wait list record.</p>
                    @error('product_category_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">Priority Override</label>
                    <select name="priority_override" class="{{ $inputClass }}">
                        <option value="">No Priority Override</option>
                        @foreach ([1, 2, 3] as $position)
                            <option value="{{ $position }}" @selected(old('priority_override') == $position)>Move to Position #{{ $position }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $helpClass }}">Move this request ahead of others.</p>
                    @error('priority_override')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }} required">Store Preference</label>
                    <select name="store_preference" id="wl-store-pref" class="{{ $inputClass }}">
                        @foreach (WaitListStorePreference::cases() as $preference)
                            <option value="{{ $preference->value }}" @selected(old('store_preference', 'any_store') === $preference->value)>{{ $preference->label() }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $helpClass }}">Where the customer prefers to pick up.</p>
                    @error('store_preference')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Row 2: store (when named) / reason --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                <div>
                    <label class="{{ $labelClass }}">Store (if not Any Store)</label>
                    <select name="store_id" id="wl-store" class="{{ $inputClass }}">
                        <option value="">— Select store —</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->store_name }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $helpClass }}">Required when not Any Store.</p>
                    @error('store_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }} required">Reason</label>
                    <select name="reason" required class="{{ $inputClass }}">
                        <option value="">— Select reason —</option>
                        @foreach (WaitListReason::options() as $value => $label)
                            <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $helpClass }}">Why the customer is being added to the wait list.</p>
                    @error('reason')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Acceptable equipment products checklist --}}
            <div id="wl-products-card" class="rounded-lg border border-gray-200 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-truck class="w-4 h-4 text-blue-600" />
                        <span class="text-sm font-semibold text-gray-800">Acceptable Equipment Products</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span id="wl-product-count" class="text-xs font-medium text-gray-500"></span>
                        <label id="wl-select-all-wrap" class="hidden items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                            <input type="checkbox" id="wl-select-all" class="rounded border-gray-300">
                            Select All
                        </label>
                    </div>
                </div>
                <div id="wl-products" class="p-4">
                    <p class="text-sm text-gray-400 italic">Select an equipment category above to load its products.</p>
                </div>
                <p id="wl-products-error" class="hidden px-4 pb-3 text-sm text-red-600">Select at least one acceptable equipment product.</p>
                @error('product_ids')<p class="px-4 pb-3 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('product_ids.*')<p class="px-4 pb-3 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Internal Notes --}}
            <div>
                <label class="{{ $labelClass }}">Internal Notes</label>
                <textarea name="internal_notes" rows="3"
                    placeholder="Special requests, preferred equipment, transportation details, callback information, customer comments, or other internal notes."
                    class="{{ $inputClass }}">{{ old('internal_notes') }}</textarea>
                <p class="{{ $helpClass }}">Any additional details or context about this wait list.</p>
            </div>

            {{-- Actions --}}
            <div class="pt-3 border-t border-gray-100 flex flex-wrap items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium text-sm transition">
                    Create Wait List Record
                </button>
                <a href="{{ route('admin.wait-list.index') }}"
                    class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    Cancel
                </a>
                <span class="text-xs text-gray-400">No automatic customer notifications, holds, or reservations are created.</span>
            </div>
        </div>
    </form>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Rental products with their category memberships. The employee selects
    // acceptable PRODUCT TYPES — individual inventory units are evaluated
    // later, when a return triggers matching.
    const PRODUCTS = @json($productOptions);
    const OLD_SELECTED = @json(collect(old('product_ids', []))->map(fn ($id) => (string) $id));

    const categorySelect = document.getElementById('wl-category');
    const container      = document.getElementById('wl-products');
    const countEl        = document.getElementById('wl-product-count');
    const selectAll      = document.getElementById('wl-select-all');
    const selectAllWrap  = document.getElementById('wl-select-all-wrap');
    const errorEl        = document.getElementById('wl-products-error');

    function productCheckboxes() {
        return Array.from(container.querySelectorAll('input[name="product_ids[]"]'));
    }

    function refreshState() {
        const boxes = productCheckboxes();
        const checked = boxes.filter(b => b.checked).length;

        countEl.textContent = boxes.length
            ? checked + ' of ' + boxes.length + ' equipment options selected'
            : '';

        selectAll.checked = boxes.length > 0 && checked === boxes.length;
        selectAll.indeterminate = checked > 0 && checked < boxes.length;

        if (checked > 0) errorEl.classList.add('hidden');
    }

    // Changing category clears prior selections and loads that category's
    // products, alphabetically. Products from other categories never render.
    function renderProducts(preselect) {
        const catId = categorySelect.value;
        container.innerHTML = '';

        if (!catId) {
            container.innerHTML = '<p class="text-sm text-gray-400 italic">Select an equipment category above to load its products.</p>';
            selectAllWrap.classList.add('hidden');
            selectAllWrap.classList.remove('inline-flex');
            refreshState();
            return;
        }

        const products = PRODUCTS
            .filter(p => p.category_ids.map(String).includes(String(catId)))
            .sort((a, b) => a.name.localeCompare(b.name));

        if (!products.length) {
            container.innerHTML = '<p class="text-sm text-gray-400 italic">No rental products found in this category.</p>';
            selectAllWrap.classList.add('hidden');
            selectAllWrap.classList.remove('inline-flex');
            refreshState();
            return;
        }

        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-x-6 gap-y-2';

        products.forEach(function (product) {
            const label = document.createElement('label');
            label.className = 'flex items-center gap-2.5 text-sm text-gray-700 rounded-md px-2 py-1.5 hover:bg-gray-50 cursor-pointer';

            const box = document.createElement('input');
            box.type = 'checkbox';
            box.name = 'product_ids[]';
            box.value = product.id;
            box.className = 'rounded border-gray-300';
            box.checked = (preselect || []).includes(String(product.id));
            box.addEventListener('change', refreshState);

            const text = document.createElement('span');
            text.textContent = product.name;

            label.appendChild(box);
            label.appendChild(text);
            grid.appendChild(label);
        });

        container.appendChild(grid);
        selectAllWrap.classList.remove('hidden');
        selectAllWrap.classList.add('inline-flex');
        refreshState();
    }

    selectAll.addEventListener('change', function () {
        productCheckboxes().forEach(b => b.checked = selectAll.checked);
        refreshState();
    });

    categorySelect.addEventListener('change', function () {
        renderProducts([]); // stale selections never survive a category change
    });

    // At least one acceptable product before the record can be saved
    document.getElementById('wl-form').addEventListener('submit', function (e) {
        if (!productCheckboxes().some(b => b.checked)) {
            e.preventDefault();
            errorEl.classList.remove('hidden');
            document.getElementById('wl-products-card').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    // Store selector only applies when the preference names a store
    const storePref = document.getElementById('wl-store-pref');
    const storeSelect = document.getElementById('wl-store');
    function syncStore() {
        storeSelect.disabled = storePref.value === 'any_store';
        if (storeSelect.disabled) storeSelect.value = '';
    }
    storePref.addEventListener('change', syncStore);
    syncStore();

    // Restore state after a validation round-trip
    renderProducts(OLD_SELECTED);

    // Searchable CRM customer selector — search box opens with the dropdown,
    // filtering across name / company / phone in the option label.
    new Choices(document.getElementById('wl-customer'), {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        searchResultLimit: 1000,
        renderChoiceLimit: -1,
        searchPlaceholderValue: 'Search customers by name, company, phone…',
    });
});
</script>
@endpush
