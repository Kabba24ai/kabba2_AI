<div class="space-y-4">

    <div class="flex items-center justify-between mb-2">
        <div>
            <h2 class="text-lg font-semibold">Contract Drivers</h2>
            <p class="text-sm text-gray-500">External drivers hired to move equipment. They are assignable in Dispatch but are not company (HRM) employees.</p>
        </div>
        <button type="button" id="add-contract-btn"
            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <x-heroicon-o-plus class="w-4 h-4" />
            Add Contract Driver
        </button>
    </div>

    {{-- Add form --}}
    <div id="add-contract-form" class="hidden bg-white rounded-xl shadow-sm border p-4">
        <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.contract-driver.save') }}">
            @csrf
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">First Name<span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" required class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Last Name</label>
                    <input type="text" name="last_name" class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Phone</label>
                    <input type="text" name="mobile_phone" class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm" placeholder="(555) 555-5555">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Designation</label>
                    <select name="designation" class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm">
                        @foreach ($driverDesignations as $value => $label)
                            <option value="{{ $value }}" {{ $value === 'alternate' ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cdl_a" value="1" class="rounded border-gray-300 text-indigo-600">
                        <span class="text-sm">CDL A</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cdl_b" value="1" class="rounded border-gray-300 text-indigo-600">
                        <span class="text-sm">CDL B</span>
                    </label>
                </div>
                <div class="flex items-end justify-end gap-2">
                    <button type="button" id="cancel-contract-btn" class="px-4 py-1.5 rounded-lg text-xs border border-gray-300 text-gray-700 hover:bg-gray-100">Cancel</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg text-xs font-medium">Add Driver</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Existing contract drivers --}}
    <div class="grid grid-cols-3 gap-4">
    @forelse ($contractDrivers as $driver)
        @php $cap = $driverCapabilities->get($driver->id); @endphp
        <div class="bg-white rounded-xl shadow-sm border flex flex-col">
            <div class="flex items-center gap-3 px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 text-sm font-bold flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($driver->first_name, 0, 1) . substr($driver->last_name, 0, 1)) }}
                </div>
                <span class="font-semibold text-sm truncate min-w-0">{{ $driver->full_name }}</span>
                <span class="ml-auto shrink-0 text-xs text-amber-700 font-medium bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">Contract</span>
            </div>

            <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.contract-driver.save') }}" class="p-4 flex flex-col flex-1">
                @csrf
                <input type="hidden" name="id" value="{{ $driver->id }}">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">First Name</label>
                        <input type="text" name="first_name" value="{{ $driver->first_name }}" required class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Last Name</label>
                        <input type="text" name="last_name" value="{{ $driver->last_name }}" class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Phone</label>
                        <input type="text" name="mobile_phone" value="{{ $driver->mobile_phone }}" class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Designation <span class="text-gray-400">(AI assignment tier)</span></label>
                        <select name="designation" class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm">
                            @foreach ($driverDesignations as $value => $label)
                                <option value="{{ $value }}" {{ ($cap?->designation ?? 'alternate') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cdl_a" value="1" class="rounded border-gray-300 text-indigo-600" {{ $driver->cdl_a ? 'checked' : '' }}>
                        <span class="text-sm">CDL A</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cdl_b" value="1" class="rounded border-gray-300 text-indigo-600" {{ $driver->cdl_b ? 'checked' : '' }}>
                        <span class="text-sm">CDL B</span>
                    </label>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg text-xs font-medium">Save</button>
                </div>
            </form>

            <div class="px-4 pb-4 -mt-2">
                <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.contract-driver.delete', $driver->id) }}"
                      onsubmit="return confirm('Deactivate this contract driver? They will no longer be assignable in Dispatch.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-gray-500 hover:text-red-600 hover:underline">Deactivate</button>
                </form>
            </div>
        </div>
    @empty
        <div class="col-span-3 bg-white rounded-xl shadow-sm p-8 text-center text-gray-400 text-sm italic">
            No contract drivers yet. Use “Add Contract Driver” to add an external driver.
        </div>
    @endforelse
    </div>

    <script>
    (function () {
        const btn  = document.getElementById('add-contract-btn');
        const form = document.getElementById('add-contract-form');
        const cancel = document.getElementById('cancel-contract-btn');
        btn?.addEventListener('click', function () {
            form?.classList.remove('hidden');
            btn.classList.add('hidden');
        });
        cancel?.addEventListener('click', function () {
            form?.classList.add('hidden');
            btn?.classList.remove('hidden');
        });
    })();
    </script>

</div>
