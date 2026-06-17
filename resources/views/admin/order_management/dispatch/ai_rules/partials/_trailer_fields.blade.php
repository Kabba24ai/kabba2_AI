<div class="grid grid-cols-2 md:grid-cols-3 gap-4">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Trailer Name <span class="text-red-500">*</span></label>
        <input type="text" name="trailer_name" required value="{{ $trailer?->trailer_name }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g. 20ft Gooseneck">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Trailer Number</label>
        <input type="text" name="trailer_number" value="{{ $trailer?->trailer_number }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g. TRL-01">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Store</label>
        <select name="store_id" class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">— Any —</option>
            @foreach ($stores as $store)
                <option value="{{ $store->id }}" {{ $trailer?->store_id == $store->id ? 'selected' : '' }}>
                    {{ $store->store_name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">GVWR (lbs)</label>
        <input type="number" name="gvwr" min="0" step="100" value="{{ $trailer?->gvwr }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g. 25900">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Payload Capacity (lbs)</label>
        <input type="number" name="payload_capacity" min="0" step="100" value="{{ $trailer?->payload_capacity }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g. 14000">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Hitch Type</label>
        <select name="hitch_type" class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">— None —</option>
            @foreach ($hitchTypes as $hitch)
                <option value="{{ $hitch }}" {{ $trailer?->hitch_type === $hitch ? 'selected' : '' }}>{{ $hitch }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Deck Length (ft)</label>
        <input type="number" name="deck_length" min="0" step="0.5" value="{{ $trailer?->deck_length }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g. 20">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Deck Width (ft)</label>
        <input type="number" name="deck_width" min="0" step="0.5" value="{{ $trailer?->deck_width }}"
            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g. 8.5">
    </div>
</div>
<div class="flex items-center gap-6 mt-4">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="cdl_required" value="1" class="rounded border-gray-300 text-indigo-600"
            {{ $trailer?->cdl_required ? 'checked' : '' }}>
        <span class="text-sm">CDL Required</span>
    </label>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
            {{ ($trailer === null || $trailer?->is_active) ? 'checked' : '' }}>
        <span class="text-sm">Active</span>
    </label>
</div>
<div class="mt-3">
    <label class="block text-xs text-gray-500 mb-1">Notes</label>
    <textarea name="notes" rows="2"
        class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="Optional notes for AI...">{{ $trailer?->notes }}</textarea>
</div>
