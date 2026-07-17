@extends('admin.layouts.app')

@section('title', 'Broadcast — ' . $event->name)

@section('content')

@include('flash::message')

<div class="max-w-5xl mx-auto">

    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-semibold text-gray-900">{{ $event->name }}</h2>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $event->status->badgeClass() }}">
                    {{ $event->status->label() }}
                </span>
                @if ($event->archived_at)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">Archived</span>
                @endif
            </div>
            <p class="text-sm text-gray-600 mt-1">
                Created by {{ $event->createdBy?->full_name ?? '—' }} on {{ $event->created_at->format('M j, Y g:i A') }}
            </p>
        </div>
        <a href="{{ route('admin.crm.message-management.broadcast-queue.index') }}"
            class="inline-flex items-center gap-2 border border-gray-300 bg-white text-gray-700 font-medium px-5 py-2.5 text-sm rounded-lg hover:bg-gray-50 transition">
            ← Broadcast Queue
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Frozen message --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Frozen Message Content</h4>
                <p class="text-sm mb-1"><span class="text-gray-600">Source message:</span> <span class="font-medium text-gray-900">{{ $event->message_name ?? '—' }}</span></p>
                <p class="text-sm mb-3"><span class="text-gray-600">Category:</span> <span class="font-medium text-gray-900">{{ $event->category_name ?? '—' }}</span></p>
                <div class="border border-gray-200 bg-gray-50 rounded-md p-4 text-sm text-gray-800 whitespace-pre-wrap">{{ $event->message_content }}</div>
                <p class="text-xs text-gray-500 mt-2">{{ strlen((string) $event->message_content) }} characters — content is frozen; editing the library message never changes this record.</p>
            </div>

            {{-- Results --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Recipient Results</h4>
                <div class="grid grid-cols-3 gap-4 text-center mb-4">
                    <div class="border border-gray-200 rounded-lg py-3">
                        <div class="text-2xl font-bold text-gray-900">{{ $event->recipient_count }}</div>
                        <div class="text-xs text-gray-500 mt-1">Frozen Recipients</div>
                    </div>
                    <div class="border border-green-200 bg-green-50 rounded-lg py-3">
                        <div class="text-2xl font-bold text-green-700">{{ $recipientSummary['sent'] ?? 0 }}</div>
                        <div class="text-xs text-green-700 mt-1">Sent</div>
                    </div>
                    <div class="border border-red-200 bg-red-50 rounded-lg py-3">
                        <div class="text-2xl font-bold text-red-700">{{ $recipientSummary['failed'] ?? 0 }}</div>
                        <div class="text-xs text-red-700 mt-1">Failed</div>
                    </div>
                </div>

                @if ($failedRecipients->isNotEmpty())
                    <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Failed Recipients</h5>
                    <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 text-sm max-h-64 overflow-y-auto">
                        @foreach ($failedRecipients as $recipient)
                            <div class="px-4 py-2.5 flex justify-between gap-4">
                                <span class="text-gray-800">{{ $recipient->customer_name ?: $recipient->phone }} <span class="text-gray-400">{{ $recipient->phone }}</span></span>
                                <span class="text-red-600 text-xs truncate max-w-[220px]" title="{{ $recipient->error_message }}">{{ $recipient->error_message }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Audience Definition</h4>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Type</dt><dd class="font-medium text-gray-800">{{ $event->audience_type === 'all' ? 'All eligible recipients' : 'CRM tag segmentation' }}</dd></div>
                    @if ($event->audience_type !== 'all')
                        <div class="flex justify-between"><dt class="text-gray-500">Positive mode</dt><dd class="font-medium text-gray-800">Match {{ strtoupper($event->positive_mode ?? 'any') }}</dd></div>
                    @endif
                    <div><dt class="text-gray-500 mb-1">Included tags</dt><dd class="text-gray-800">{{ implode(', ', $event->include_tag_names ?? []) ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500 mb-1">Excluded tags</dt><dd class="text-gray-800">{{ implode(', ', $event->exclude_tag_names ?? []) ?: '—' }}</dd></div>
                    @if ($event->audience_description)
                        <div class="pt-2 border-t border-gray-100 text-xs text-gray-500">{{ $event->audience_description }}</div>
                    @endif
                </dl>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Timeline</h4>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Created</dt><dd class="text-gray-800">{{ $event->created_at->format('M j, g:i A') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Queued</dt><dd class="text-gray-800">{{ $event->queued_at?->format('M j, g:i A') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Scheduled for</dt><dd class="text-gray-800">{{ $event->scheduled_at?->format('M j, g:i A') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Sending started</dt><dd class="text-gray-800">{{ $event->sending_started_at?->format('M j, g:i A') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Completed</dt><dd class="text-gray-800">{{ $event->completed_at?->format('M j, g:i A') ?? '—' }}</dd></div>
                    @if ($event->cancelled_at)
                        <div class="flex justify-between"><dt class="text-gray-500">Cancelled</dt><dd class="text-gray-800">{{ $event->cancelled_at->format('M j, g:i A') }}</dd></div>
                    @endif
                </dl>
            </div>

            @if ($event->status === \App\Enums\Communication\SmsBroadcastStatus::Scheduled)
                <div class="bg-white border border-blue-200 rounded-xl shadow-sm p-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Scheduled</h4>
                    <p class="text-sm text-gray-700 mb-3">This broadcast sends {{ $event->scheduled_at->format('M j, Y \a\t g:i A') }}.</p>
                    <a href="{{ route('admin.crm.message-management.broadcast-queue.send', $event->id) }}"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Change Schedule</a>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
