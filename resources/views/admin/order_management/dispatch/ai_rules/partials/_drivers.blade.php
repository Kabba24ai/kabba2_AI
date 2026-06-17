<div class="space-y-4">

    <div class="flex items-center justify-between mb-2">
        <div>
            <h2 class="text-lg font-semibold">Driver Capabilities</h2>
            <p class="text-sm text-gray-500">Configure transport capabilities for each driver so AI can make appropriate assignments.</p>
        </div>
    </div>

    @forelse ($drivers as $driver)
        @php $cap = $driverCapabilities->get($driver->id); @endphp
        <div class="bg-white rounded-xl shadow-sm border">
            <div class="flex items-center gap-3 px-5 py-3 border-b bg-gray-50 rounded-t-xl">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-sm font-bold flex items-center justify-center">
                    {{ strtoupper(substr($driver->first_name, 0, 1) . substr($driver->last_name, 0, 1)) }}
                </div>
                <span class="font-semibold text-sm">{{ $driver->full_name }}</span>
                @if ($cap)
                    <span class="ml-auto text-xs text-green-600 font-medium bg-green-50 border border-green-200 px-2 py-0.5 rounded-full">Configured</span>
                @else
                    <span class="ml-auto text-xs text-gray-400 font-medium bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-full">Not configured</span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.driver-capability.save') }}" class="p-5">
                @csrf
                <input type="hidden" name="user_id" value="{{ $driver->id }}">

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                    {{-- CDL --}}
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cdl_license" value="1"
                            class="rounded border-gray-300 text-indigo-600"
                            {{ $cap?->cdl_license ? 'checked' : '' }}>
                        <span class="text-sm">CDL License</span>
                    </label>

                    {{-- Can tow equipment trailer --}}
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="can_tow_equipment_trailer" value="1"
                            class="rounded border-gray-300 text-indigo-600"
                            {{ $cap?->can_tow_equipment_trailer ? 'checked' : '' }}>
                        <span class="text-sm">Can Tow Equipment Trailer</span>
                    </label>

                    {{-- Can tow gooseneck --}}
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="can_tow_gooseneck" value="1"
                            class="rounded border-gray-300 text-indigo-600"
                            {{ $cap?->can_tow_gooseneck ? 'checked' : '' }}>
                        <span class="text-sm">Can Tow Gooseneck</span>
                    </label>

                    {{-- Can operate CDL truck --}}
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="can_operate_cdl_truck" value="1"
                            class="rounded border-gray-300 text-indigo-600"
                            {{ $cap?->can_operate_cdl_truck ? 'checked' : '' }}>
                        <span class="text-sm">Can Operate CDL Truck</span>
                    </label>

                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">

                    {{-- Max GVWR --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Max GVWR (lbs)</label>
                        <input type="number" name="max_gvwr" min="0" step="100"
                            value="{{ $cap?->max_gvwr }}"
                            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="e.g. 26000">
                    </div>

                    {{-- Max trailer weight --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Max Trailer Weight (lbs)</label>
                        <input type="number" name="max_trailer_weight" min="0" step="100"
                            value="{{ $cap?->max_trailer_weight }}"
                            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="e.g. 14000">
                    </div>

                    {{-- Home store --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Home Store</label>
                        <select name="home_store_id"
                            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">— None —</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" {{ $cap?->home_store_id == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Skill rating --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Skill Rating (1–5)</label>
                        <select name="skill_rating"
                            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ ($cap?->skill_rating ?? 3) == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                </div>

                <div class="mt-4">
                    <label class="block text-xs text-gray-500 mb-1">Notes</label>
                    <textarea name="notes" rows="2"
                        class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Any special notes for AI to consider...">{{ $cap?->notes }}</textarea>
                </div>

                <div class="mt-3 flex justify-end">
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg text-xs font-medium">
                        Save
                    </button>
                </div>
            </form>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400 text-sm italic">
            No active drivers found. Mark users as drivers in HRM first.
        </div>
    @endforelse

</div>
