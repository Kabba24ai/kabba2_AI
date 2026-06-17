<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">Trucks</h2>
            <p class="text-sm text-gray-500">Register trucks available for dispatch. AI will match trucks to jobs based on type, GVWR, and CDL requirements.</p>
        </div>
        <button type="button" id="add-truck-btn"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <x-heroicon-o-plus class="w-4 h-4" /> Add Truck
        </button>
    </div>

    {{-- Step 1: Select Truck Type --}}
    <div id="add-truck-step1" class="hidden bg-white rounded-xl shadow-sm border p-6">
        <div class="flex items-center gap-2 mb-5">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold">1</span>
            <h3 class="font-semibold text-sm text-gray-800">Select Truck Type</h3>
        </div>
        <div class="max-w-xs">
            <label class="block text-xs text-gray-500 mb-1">Truck Type <span class="text-red-500">*</span></label>
            <select id="step1-truck-type"
                class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">— Select Type —</option>
                @foreach ($truckTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
            <p id="step1-error" class="text-xs text-red-500 mt-1 hidden">Please select a truck type to continue.</p>
        </div>
        <div class="mt-5 flex items-center gap-3">
            <button type="button" onclick="cancelAddTruck()"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <button type="button" onclick="proceedToTruckForm()"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                Next <x-heroicon-o-arrow-right class="w-4 h-4" />
            </button>
        </div>
    </div>

    {{-- Step 2: Full truck form --}}
    <div id="add-truck-form" class="hidden bg-white rounded-xl shadow-sm border p-5">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white text-xs font-bold">✓</span>
                <span class="text-xs text-green-700 font-medium">Type selected:</span>
                <span id="step2-type-label" class="text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-full"></span>
            </div>
            <button type="button" onclick="backToStep1()"
                class="text-xs text-gray-400 hover:text-gray-600 underline ml-2">Change type</button>
        </div>
        <h3 class="font-semibold text-sm mb-4">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold mr-2">2</span>
            Enter Truck Details
        </h3>
        <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.truck.save') }}">
            @csrf
            @include('admin.order_management.dispatch.ai_rules.partials._truck_fields', ['truck' => null, 'selectedTruckType' => '', 'fieldPrefix' => 'addtruck'])
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="cancelAddTruck()"
                    class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    Save Truck
                </button>
            </div>
        </form>
    </div>

    {{-- Existing Trucks --}}
    @forelse ($trucks as $truck)
        <div class="bg-white rounded-xl shadow-sm border">
            <div class="flex items-center gap-3 px-5 py-3 border-b bg-gray-50 rounded-t-xl">
                <x-heroicon-o-truck class="w-4 h-4 text-blue-600" />
                <div class="flex flex-col min-w-0">
                    <span class="font-semibold text-sm">{{ $truck->truck_name }}</span>
                    @if ($truck->truck_type)
                        <span class="text-xs text-blue-600 font-medium">{{ $truck->truck_type }}</span>
                    @endif
                </div>
                @if ($truck->truck_number)
                    <span class="text-xs text-gray-400">#{{ $truck->truck_number }}</span>
                @endif
                <span class="ml-auto text-xs {{ $truck->is_active ? 'text-green-600 bg-green-50 border-green-200' : 'text-gray-400 bg-gray-100 border-gray-200' }} border px-2 py-0.5 rounded-full">
                    {{ $truck->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.truck.save') }}" class="p-5">
                @csrf
                <input type="hidden" name="id" value="{{ $truck->id }}">
                @include('admin.order_management.dispatch.ai_rules.partials._truck_fields', ['truck' => $truck, 'fieldPrefix' => 'truck-'.$truck->id])
                <div class="mt-4 flex justify-between items-center">
                    <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.truck.delete', $truck->id) }}"
                        onsubmit="return confirm('Remove this truck?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                    </form>
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg text-xs font-medium">
                        Save
                    </button>
                </div>
            </form>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400 text-sm italic">
            No trucks added yet. Click "Add Truck" to get started.
        </div>
    @endforelse

</div>

<script>
(function () {
    document.getElementById('add-truck-btn').addEventListener('click', function () {
        this.classList.add('hidden');
        document.getElementById('add-truck-step1').classList.remove('hidden');
        document.getElementById('add-truck-form').classList.add('hidden');
    });

    window.cancelAddTruck = function () {
        document.getElementById('add-truck-step1').classList.add('hidden');
        document.getElementById('add-truck-form').classList.add('hidden');
        document.getElementById('add-truck-btn').classList.remove('hidden');
        document.getElementById('step1-truck-type').value = '';
        document.getElementById('step1-error').classList.add('hidden');
    };

    window.proceedToTruckForm = function () {
        const type = document.getElementById('step1-truck-type').value;
        if (!type) {
            document.getElementById('step1-error').classList.remove('hidden');
            return;
        }
        document.getElementById('step1-error').classList.add('hidden');
        document.getElementById('step2-type-label').textContent = type;

        // Set the truck_type select inside the form and hide its wrapper
        const visibleSelect = document.querySelector('#add-truck-form select[name="truck_type"]');
        if (visibleSelect) {
            visibleSelect.value = type;
            visibleSelect.closest('div').classList.add('hidden');
        }

        document.getElementById('add-truck-step1').classList.add('hidden');
        document.getElementById('add-truck-form').classList.remove('hidden');
    };

    window.backToStep1 = function () {
        document.getElementById('add-truck-form').classList.add('hidden');
        document.getElementById('add-truck-step1').classList.remove('hidden');
    };
})();
</script>
