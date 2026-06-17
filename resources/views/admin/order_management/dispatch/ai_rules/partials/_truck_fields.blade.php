<div class="grid grid-cols-2 gap-4">
    {{-- Truck Type is first --}}
    <div>
        <label class="block text-xs text-gray-500 mb-1">Truck Type <span class="text-red-500">*</span></label>
        <select name="truck_type" id="{{ isset($fieldPrefix) ? $fieldPrefix.'-truck-type' : 'truck-type-field' }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
            <option value="">— Select Type —</option>
            @foreach ($truckTypes as $type)
                <option value="{{ $type }}" {{ ($truck?->truck_type ?? $selectedTruckType ?? '') === $type ? 'selected' : '' }}>
                    {{ $type }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Truck Name <span class="text-red-500">*</span></label>
        <input type="text" name="truck_name" required value="{{ $truck?->truck_name }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g. F-350 Crew Cab">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Truck Number</label>
        <input type="text" name="truck_number" value="{{ $truck?->truck_number }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g. TRK-01">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Store</label>
        <select name="store_id" class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">— Any —</option>
            @foreach ($stores as $store)
                <option value="{{ $store->id }}" {{ $truck?->store_id == $store->id ? 'selected' : '' }}>
                    {{ $store->store_name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">GVWR (lbs)</label>
        <input type="number" name="gvwr" min="0" step="100" value="{{ $truck?->gvwr ? (int)$truck->gvwr : '' }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g. 11500">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tow Rating (lbs)</label>
        <input type="number" name="tow_rating" min="0" step="100" value="{{ $truck?->tow_rating ? (int)$truck->tow_rating : '' }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g. 18000">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-2">Hitch Types</label>
        <div class="flex flex-wrap gap-3">
            @foreach ($trailerTypes as $hitch)
                <label class="flex items-center gap-1.5 cursor-pointer text-sm">
                    <input type="checkbox" name="hitch_types[]" value="{{ $hitch }}"
                        class="rounded border-gray-300 text-indigo-600"
                        {{ in_array($hitch, $truck?->hitch_types ?? []) ? 'checked' : '' }}>
                    {{ $hitch }}
                </label>
            @endforeach
        </div>
    </div>
</div>
<div class="flex items-center gap-6 mt-4">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="cdl_required" value="1" class="rounded border-gray-300 text-indigo-600"
            {{ $truck?->cdl_required ? 'checked' : '' }}>
        <span class="text-sm">CDL Required</span>
    </label>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            {{ ($truck === null || $truck?->is_active) ? 'checked' : '' }}>
        <span class="text-sm">Active</span>
    </label>
</div>
<div class="mt-3">
    <label class="block text-xs text-gray-500 mb-1">Notes</label>
    <textarea name="notes" rows="2"
        class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="Optional notes for AI...">{{ $truck?->notes }}</textarea>
</div>
