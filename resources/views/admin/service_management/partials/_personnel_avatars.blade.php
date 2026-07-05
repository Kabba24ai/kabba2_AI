{{-- Assigned personnel as overlapping initial avatars (HRM users) --}}
@if ($people->isEmpty())
    <span class="text-xs text-gray-400 italic">Unassigned</span>
@else
    <div class="flex items-center -space-x-2">
        @foreach ($people->take(4) as $person)
            <span class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold flex items-center justify-center ring-2 ring-white"
                title="{{ $person->full_name }}">
                {{ strtoupper(substr($person->first_name, 0, 1) . substr($person->last_name, 0, 1)) }}
            </span>
        @endforeach
        @if ($people->count() > 4)
            <span class="w-7 h-7 rounded-full bg-gray-100 text-gray-500 text-[10px] font-bold flex items-center justify-center ring-2 ring-white">
                +{{ $people->count() - 4 }}
            </span>
        @endif
    </div>
@endif
