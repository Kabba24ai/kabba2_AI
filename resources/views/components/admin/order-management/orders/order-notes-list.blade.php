@props(['notes'])
<div id="note-loading" class="hidden"></div>
<ul class="space-y-2">
    @forelse ($notes as $note)
        <li class="list-disc border-b border-gray-200 pb-3">
            <div class="flex justify-between items-start">
                <div class="flex-1 pr-3">
                    <p class="text-sm text-gray-800 leading-relaxed text-justify">
                        {{ $note->note }}
                        <span class="font-semibold">- {{ $note->user->full_name ?? 'N/A' }}</span>
                    </p>

                    {{-- Metadata --}}
                    <div class="mt-1 space-y-1 text-xs text-gray-500">
                        @if ($note->createdBy)
                            <div>
                                Created at: {{ \App\Helpers\CustomHelper::formatDateTime($note->created_at) }}
                                @if ($note->createdBy)
                                    by {{ $note->createdBy->full_name ?? 'Unknown' }}
                                    ({{ $note->created_by_type_name ?? 'N/A' }})
                                @endif
                            </div>
                        @endif
                        @if ($note->updatedBy)
                            <div>
                                Updated at: {{ \App\Helpers\CustomHelper::formatDateTime($note->updated_at) }}
                                @if ($note->updatedBy)
                                    by {{ $note->updatedBy->full_name ?? 'Unknown' }}
                                    ({{ $note->updated_by_type_name ?? 'N/A' }})
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 mt-1">
                    <button class="text-blue-500 hover:text-blue-700" title="Edit Note" data-note-id="{{ $note->id }}"
                        data-note-text="{{ e($note->note) }}" data-user-id="{{ $note->user_id }}">
                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                    </button>
                    <button class="text-red-500 hover:text-red-700" title="Delete Note"
                        data-note-id="{{ $note->id }}">
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </li>
    @empty
        <li class="list-disc text-gray-400 text-sm">No notes available.</li>
    @endforelse
</ul>
