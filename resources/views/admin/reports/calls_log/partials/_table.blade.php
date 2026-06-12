@if($calls->isEmpty())
    <div class="text-center py-16 text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto w-10 h-10 mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
        </svg>
        <p class="text-sm">No call log records found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($calls as $call)
            @php
                $customerName  = $call->customer?->full_name ?? $call->contact_name ?? '—';
                $customerPhone = $call->customer?->phone     ?? $call->contact_phone;
                $customerEmail = $call->customer?->email     ?? $call->contact_email;
                $assigneeName  = $call->assignee?->full_name ?? $call->assignee?->name ?? '—';
                $creatorName   = $call->creator?->full_name  ?? $call->creator?->name  ?? '—';
            @endphp

            <div class="border border-gray-200 rounded-xl bg-white p-4 hover:shadow-md transition">
                <div class="flex justify-between gap-4">

                    <div class="flex-1 min-w-0">

                        {{-- Header row: name, phone, email, badges --}}
                        <div class="flex items-center gap-2 flex-wrap">

                            <span class="text-sm font-medium text-gray-500">
                                {{ $call->customer_id ? 'Customer:' : 'Contact Person:' }}
                            </span>

                            <h3 class="text-sm font-semibold text-gray-900">
                                {{ $customerName }}
                            </h3>

                            @if($customerPhone)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-50 text-gray-600 text-xs">
                                    <x-heroicon-o-phone class="w-3.5 h-3.5" />
                                    {{ \App\Helpers\CustomHelper::formatPhone($customerPhone) }}
                                </span>
                            @endif

                            @if($customerEmail)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-50 text-gray-600 text-xs">
                                    <x-heroicon-o-envelope class="w-3.5 h-3.5" />
                                    {{ $customerEmail }}
                                </span>
                            @endif

                            @if($call->is_urgent)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold border border-red-200">
                                    <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" />
                                    Urgent
                                </span>
                            @endif

                            @if($call->status === 'clear')
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold border border-green-200">
                                    <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                                    Completed
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-200">
                                    Active
                                </span>
                            @endif

                        </div>

                        {{-- Reason / Notes block --}}
                        <div class="mt-3 border-l-4 border-blue-200 bg-blue-50/40 rounded-r-lg p-3">
                            <div class="text-sm flex flex-wrap items-center gap-x-4 gap-y-1">

                                <span>
                                    <span class="font-semibold text-gray-700">Reason:</span>
                                    <span class="text-gray-600">
                                        {{ ucwords(str_replace('_', ' ', $call->reason)) }}
                                    </span>
                                </span>

                                @if($call->notes)
                                    <span>
                                        <span class="font-semibold text-gray-700">Notes:</span>
                                        <span class="text-gray-600">{{ $call->notes }}</span>
                                    </span>
                                @endif

                            </div>
                        </div>

                        {{-- Footer: assigned to, created by, date --}}
                        <div class="flex flex-wrap items-center gap-4 mt-4 text-xs text-gray-500">

                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-user class="w-3.5 h-3.5" />
                                <span class="font-medium">Assigned To:</span>
                                {{ $assigneeName }}
                            </span>

                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-user-plus class="w-3.5 h-3.5" />
                                <span class="font-medium">Created By:</span>
                                {{ $creatorName }}
                            </span>

                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                                {{ \App\Helpers\CustomHelper::formatDateTime($call->created_at) }}
                            </span>

                        </div>

                        {{-- Action Taken (most recent activity, inline) --}}
                        @if($call->activities->isNotEmpty())
                            @php $latest = $call->activities->sortByDesc('created_at')->first(); @endphp
                            <div class="mt-2 rounded-lg bg-green-50 border border-green-100 p-3">
                                <div class="text-xs font-semibold text-green-700 mb-1 uppercase tracking-wide">Action Taken</div>
                                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-semibold">
                                        {{ ucwords(str_replace('_', ' ', $latest->status)) }}
                                    </span>
                                    <span>by <strong>{{ $latest->user?->full_name ?? '—' }}</strong></span>
                                    <span>on {{ \App\Helpers\CustomHelper::formatDateTime($latest->created_at) }}</span>
                                </div>
                                @if($latest->notes)
                                    <p class="mt-1.5 text-sm text-gray-700 leading-relaxed">{{ $latest->notes }}</p>
                                @endif
                            </div>
                        @endif

                        {{-- Activity timeline (collapsible, for full history) --}}
                        <div
                            id="call-activities-{{ $call->id }}"
                            class="hidden overflow-hidden transition-all duration-300 ease-in-out"
                            style="max-height:0">

                            @if($call->activities->isNotEmpty())
                                <div class="mt-4 ml-3 border-l-2 border-blue-200 pl-6">
                                    @foreach($call->activities as $activity)
                                        <div class="relative pb-6">

                                            <div class="absolute -left-[33px] top-1 w-5 h-5 rounded-full bg-blue-500 border-4 border-white shadow"></div>

                                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">

                                                <div class="flex items-start justify-between gap-4">
                                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                        {{ ucwords(str_replace('_', ' ', $activity->status)) }}
                                                    </span>
                                                    <div class="text-xs text-gray-500">
                                                        Updated by
                                                        <span class="font-medium text-gray-700">
                                                            {{ $activity->user?->full_name ?? $activity->user?->name ?? '—' }}
                                                        </span>
                                                        on {{ \App\Helpers\CustomHelper::formatDateTime($activity->created_at) }}
                                                    </div>
                                                </div>

                                                @if($activity->notes)
                                                    <div class="mt-3 rounded-lg bg-gray-50 border border-gray-100 p-3">
                                                        <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-1">Notes</div>
                                                        <p class="text-sm text-gray-700 leading-relaxed">{{ $activity->notes }}</p>
                                                    </div>
                                                @endif

                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-3 text-xs text-gray-400 italic">No activity recorded yet.</p>
                            @endif

                        </div>

                    </div>

                    {{-- Actions column --}}
                    <div class="flex flex-col items-center gap-1 border-l border-gray-100 pl-3 shrink-0">

                        <div class="flex items-center gap-1">
                            <button
                                onclick="viewCallNeeded({{ $call->id }})"
                                class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition"
                                title="Edit">
                                <x-heroicon-o-pencil-square class="w-5 h-5" />
                            </button>

                            @if($call->status === 'active')
                                <button
                                    onclick="openCompleteCallModal({{ $call->id }})"
                                    class="p-2 rounded-lg text-green-600 hover:bg-green-50 transition"
                                    title="Mark as Complete">
                                    <x-heroicon-o-check-circle class="w-5 h-5" />
                                </button>
                            @else
                                <span
                                    class="p-2 text-gray-300 cursor-default"
                                    title="Already completed">
                                    <x-heroicon-o-check-circle class="w-5 h-5" />
                                </span>
                            @endif
                        </div>

                        <button
                            onclick="toggleCallActivities({{ $call->id }})"
                            class="text-xs font-medium text-blue-600 hover:text-blue-800 whitespace-nowrap">
                            Activity ({{ $call->activities->count() }})
                        </button>

                    </div>

                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $calls->links() }}
    </div>
@endif
