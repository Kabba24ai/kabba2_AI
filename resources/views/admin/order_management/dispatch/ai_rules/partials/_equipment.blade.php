<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold">Equipment Transport Rules</h2>
        <p class="text-sm text-gray-500">Define transport requirements per equipment category. AI will use these rules to match trailers and trucks to orders.</p>
    </div>

    @forelse ($categories as $category)
        @php $rule = $equipmentRules->get($category->id); @endphp
        <div class="bg-white rounded-xl shadow-sm border">
            <div class="flex items-center gap-3 px-5 py-3 border-b bg-gray-50 rounded-t-xl">
                <x-heroicon-o-wrench-screwdriver class="w-4 h-4 text-gray-500" />
                <span class="font-semibold text-sm">{{ $category->title }}</span>
                @if ($rule)
                    <span class="ml-auto text-xs text-green-600 font-medium bg-green-50 border border-green-200 px-2 py-0.5 rounded-full">Configured</span>
                @else
                    <span class="ml-auto text-xs text-gray-400 font-medium bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-full">No rule</span>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.equipment-rule.save') }}" class="p-5">
                @csrf
                <input type="hidden" name="product_category_id" value="{{ $category->id }}">

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Min Trailer Capacity (lbs)</label>
                        <input type="number" name="min_trailer_capacity" min="0" step="100"
                            value="{{ $rule?->min_trailer_capacity }}"
                            class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="e.g. 10000">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Allowed Trailer Types</label>
                        <div class="space-y-1">
                            @foreach ($hitchTypes as $hitch)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="allowed_trailer_types[]" value="{{ $hitch }}"
                                        class="rounded border-gray-300 text-indigo-600"
                                        {{ in_array($hitch, $rule?->allowed_trailer_types ?? []) ? 'checked' : '' }}>
                                    {{ $hitch }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Allowed Truck Types</label>
                        <div class="space-y-1">
                            @foreach ($hitchTypes as $hitch)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="allowed_truck_types[]" value="{{ $hitch }}"
                                        class="rounded border-gray-300 text-indigo-600"
                                        {{ in_array($hitch, $rule?->allowed_truck_types ?? []) ? 'checked' : '' }}>
                                    {{ $hitch }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-6 mt-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cdl_required" value="1" class="rounded border-gray-300 text-indigo-600"
                            {{ $rule?->cdl_required ? 'checked' : '' }}>
                        <span class="text-sm">CDL Required</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="can_share_trailer" value="1" class="rounded border-gray-300 text-indigo-600"
                            {{ $rule?->can_share_trailer ? 'checked' : '' }}>
                        <span class="text-sm">Can Share Trailer</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="must_haul_alone" value="1" class="rounded border-gray-300 text-indigo-600"
                            {{ $rule?->must_haul_alone ? 'checked' : '' }}>
                        <span class="text-sm">Must Haul Alone</span>
                    </label>
                </div>

                <div class="mt-3">
                    <label class="block text-xs text-gray-500 mb-1">Special Notes</label>
                    <textarea name="special_notes" rows="2"
                        class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Any special transport instructions for AI...">{{ $rule?->special_notes }}</textarea>
                </div>

                <div class="mt-3 flex justify-end">
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg text-xs font-medium">
                        Save Rule
                    </button>
                </div>
            </form>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400 text-sm italic">
            No equipment categories found.
        </div>
    @endforelse
</div>
