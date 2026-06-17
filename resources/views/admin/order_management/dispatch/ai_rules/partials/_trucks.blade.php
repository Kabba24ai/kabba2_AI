<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">Trucks</h2>
            <p class="text-sm text-gray-500">Register trucks available for dispatch. AI will match trucks to jobs based on GVWR and CDL requirements.</p>
        </div>
        <button type="button" onclick="document.getElementById('add-truck-form').classList.toggle('hidden')"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <x-heroicon-o-plus class="w-4 h-4" /> Add Truck
        </button>
    </div>

    {{-- Add Truck Form --}}
    <div id="add-truck-form" class="hidden bg-white rounded-xl shadow-sm border p-5">
        <h3 class="font-semibold text-sm mb-4">New Truck</h3>
        <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.truck.save') }}">
            @csrf
            @include('admin.order_management.dispatch.ai_rules.partials._truck_fields', ['truck' => null])
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('add-truck-form').classList.add('hidden')"
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
                <span class="font-semibold text-sm">{{ $truck->truck_name }}</span>
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
                @include('admin.order_management.dispatch.ai_rules.partials._truck_fields', ['truck' => $truck])
                <div class="mt-4 flex justify-between items-center">
                    <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.truck.delete', $truck->id) }}" onsubmit="return confirm('Remove this truck?')">
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
