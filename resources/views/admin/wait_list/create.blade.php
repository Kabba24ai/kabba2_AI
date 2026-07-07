@extends('admin.layouts.app')

@section('title', 'New Wait List')

@push('css')
<style> main { background-color: #f8fafc; flex: 1 1 auto; } </style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\WaitList\WaitListReason;
        use App\Enums\WaitList\WaitListRequestType;
        use App\Enums\WaitList\WaitListStorePreference;
        $inputClass = 'w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white focus:ring focus:border-blue-400 outline-none disabled:bg-gray-50 disabled:text-gray-400';
        $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
        $helpClass  = 'text-xs text-gray-400 mt-1';
        // Flat equipment list for the client-side category filter (search aid only —
        // matching still runs against the submitted equipment IDs, never the filter)
        $equipmentOptions = $equipment->map(fn ($unit) => [
            'id'          => $unit->id,
            'label'       => $unit->equipment_name . ($unit->equipment_id ? ' (' . $unit->equipment_id . ')' : ''),
            'category_id' => $unit->product_category_id,
        ])->values();
    @endphp

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">New Wait List Record</h1>
                <p class="text-sm text-gray-500 mt-1">
                    One record = one equipment need. If a customer needs three machines, create three records.
                </p>
            </div>
            <a href="{{ route('admin.wait-list.index') }}"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
                <x-heroicon-o-arrow-left class="w-4 h-4" /> Wait List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.wait-list.store') }}">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6 space-y-5">

            {{-- Row 1: customer / request type / priority / reserved --}}
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
                    <label class="{{ $labelClass }} required">Equipment Request</label>
                    <select name="request_type" id="wl-request-type" required class="{{ $inputClass }}">
                        @foreach (WaitListRequestType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('request_type', 'category') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $helpClass }}">Select the type of equipment request.</p>
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

                <div class="hidden xl:flex items-center justify-center border-l border-gray-100 text-gray-300 select-none" aria-hidden="true">—</div>
            </div>

            {{-- Category Wait List section --}}
            <div id="wl-section-category" class="rounded-lg border border-blue-100 overflow-hidden transition-opacity">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-blue-50 border-b border-blue-100">
                    <x-heroicon-o-table-cells class="w-4 h-4 text-blue-600" />
                    <span class="text-sm font-semibold text-gray-800">If Category Wait List</span>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                    <div>
                        <label class="{{ $labelClass }} required">Equipment Category</label>
                        <select name="product_category_id" class="{{ $inputClass }}">
                            <option value="">— Select category —</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('product_category_id') == $category->id)>{{ $category->title }}</option>
                            @endforeach
                        </select>
                        <p class="{{ $helpClass }}">Select the equipment category requested.</p>
                        @error('product_category_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }} required">Store Preference</label>
                        <select name="store_preference" data-store="#wl-cat-store" class="wl-store-pref {{ $inputClass }}">
                            @foreach (WaitListStorePreference::cases() as $preference)
                                <option value="{{ $preference->value }}" @selected(old('store_preference', 'any_store') === $preference->value)>{{ $preference->label() }}</option>
                            @endforeach
                        </select>
                        <p class="{{ $helpClass }}">Where customer prefers to pick up.</p>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Store (if not Any Store)</label>
                        <select name="store_id" id="wl-cat-store" class="{{ $inputClass }}">
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
                        <select name="reason" class="{{ $inputClass }}">
                            <option value="">— Select reason —</option>
                            @foreach (WaitListReason::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="{{ $helpClass }}">Why the customer is being added to the wait list.</p>
                        @error('reason')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- OR divider --}}
            <div class="relative" aria-hidden="true">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-dashed border-gray-200"></div></div>
                <div class="relative flex justify-center">
                    <span class="bg-white px-3 py-0.5 rounded-full border border-gray-200 text-xs font-semibold text-gray-500">OR</span>
                </div>
            </div>

            {{-- Specific Equipment Wait List section --}}
            <div id="wl-section-specific" class="rounded-lg border border-green-100 overflow-hidden transition-opacity">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-green-50 border-b border-green-100">
                    <x-heroicon-o-truck class="w-4 h-4 text-green-600" />
                    <span class="text-sm font-semibold text-gray-800">If Specific Equipment Wait List</span>
                </div>
                <div class="p-4 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                        <div>
                            <label class="{{ $labelClass }} required">Equipment Category Filter</label>
                            <select name="equipment_category_filter" id="wl-eq-filter" class="{{ $inputClass }}">
                                <option value="">— Select category first —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('equipment_category_filter') == $category->id)>{{ $category->title }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $helpClass }}">Select a category to filter equipment.</p>
                        </div>

                        @for ($i = 0; $i < 3; $i++)
                            <div>
                                <label class="{{ $labelClass }} {{ $i === 0 ? 'required' : '' }}">Choice #{{ $i + 1 }}{{ $i > 0 ? ' (Optional)' : '' }}</label>
                                <select name="equipment_ids[]" data-old="{{ old("equipment_ids.$i") }}"
                                    class="wl-choice {{ $inputClass }}" disabled>
                                    <option value="">{{ $i === 0 ? '— Select equipment —' : '— Optional —' }}</option>
                                </select>
                                <p class="{{ $helpClass }}">{{ $i === 0 ? 'Required.' : 'Optional alternative.' }}</p>
                                @if ($i === 0)
                                    @error('equipment_ids')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                    @error('equipment_ids.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                @endif
                            </div>
                        @endfor
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                        <div>
                            <label class="{{ $labelClass }} required">Store Preference</label>
                            <select name="store_preference" data-store="#wl-sp-store" class="wl-store-pref {{ $inputClass }}">
                                @foreach (WaitListStorePreference::cases() as $preference)
                                    <option value="{{ $preference->value }}" @selected(old('store_preference', 'any_store') === $preference->value)>{{ $preference->label() }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $helpClass }}">Where customer prefers to pick up.</p>
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Store (if not Any Store)</label>
                            <select name="store_id" id="wl-sp-store" class="{{ $inputClass }}">
                                <option value="">— Select store —</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->store_name }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $helpClass }}">Required when not Any Store.</p>
                        </div>

                        <div>
                            <label class="{{ $labelClass }} required">Reason</label>
                            <select name="reason" class="{{ $inputClass }}">
                                <option value="">— Select reason —</option>
                                @foreach (WaitListReason::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $helpClass }}">Why the customer is being added to the wait list.</p>
                        </div>

                        <div class="hidden xl:flex items-center justify-center border-l border-gray-100 text-gray-300 select-none" aria-hidden="true">—</div>
                    </div>
                </div>
            </div>

            {{-- Internal Notes --}}
            <div>
                <label class="{{ $labelClass }}">Internal Notes</label>
                <textarea name="internal_notes" rows="4"
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
    const typeSelect    = document.getElementById('wl-request-type');
    const filter        = document.getElementById('wl-eq-filter');
    const choiceSelects = Array.from(document.querySelectorAll('.wl-choice'));
    const sections = {
        category: document.getElementById('wl-section-category'),
        specific: document.getElementById('wl-section-specific'),
    };

    const EQUIPMENT = @json($equipmentOptions);

    // Rebuild the three choice dropdowns from the selected filter category.
    // Selections that don't belong to the new category simply find no matching
    // option and fall back to the placeholder (i.e. they are cleared).
    function populateChoices() {
        const catId = filter.value;
        choiceSelects.forEach(function (sel, i) {
            const keep = sel.value || sel.dataset.old || '';
            sel.dataset.old = '';
            sel.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = i === 0 ? '— Select equipment —' : '— Optional —';
            sel.appendChild(placeholder);
            if (!catId) return;
            EQUIPMENT.filter(u => String(u.category_id) === catId).forEach(function (u) {
                const option = document.createElement('option');
                option.value = u.id;
                option.textContent = u.label;
                if (String(u.id) === String(keep)) option.selected = true;
                sel.appendChild(option);
            });
        });
    }

    // Both sections stay rendered (no jumping); the inactive one is dimmed and
    // its inputs disabled so only the active section's fields submit.
    function setSection(el, active) {
        el.classList.toggle('opacity-50', !active);
        el.querySelectorAll('select, input, textarea').forEach(f => f.disabled = !active);
    }

    function sync() {
        const specificMode = typeSelect.value === 'specific_equipment';
        setSection(sections.category, !specificMode);
        setSection(sections.specific, specificMode);

        // Choice dropdowns stay locked until a filter category is chosen
        if (specificMode) {
            choiceSelects.forEach(sel => sel.disabled = !filter.value);
        }

        // Store selector only applies when the preference names a store
        document.querySelectorAll('.wl-store-pref').forEach(function (pref) {
            const store = document.querySelector(pref.dataset.store);
            if (!pref.disabled) store.disabled = pref.value === 'any_store';
        });
    }

    populateChoices();
    sync();

    typeSelect.addEventListener('change', sync);
    filter.addEventListener('change', function () { populateChoices(); sync(); });
    document.querySelectorAll('.wl-store-pref').forEach(p => p.addEventListener('change', sync));

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
