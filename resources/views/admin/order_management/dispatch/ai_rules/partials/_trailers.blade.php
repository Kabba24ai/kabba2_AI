<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">Trailers</h2>
            <p class="text-sm text-gray-500">Register trailers available for dispatch. AI will match trailers to equipment based on payload capacity and hitch type.</p>
        </div>
        <button type="button" onclick="document.getElementById('add-trailer-form').classList.toggle('hidden')"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <x-heroicon-o-plus class="w-4 h-4" /> Add Trailer
        </button>
    </div>

    {{-- Add Trailer Form --}}
    <div id="add-trailer-form" class="hidden bg-white rounded-xl shadow-sm border p-5">
        <h3 class="font-semibold text-sm mb-4">New Trailer</h3>
        <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.trailer.save') }}">
            @csrf
            @include('admin.order_management.dispatch.ai_rules.partials._trailer_fields', ['trailer' => null])
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('add-trailer-form').classList.add('hidden')"
                    class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    Save Trailer
                </button>
            </div>
        </form>
    </div>

    {{-- Existing Trailers --}}
    <div class="grid grid-cols-3 gap-4">
    @forelse ($trailers as $trailer)
        <div class="bg-white rounded-xl shadow-sm border flex flex-col">
            <div class="flex items-center gap-3 px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <x-heroicon-o-rectangle-stack class="w-4 h-4 text-purple-600 shrink-0" />
                <div class="flex flex-col min-w-0">
                    <span class="font-semibold text-sm truncate">{{ $trailer->trailer_name }}</span>
                </div>
                @if ($trailer->trailer_number)
                    <span class="text-xs text-gray-400 shrink-0">#{{ $trailer->trailer_number }}</span>
                @endif
                <span class="ml-auto shrink-0 text-xs {{ $trailer->is_active ? 'text-green-600 bg-green-50 border-green-200' : 'text-gray-400 bg-gray-100 border-gray-200' }} border px-2 py-0.5 rounded-full">
                    {{ $trailer->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            {{-- Save form — closed before footer so delete form is not nested --}}
            <form id="save-trailer-{{ $trailer->id }}" method="POST"
                action="{{ route('admin.order-management.dispatch.ai-rules.trailer.save') }}" class="p-4 flex flex-col flex-1">
                @csrf
                <input type="hidden" name="id" value="{{ $trailer->id }}">
                @include('admin.order_management.dispatch.ai_rules.partials._trailer_fields', ['trailer' => $trailer])
            </form>
            {{-- Footer: delete form + save button as siblings (not nested) --}}
            <div class="px-4 pb-4 flex justify-between items-center">
                <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.trailer.delete', $trailer->id) }}"
                    onsubmit="return confirm('Remove this trailer?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                </form>
                <button type="submit" form="save-trailer-{{ $trailer->id }}"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg text-xs font-medium">
                    Save
                </button>
            </div>
        </div>
    @empty
        <div class="col-span-3 bg-white rounded-xl shadow-sm p-8 text-center text-gray-400 text-sm italic">
            No trailers added yet. Click "Add Trailer" to get started.
        </div>
    @endforelse
    </div>

</div>
