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
        $inputClass = 'w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white focus:ring focus:border-blue-400 outline-none';
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
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
            <div class="md:col-span-2 xl:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1 required">CRM Customer</label>
                <select name="customer_id" id="wl-customer" required class="{{ $inputClass }}">
                    <option value="">— Select customer —</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                            {{ trim($customer->first_name . ' ' . $customer->last_name) }}
                            @if ($customer->company_name) — {{ $customer->company_name }} @endif
                            @if ($customer->phone) ({{ $customer->phone }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('customer_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Request Type</label>
                <select name="request_type" id="wl-request-type" required class="{{ $inputClass }}">
                    @foreach (WaitListRequestType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('request_type', 'category') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div id="wl-category-wrap">
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Category</label>
                <select name="product_category_id" class="{{ $inputClass }}">
                    <option value="">— Select category —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('product_category_id') == $category->id)>{{ $category->title }}</option>
                    @endforeach
                </select>
                @error('product_category_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div id="wl-equipment-wrap" class="md:col-span-2 xl:col-span-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Specific Equipment (up to 3)</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @for ($i = 0; $i < 3; $i++)
                        <select name="equipment_ids[]" class="{{ $inputClass }}">
                            <option value="">— {{ $i === 0 ? 'Equipment' : 'Optional' }} —</option>
                            @foreach ($equipment as $unit)
                                <option value="{{ $unit->id }}" @selected(old("equipment_ids.$i") == $unit->id)>
                                    {{ $unit->equipment_name }} @if ($unit->equipment_id)({{ $unit->equipment_id }})@endif
                                </option>
                            @endforeach
                        </select>
                    @endfor
                </div>
                @error('equipment_ids')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                @error('equipment_ids.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Store Preference</label>
                <select name="store_preference" id="wl-store-pref" required class="{{ $inputClass }}">
                    @foreach (WaitListStorePreference::cases() as $preference)
                        <option value="{{ $preference->value }}" @selected(old('store_preference', 'any_store') === $preference->value)>{{ $preference->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div id="wl-store-wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Store</label>
                <select name="store_id" class="{{ $inputClass }}">
                    <option value="">— Select store —</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->store_name }}</option>
                    @endforeach
                </select>
                @error('store_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Reason for Wait List</label>
                <select name="reason" required class="{{ $inputClass }}">
                    <option value="">— Select reason —</option>
                    @foreach (WaitListReason::options() as $value => $label)
                        <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('reason')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority Override</label>
                <select name="priority_override" class="{{ $inputClass }}">
                    <option value="">No priority override</option>
                    @foreach ([1, 2, 3] as $position)
                        <option value="{{ $position }}" @selected(old('priority_override') == $position)>Make this #{{ $position }}</option>
                    @endforeach
                </select>
                @error('priority_override')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2 xl:col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Internal Notes</label>
                <textarea name="internal_notes" rows="4"
                    placeholder="Any extra explanation for the wait list reason, timing details, callbacks promised, etc."
                    class="{{ $inputClass }}">{{ old('internal_notes') }}</textarea>
            </div>

            <div class="md:col-span-2 xl:col-span-4 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium text-sm transition">
                    Create Wait List Record
                </button>
                <span class="text-xs text-gray-400">No automatic customer notifications, holds, or reservations are created.</span>
            </div>
        </div>
    </form>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const type = document.getElementById('wl-request-type');
    const pref = document.getElementById('wl-store-pref');

    function sync() {
        document.getElementById('wl-category-wrap').classList.toggle('hidden', type.value !== 'category');
        document.getElementById('wl-equipment-wrap').classList.toggle('hidden', type.value !== 'specific_equipment');
        document.getElementById('wl-store-wrap').classList.toggle('hidden', pref.value === 'any_store');
    }

    type.addEventListener('change', sync);
    pref.addEventListener('change', sync);
    sync();

    // Searchable CRM customer selector — search box opens with the dropdown,
    // filtering across name / company / phone in the option label.
    new Choices(document.getElementById('wl-customer'), {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        searchResultLimit: 1000,
        renderChoiceLimit: -1,
        searchPlaceholderValue: 'Type customer, company, or contact name…',
        placeholderValue: '— Select customer —',
    });
});
</script>
@endpush
