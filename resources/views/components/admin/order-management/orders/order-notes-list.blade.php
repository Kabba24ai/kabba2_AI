@props(['notes'])
<div id="note-loading" class="hidden"></div>
<ul class="space-y-2">
    @forelse ($notes as $note)
        @php
            $createdBy = data_get($note, 'createdBy');
            $updatedBy = data_get($note, 'updatedBy');
            $createdAt = data_get($note, 'created_at');
            $updatedAt = data_get($note, 'updated_at');
            $createdByTypeName = data_get($note, 'created_by_type_name');
            $sourceLabel = data_get($note, 'source_label', 'Order Note');
            $contextLabel = data_get($note, 'context_label');
            $isEditable = data_get($note, 'is_editable', $createdByTypeName === 'User');
        @endphp
        <li class="list-disc border-b border-gray-200 pb-3">
            <div class="flex justify-between items-start">
                <div class="flex-1 pr-3">
                    <div class="mb-1 flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700">
                            {{ $sourceLabel }}
                        </span>
                        @if (filled($contextLabel))
                            <span class="text-[11px] text-gray-500">{{ $contextLabel }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-800 leading-relaxed text-justify font-semibold">
                        {{ $note->note }}
                    </p>

                    {{-- Metadata --}}
                    <div class="mt-1 space-y-1 text-xs text-gray-500">
                        @if ($createdAt)
                            <div>
                                Created {{ \App\Helpers\CustomHelper::formatDateTime($createdAt) }}
                                @if ($createdBy && $createdByTypeName === "User")
                                    {{-- If created by a user, show their name --}}
                                    by <span class="font-semibold">{{ $createdBy->full_name ?? 'Unknown' }}</span>
                                    {{-- ({{ $note->created_by_type_name ?? 'N/A' }}) --}}
                                @else
                                    by <span class="font-semibold">Order Page</span>
                                @endif
                            </div>
                        @endif
                        @if ($updatedAt && $updatedBy)
                            <div>
                                Updated {{ \App\Helpers\CustomHelper::formatDateTime($updatedAt) }}
                                @if ($updatedBy)
                                    by <span class="font-semibold">{{ $updatedBy->full_name ?? 'Unknown' }}</span>
                                    {{-- ({{ $note->updated_by_type_name ?? 'N/A' }}) --}}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                @if ($isEditable)
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
                @endif
            </div>
        </li>
    @empty
        <li class="list-disc text-gray-400 text-sm">No notes available.</li>
    @endforelse
</ul>
