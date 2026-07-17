@extends('admin.layouts.app')

@section('title', 'Wait List #' . $waitList->id)

@push('css')
<style> main { background-color: #f8fafc; flex: 1 1 auto; } </style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\WaitList\WaitListCommunicationType;
        use App\Enums\WaitList\WaitListStatus;
        $isOpen = in_array($waitList->status->value, WaitListStatus::waiting(), true);
        $inputClass = 'w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white';
    @endphp

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-semibold text-gray-900">Wait List #{{ $waitList->id }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $waitList->status->color() }}">
                        {{ $waitList->status->label() }}
                    </span>
                    @if ($waitList->priority_override)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">
                            Priority {{ $waitList->priority_override }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    Created {{ $waitList->created_at->format('M j, Y g:i A') }}
                    @if ($waitList->createdBy) by {{ $waitList->createdBy->full_name }} @endif
                    · Waiting {{ $waitList->age_days }} {{ Str::plural('day', $waitList->age_days) }}
                </p>
            </div>
            <a href="{{ route('admin.wait-list.index') }}"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
                <x-heroicon-o-arrow-left class="w-4 h-4" /> Wait List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-start">
        <div class="lg:col-span-2 space-y-6">

            {{-- Record details --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Customer &amp; Demand</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Customer</dt>
                        <dd class="font-medium text-gray-900">
                            @if ($waitList->customer)
                                <a href="{{ route('admin.crm.customers.view', $waitList->customer->unique_id) }}" class="text-sky-600 hover:underline">
                                    {{ $waitList->customer_name }}
                                </a>
                            @else
                                {{ $waitList->customer_name }}
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Company</dt>
                        <dd class="font-medium text-gray-900">{{ $waitList->company_name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Phone</dt>
                        <dd class="font-medium text-gray-900">{{ $waitList->phone ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Email</dt>
                        <dd class="font-medium text-gray-900">{{ $waitList->email ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Request Type</dt>
                        <dd class="font-medium text-gray-900">{{ $waitList->request_type->label() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Waiting For</dt>
                        <dd class="font-medium text-gray-900 text-right">{{ $waitList->demandLabel() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Store Preference</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $waitList->store_preference->label() }}
                            @if ($waitList->store) — {{ $waitList->store->store_name }} @endif
                        </dd>
                    </div>
                    @if ($waitList->convertedOrder)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Converted To</dt>
                            <dd class="font-medium text-gray-900">
                                {!! $waitList->convertedOrder->view_link ?? $waitList->convertedOrder->order_number !!}
                                <span class="text-xs text-gray-400">{{ $waitList->converted_at?->format('M j, Y') }}</span>
                            </dd>
                        </div>
                    @endif
                    @if ($waitList->cancelled_at)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Cancelled</dt>
                            <dd class="text-gray-700">{{ $waitList->cancelled_at->format('M j, Y') }} by {{ $waitList->cancelledBy?->full_name ?? '—' }}</dd>
                        </div>
                    @endif
                </dl>
                @if ($waitList->items->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">
                            Acceptable Equipment ({{ $waitList->items->count() }} {{ Str::plural('unit', $waitList->items->count()) }})
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($waitList->items as $item)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    <span class="font-bold">{{ $item->equipment?->equipment_id ?? "#{$item->equipment_id}" }}</span>
                                    <span>{{ $item->equipment?->equipment_name }}</span>
                                    <span class="text-blue-400">· {{ $item->equipment?->current_status?->label() ?? '—' }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @elseif ($waitList->selectedProducts->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide mb-2">
                            Legacy Product Selections — re-select actual equipment units
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($waitList->selectedProducts as $product)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-100">
                                    {{ $product->product_name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Reason</p>
                        <p class="text-gray-700">{{ $waitList->reason ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Internal Notes</p>
                        <p class="text-gray-700">{{ $waitList->internal_notes ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Communication history --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Communication History</h2>

                <form method="POST" action="{{ route('admin.wait-list.communications.store', $waitList) }}"
                    class="flex flex-wrap items-end gap-3 mb-4 pb-4 border-b border-gray-100">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                        <select name="type" required class="border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                            @foreach (WaitListCommunicationType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Note</label>
                        <input type="text" name="note" placeholder="What happened?" class="{{ $inputClass }}">
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                        Log Communication
                    </button>
                </form>

                @if ($waitList->communications->isEmpty())
                    <p class="text-sm text-gray-400 italic">No communications logged yet.</p>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($waitList->communications as $entry)
                            <div class="py-2.5">
                                <p class="text-sm">
                                    <span class="font-semibold text-gray-800">{{ $entry->type->label() }}</span>
                                    @if ($entry->note) <span class="text-gray-600">— {{ $entry->note }}</span> @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $entry->user?->full_name ?? '—' }} · {{ $entry->created_at->format('M j, Y g:i A') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Alert history --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Match Alert History</h2>
                @if ($waitList->alerts->isEmpty())
                    <p class="text-sm text-gray-400 italic">No matches fired yet.</p>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($waitList->alerts as $alert)
                            <div class="py-2.5 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ $alert->equipment?->equipment_name ?? "Equipment #{$alert->equipment_id}" }}
                                        <span class="font-normal text-gray-500">— {{ $alert->match_type->label() }} match</span>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ $alert->created_at->format('M j, Y g:i A') }}
                                        @if ($alert->acknowledged_at) · Acknowledged by {{ $alert->acknowledgedBy?->full_name }} {{ $alert->acknowledged_at->format('M j g:i A') }} @endif
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $alert->status->color() }}">
                                    {{ $alert->status->label() }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Right column: manual dispositions --}}
        <div class="space-y-6">
            @if ($isOpen && $waitList->isAcceptedAwaitingConversion())
                <div class="rounded-xl border-2 border-emerald-300 bg-emerald-50 p-4">
                    <p class="text-sm font-semibold text-emerald-800 flex items-center gap-2">
                        <x-heroicon-o-check-circle class="w-5 h-5" />
                        Accepted — Awaiting Conversion
                    </p>
                    <p class="text-xs text-emerald-700 mt-1">
                        The customer said yes, but the rental is not secured until the order is created
                        and linked below. This record stays in the active queue until then.
                    </p>
                </div>
            @endif
            @if ($isOpen)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 mb-3">Convert to Rental</h2>
                    <p class="text-xs text-gray-400 mb-3">
                        Create the reservation/rental order in Orders as usual, then link it here. Nothing is created automatically.
                    </p>
                    <form method="POST" action="{{ route('admin.wait-list.convert', $waitList) }}" class="space-y-2">
                        @csrf
                        <select name="converted_order_id" required class="{{ $inputClass }}">
                            <option value="">— Select order —</option>
                            @foreach ($orders as $order)
                                <option value="{{ $order->id }}">{{ $order->order_number }} — {{ $order->customer_name }}</option>
                            @endforeach
                        </select>
                        @error('converted_order_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                            Mark Converted
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 mb-3">Cancel Wait List</h2>
                    <p class="text-xs text-gray-400 mb-3">Manual only — the record stays searchable in history.</p>
                    <form method="POST" action="{{ route('admin.wait-list.cancel', $waitList) }}"
                        onsubmit="return confirm('Cancel this wait list record?');">
                        @csrf
                        <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-medium border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition">
                            Cancel Wait List
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

@endsection
