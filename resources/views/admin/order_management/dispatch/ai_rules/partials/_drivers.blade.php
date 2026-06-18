<div class="space-y-4">

    <div class="flex items-center justify-between mb-2">
        <div>
            <h2 class="text-lg font-semibold">Driver Capabilities</h2>
            <p class="text-sm text-gray-500">Configure transport capabilities for each driver so AI can make appropriate assignments.</p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
    @forelse ($drivers as $driver)
        @php $cap = $driverCapabilities->get($driver->id); @endphp
        <div class="bg-white rounded-xl shadow-sm border flex flex-col">

            {{-- Card header --}}
            <div class="flex items-center gap-3 px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-sm font-bold flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($driver->first_name, 0, 1) . substr($driver->last_name, 0, 1)) }}
                </div>
                <span class="font-semibold text-sm truncate min-w-0">{{ $driver->full_name }}</span>
                @if ($cap)
                    <span class="ml-auto shrink-0 text-xs text-green-600 font-medium bg-green-50 border border-green-200 px-2 py-0.5 rounded-full">Configured</span>
                @else
                    <span class="ml-auto shrink-0 text-xs text-gray-400 font-medium bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-full">Not configured</span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.driver-capability.save') }}" class="p-4 flex flex-col flex-1">
                @csrf
                <input type="hidden" name="user_id" value="{{ $driver->id }}">

                {{-- CDL from HRM (read-only) --}}
                <div class="mb-3 p-2.5 bg-gray-50 border border-gray-200 rounded-lg">
                    <p class="text-xs text-gray-500 mb-1.5 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        CDL — set in <a href="{{ route('admin.hrm.users.edit', $driver->unique_id) }}" class="underline text-indigo-600 hover:text-indigo-800">HRM Settings</a>
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full
                            {{ $driver->cdl_a ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-400 border border-gray-200' }}">
                            @if ($driver->cdl_a)
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @endif
                            CDL A
                        </span>
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full
                            {{ $driver->cdl_b ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-400 border border-gray-200' }}">
                            @if ($driver->cdl_b)
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @endif
                            CDL B
                        </span>
                    </div>
                </div>

                {{-- Transport capability checkboxes --}}
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="can_tow_equipment_trailer" value="1"
                            class="rounded border-gray-300 text-indigo-600"
                            {{ $cap?->can_tow_equipment_trailer ? 'checked' : '' }}>
                        <span class="text-sm">Can Tow Trailer</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="can_tow_gooseneck" value="1"
                            class="rounded border-gray-300 text-indigo-600"
                            {{ $cap?->can_tow_gooseneck ? 'checked' : '' }}>
                        <span class="text-sm">Can Tow Gooseneck</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer col-span-2">
                        <input type="checkbox" name="can_operate_cdl_truck" value="1"
                            class="rounded border-gray-300 text-indigo-600"
                            {{ $cap?->can_operate_cdl_truck ? 'checked' : '' }}>
                        <span class="text-sm">Can Operate CDL Truck</span>
                    </label>
                </div>

                {{-- Numeric / select fields --}}
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Max GVWR (lbs)</label>
                        <input type="number" name="max_gvwr" min="0" step="100"
                            value="{{ $cap?->max_gvwr }}"
                            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="e.g. 26000">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Max Trailer Wt (lbs)</label>
                        <input type="number" name="max_trailer_weight" min="0" step="100"
                            value="{{ $cap?->max_trailer_weight }}"
                            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="e.g. 14000">
                    </div>
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

                {{-- Notes --}}
                <div class="mt-3">
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
        <div class="col-span-3 bg-white rounded-xl shadow-sm p-8 text-center text-gray-400 text-sm italic">
            No active drivers found. Mark users as drivers in HRM first.
        </div>
    @endforelse
    </div>

</div>
