@extends('admin.layouts.app')

@section('title', 'SMS Broadcast Queue')

@section('content')

@include('flash::message')

<div class="max-w-7xl mx-auto">

    {{-- Page Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">SMS Broadcast Queue</h2>
            <p class="text-sm text-gray-600 mt-1">Actual broadcast events — separate from the reusable Message Library. Completed broadcasts move to the Archive after 7 days.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.crm.message-management.index') }}"
                class="inline-flex items-center gap-2 border border-gray-300 bg-white text-gray-700 font-medium px-5 py-2.5 text-sm rounded-lg hover:bg-gray-50 transition">
                Message Library
            </a>
            <a href="{{ route('admin.crm.message-management.broadcast-wizard.create') }}"
                class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white font-medium px-5 py-2.5 text-sm rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                    <path d="M22 2L11 13" /><path d="M22 2L15 22L11 13L2 9L22 2Z" />
                </svg>
                New SMS Broadcast
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">View</label>
                <select name="view" onchange="this.form.submit()"
                    class="w-full text-sm px-3 py-2.5 border border-gray-300 rounded-md">
                    <option value="active" {{ $view === 'active' ? 'selected' : '' }}>Active &amp; Recent</option>
                    <option value="archive" {{ $view === 'archive' ? 'selected' : '' }}>Archive</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()"
                    class="w-full text-sm px-3 py-2.5 border border-gray-300 rounded-md">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Broadcast, message, or audience..."
                    class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md">
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto max-w-full rounded-2xl shadow border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
                <tr>
                    <th class="py-4 px-6 text-left">Broadcast</th>
                    <th class="py-4 px-6 text-left">Audience</th>
                    <th class="py-4 px-6 text-left whitespace-nowrap">Recipients</th>
                    <th class="py-4 px-6 text-left whitespace-nowrap">Created</th>
                    <th class="py-4 px-6 text-left whitespace-nowrap">Scheduled</th>
                    <th class="py-4 px-6 text-left whitespace-nowrap">Status</th>
                    <th class="py-4 px-6 text-left whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($events as $event)
                @php $status = $event->status; @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-4 px-6">
                        <div class="font-medium text-gray-900">{{ $event->name ?: 'Untitled draft' }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $event->message_name ?: '—' }}</div>
                    </td>
                    <td class="py-4 px-6 max-w-xs">
                        <div class="text-gray-700 truncate">{{ $event->audienceSummary() }}</div>
                    </td>
                    <td class="py-4 px-6 whitespace-nowrap">
                        @if ($status === \App\Enums\Communication\SmsBroadcastStatus::Draft)
                            <span class="text-gray-400">—</span>
                        @else
                            <span class="font-semibold text-gray-900">{{ $event->recipient_count }}</span>
                            @if ($event->sent_count || $event->failed_count)
                                <div class="text-xs text-gray-500 mt-0.5">{{ $event->sent_count }} sent · {{ $event->failed_count }} failed</div>
                            @endif
                        @endif
                    </td>
                    <td class="py-4 px-6 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ \App\Helpers\CustomHelper::formatDate($event->created_at) }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $event->createdBy?->full_name ?? '—' }}</div>
                    </td>
                    <td class="py-4 px-6 whitespace-nowrap text-gray-700">
                        {{ $event->scheduled_at ? $event->scheduled_at->format('M j, Y g:i A') : '—' }}
                    </td>
                    <td class="py-4 px-6 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $status->badgeClass() }}">
                            {{ $status->label() }}
                        </span>
                    </td>
                    <td class="py-4 px-6 whitespace-nowrap">
                        <div class="flex items-center gap-2 flex-wrap">

                            @if ($status === \App\Enums\Communication\SmsBroadcastStatus::Draft)
                                <a href="{{ route('admin.crm.message-management.broadcast-wizard.create', ['event' => $event->id]) }}"
                                    class="text-blue-600 text-xs font-medium hover:underline">Continue Wizard</a>
                                <form method="POST" action="{{ route('admin.crm.message-management.broadcast-queue.delete', $event->id) }}"
                                    onsubmit="return confirm('Delete this draft broadcast?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 text-xs font-medium hover:underline">Delete</button>
                                </form>

                            @elseif ($status === \App\Enums\Communication\SmsBroadcastStatus::AwaitingConfirmation)
                                <a href="{{ route('admin.crm.message-management.broadcast-queue.send', $event->id) }}"
                                    class="inline-flex items-center px-3 py-1.5 rounded-md bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700">Review &amp; Send</a>
                                <form method="POST" action="{{ route('admin.crm.message-management.broadcast-queue.rebuild', $event->id) }}"
                                    onsubmit="return confirm('Editing replaces the prepared broadcast package. The audience will be recalculated and final confirmation will be required again. Continue?')" class="inline">
                                    @csrf
                                    <button class="text-gray-600 text-xs font-medium hover:underline">Edit</button>
                                </form>
                                <form method="POST" action="{{ route('admin.crm.message-management.broadcast-queue.cancel', $event->id) }}"
                                    onsubmit="return confirm('Cancel this broadcast? It stays in history as Cancelled.')" class="inline">
                                    @csrf
                                    <button class="text-amber-600 text-xs font-medium hover:underline">Cancel</button>
                                </form>
                                <form method="POST" action="{{ route('admin.crm.message-management.broadcast-queue.delete', $event->id) }}"
                                    onsubmit="return confirm('Permanently delete this broadcast?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 text-xs font-medium hover:underline">Delete</button>
                                </form>

                            @elseif ($status === \App\Enums\Communication\SmsBroadcastStatus::Scheduled)
                                <a href="{{ route('admin.crm.message-management.broadcast-queue.show', $event->id) }}"
                                    class="text-blue-600 text-xs font-medium hover:underline">View</a>
                                <a href="{{ route('admin.crm.message-management.broadcast-queue.send', $event->id) }}"
                                    class="text-indigo-600 text-xs font-medium hover:underline">Change Schedule</a>
                                <form method="POST" action="{{ route('admin.crm.message-management.broadcast-queue.rebuild', $event->id) }}"
                                    onsubmit="return confirm('Editing replaces the prepared broadcast package and removes the schedule. The audience will be recalculated and final confirmation will be required again. Continue?')" class="inline">
                                    @csrf
                                    <button class="text-gray-600 text-xs font-medium hover:underline">Edit</button>
                                </form>
                                <form method="POST" action="{{ route('admin.crm.message-management.broadcast-queue.cancel', $event->id) }}"
                                    onsubmit="return confirm('Cancel this scheduled broadcast? It stays in history as Cancelled.')" class="inline">
                                    @csrf
                                    <button class="text-amber-600 text-xs font-medium hover:underline">Cancel</button>
                                </form>
                                <form method="POST" action="{{ route('admin.crm.message-management.broadcast-queue.delete', $event->id) }}"
                                    onsubmit="return confirm('PERMANENTLY delete this scheduled broadcast before it sends? This cannot be undone.')" class="inline">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="destructive" value="1">
                                    <button class="text-red-600 text-xs font-medium hover:underline">Delete</button>
                                </form>

                            @elseif ($status === \App\Enums\Communication\SmsBroadcastStatus::Sending)
                                <a href="{{ route('admin.crm.message-management.broadcast-queue.show', $event->id) }}"
                                    class="text-blue-600 text-xs font-medium hover:underline">View Progress</a>

                            @else {{-- Sent / Partially Sent / Failed / Cancelled --}}
                                <a href="{{ route('admin.crm.message-management.broadcast-queue.show', $event->id) }}"
                                    class="text-blue-600 text-xs font-medium hover:underline">View Results</a>
                                @if (!$event->archived_at)
                                    <form method="POST" action="{{ route('admin.crm.message-management.broadcast-queue.archive', $event->id) }}" class="inline">
                                        @csrf
                                        <button class="text-gray-600 text-xs font-medium hover:underline">Archive</button>
                                    </form>
                                @endif
                                @if ($event->sms_broadcast_id && $status !== \App\Enums\Communication\SmsBroadcastStatus::Cancelled)
                                    <a href="{{ route('admin.crm.message-management.broadcast-wizard.create', ['message' => $event->sms_broadcast_id]) }}"
                                        class="text-green-700 text-xs font-medium hover:underline">Send Again</a>
                                @endif
                            @endif

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-500">
                        @if ($view === 'archive')
                            No archived broadcasts yet.
                        @else
                            No broadcasts in the queue. Create one with <b>New SMS Broadcast</b>.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $events->links() }}
    </div>

</div>
@endsection
