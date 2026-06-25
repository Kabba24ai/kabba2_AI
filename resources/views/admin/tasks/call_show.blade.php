@extends('admin.layouts.app')

@section('title', 'Call Reminder Detail')

@section('content')

@include('flash::message')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.tasks.index') }}" class="text-gray-500 hover:text-gray-700">
        <x-heroicon-o-arrow-left class="w-5 h-5" />
    </a>
    <h3 class="text-xl font-semibold text-gray-800">Call Reminder Detail</h3>
</div>

@php
    $contactType = $callReminder->customer_id ? 'customer' : ($callReminder->supplier_id ? 'supplier' : 'other');
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Main --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Call Card --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-700">
                            <x-heroicon-o-phone class="w-3 h-3" />
                            Call Reminder
                        </span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $callReminder->priority->color() }} border border-transparent">
                            {{ $callReminder->priority->label() }}
                        </span>
                        @if ($callReminder->status === 'active')
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-700">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-500">
                                {{ ucfirst($callReminder->status) }}
                            </span>
                        @endif
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{ ucwords(str_replace('_', ' ', $callReminder->reason)) }}
                    </h2>
                </div>
                <div class="ml-4 flex gap-2">
                    <button
                        type="button"
                        onclick="viewCallNeeded({{ $callReminder->id }})"
                        class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                        Edit
                    </button>
                    <button
                        type="button"
                        onclick="openCompleteCallModal({{ $callReminder->id }})"
                        class="inline-flex items-center rounded-md border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
                        Complete Call
                    </button>
                </div>
            </div>

            @if ($callReminder->notes)
                <div class="mt-3 border-l-4 border-blue-200 bg-blue-50/40 rounded-r-lg p-4">
                    <p class="text-sm font-medium text-gray-500 mb-1">Notes</p>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $callReminder->notes }}</p>
                </div>
            @else
                <p class="text-sm text-gray-400 italic">No notes provided.</p>
            @endif
        </div>

        {{-- Activity Log --}}
        @if ($callReminder->activities->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Call Activity History</h4>
            <div class="space-y-4">
                @foreach ($callReminder->activities as $activity)
                    <div class="relative pl-6 pb-4 border-l-2 border-blue-200">
                        <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-blue-500 border-4 border-white shadow"></div>
                        <div class="bg-gray-50 rounded-lg border border-gray-100 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                    {{ ucwords(str_replace('_', ' ', $activity->status)) }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    {{ $activity->user?->full_name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }}
                                </span>
                            </div>
                            @if ($activity->notes)
                                <p class="text-sm text-gray-700 leading-relaxed">{{ $activity->notes }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">

        {{-- Contact --}}
        <div class="bg-white rounded-lg border border-blue-200 shadow-sm p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <x-heroicon-o-phone class="w-4 h-4 text-red-500" />
                Contact
            </h4>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Type</dt>
                    <dd class="font-medium text-gray-800">
                        @if ($contactType === 'customer') Customer
                        @elseif ($contactType === 'supplier') Supplier
                        @else Other
                        @endif
                    </dd>
                </div>

                @if ($contactType === 'customer' && $callReminder->customer)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Name</dt>
                        <dd class="font-medium text-gray-800 text-right">{{ $callReminder->customer->full_name }}</dd>
                    </div>
                    @if ($callReminder->customer->phone)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Phone</dt>
                            <dd class="font-medium text-gray-800">{{ \App\Helpers\CustomHelper::formatPhone($callReminder->customer->phone) }}</dd>
                        </div>
                    @endif
                    @if ($callReminder->customer->email)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Email</dt>
                            <dd class="font-medium text-gray-800 text-right text-xs">{{ $callReminder->customer->email }}</dd>
                        </div>
                    @endif

                @elseif ($contactType === 'supplier' && $callReminder->supplier)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Name</dt>
                        <dd class="font-medium text-gray-800 text-right">{{ $callReminder->supplier->name }}</dd>
                    </div>
                    @if ($callReminder->supplier->phone)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Phone</dt>
                            <dd class="font-medium text-gray-800">{{ \App\Helpers\CustomHelper::formatPhone($callReminder->supplier->phone) }}</dd>
                        </div>
                    @endif
                    @if ($callReminder->supplier->primary_contact_name)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Contact</dt>
                            <dd class="font-medium text-gray-800">{{ $callReminder->supplier->primary_contact_name }}</dd>
                        </div>
                    @endif

                @else
                    @if ($callReminder->contact_name)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Name</dt>
                            <dd class="font-medium text-gray-800">{{ $callReminder->contact_name }}</dd>
                        </div>
                    @endif
                    @if ($callReminder->contact_phone)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Phone</dt>
                            <dd class="font-medium text-gray-800">{{ \App\Helpers\CustomHelper::formatPhone($callReminder->contact_phone) }}</dd>
                        </div>
                    @endif
                    @if ($callReminder->contact_email)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Email</dt>
                            <dd class="font-medium text-gray-800 text-xs text-right">{{ $callReminder->contact_email }}</dd>
                        </div>
                    @endif
                @endif
            </dl>
        </div>

        {{-- Details --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Details</h4>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Assigned To</dt>
                    <dd class="font-medium text-gray-800">{{ $callReminder->assignee?->full_name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Created By</dt>
                    <dd class="font-medium text-gray-800">{{ $callReminder->creator?->full_name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Created</dt>
                    <dd class="font-medium text-gray-800">{{ $callReminder->created_at->format('M j, Y g:i A') }}</dd>
                </div>
                @if ($callReminder->follow_up_at)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Follow-up At</dt>
                        <dd class="font-medium text-amber-700">{{ $callReminder->follow_up_at->format('M j, Y g:i A') }}</dd>
                    </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500">Priority</dt>
                    <dd>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $callReminder->priority->color() }}">
                            {{ $callReminder->priority->label() }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

    </div>
</div>

{{-- Modals needed for Edit and Complete actions --}}
<script>window.taskManagerMode = true;</script>
@include('admin.dashboard.partials._call_needed_modal')

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-open in edit mode if user navigated here via "Edit" from call list
    // (edit button on this page uses viewCallNeeded() which opens the modal)
});
</script>
@endpush

@endsection
