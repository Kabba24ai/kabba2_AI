@extends('admin.layouts.app')

@section('title', 'Settlement — ' . $ticket->ticket_number)

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\Service\ServiceChargeType;
        use App\Enums\Service\ServiceMediaCategory;
        $chargeCreated = (bool) $settlement;
        $inputClass = 'w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm bg-white';
    @endphp

    {{-- ===== Header ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-semibold text-gray-900">Customer Settlement</h1>
                    <span class="text-lg text-gray-400 font-medium">{{ $ticket->ticket_number }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->repair_status->color() }}">
                        {{ $ticket->repair_status->label() }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                        {{ $ticket->financial_status->label() }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    {{ $ticket->customer ? trim($ticket->customer->first_name . ' ' . $ticket->customer->last_name) : 'No customer' }}
                    @if ($ticket->order) · Order {{ $ticket->order->order_number }} @endif
                    @if ($ticket->equipment) · {{ $ticket->equipment->equipment_name }} @endif
                </p>
            </div>
            <a href="{{ route('admin.service-management.tickets.show', $ticket) }}"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
                <x-heroicon-o-arrow-left class="w-4 h-4" />
                Back to Ticket
            </a>
        </div>
    </div>

    @if ($chargeCreated)
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-5 mb-6">
            <div class="flex items-start gap-3">
                <x-heroicon-o-check-circle class="w-6 h-6 text-emerald-500 mt-0.5 shrink-0" />
                <div>
                    <p class="text-sm font-semibold text-emerald-800">Customer charge already created for this ticket.</p>
                    <p class="text-sm text-emerald-700 mt-1">
                        ${{ number_format($settlement->final_amount, 2) }} on {{ $settlement->created_at->format('M j, Y g:i A') }}
                        @if ($settlement->extraCharge) · Reference {{ $settlement->extraCharge->unique_id }} @endif
                        — billing and payment are handled on the order. The preview below is read-only.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-start">
        <div class="lg:col-span-2 space-y-6">

            {{-- ===== Labor ===== --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Labor</h2>
                    <span class="text-sm font-semibold text-gray-700">${{ number_format($package->laborTotal(), 2) }}</span>
                </div>
                @if ($ticket->laborEntries->isEmpty())
                    <p class="text-sm text-gray-400 italic">No labor entries.</p>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($ticket->laborEntries as $entry)
                            <div class="py-2.5">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium {{ $entry->billable ? 'text-gray-800' : 'text-gray-400 line-through' }}">
                                            {{ $entry->employee?->full_name ?? 'Unassigned' }}
                                            <span class="font-normal text-gray-500">
                                                — {{ rtrim(rtrim(number_format($entry->hours, 2), '0'), '.') }}h
                                                @if ($entry->labor_rate !== null) × ${{ number_format($entry->labor_rate, 2) }} @endif
                                            </span>
                                        </p>
                                        @if ($entry->labor_description)
                                            <p class="text-xs text-gray-400 truncate">{{ $entry->labor_description }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        @unless ($entry->billable)
                                            <span class="text-[11px] font-semibold text-gray-400 uppercase">Non-billable</span>
                                        @endunless
                                        <span class="text-sm font-medium text-gray-900">{{ $entry->labor_total !== null ? '$' . number_format($entry->labor_total, 2) : '—' }}</span>
                                        @unless ($chargeCreated)
                                            <details class="relative">
                                                <summary class="text-xs font-medium text-blue-600 cursor-pointer select-none">Edit</summary>
                                                <form method="POST" action="{{ route('admin.service-management.tickets.labor.update', [$ticket, $entry]) }}"
                                                    class="absolute right-0 z-10 mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-2">
                                                    @csrf @method('PUT')
                                                    <input type="hidden" name="from" value="settlement">
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[11px] text-gray-500 mb-0.5">Date</label>
                                                            <input type="date" name="labor_date" value="{{ $entry->labor_date->toDateString() }}" required class="{{ $inputClass }}">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[11px] text-gray-500 mb-0.5">Hours</label>
                                                            <input type="number" name="hours" step="0.25" min="0.01" value="{{ $entry->hours }}" class="{{ $inputClass }}">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[11px] text-gray-500 mb-0.5">Rate ($/hr)</label>
                                                            <input type="number" name="labor_rate" step="0.01" min="0" value="{{ $entry->labor_rate }}" class="{{ $inputClass }}">
                                                        </div>
                                                        <label class="inline-flex items-end gap-1.5 pb-1.5 text-sm text-gray-700">
                                                            <input type="hidden" name="billable" value="0">
                                                            <input type="checkbox" name="billable" value="1" @checked($entry->billable) class="rounded border-gray-300">
                                                            Billable
                                                        </label>
                                                    </div>
                                                    <button type="submit" class="w-full px-3 py-1.5 rounded-md text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700">Save</button>
                                                </form>
                                            </details>
                                            <form method="POST" action="{{ route('admin.service-management.tickets.labor.destroy', [$ticket, $entry]) }}"
                                                onsubmit="return confirm('Remove this labor entry?');">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="from" value="settlement">
                                                <button type="submit" class="p-1 text-gray-300 hover:text-red-500" title="Remove"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                            </form>
                                        @endunless
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @unless ($chargeCreated)
                    <details class="mt-3 pt-3 border-t border-gray-100">
                        <summary class="text-sm font-medium text-blue-600 cursor-pointer select-none">+ Add Labor</summary>
                        <form method="POST" action="{{ route('admin.service-management.tickets.labor.store', $ticket) }}" class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                            @csrf
                            <input type="hidden" name="from" value="settlement">
                            <input type="date" name="labor_date" value="{{ now()->toDateString() }}" required class="{{ $inputClass }}">
                            <input type="number" name="hours" step="0.25" min="0.01" placeholder="Hours" class="{{ $inputClass }}">
                            <input type="number" name="labor_rate" step="0.01" min="0" placeholder="Rate $/hr" class="{{ $inputClass }}">
                            <button type="submit" class="px-3 py-1.5 rounded-md text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700">Add</button>
                            <input type="text" name="labor_description" placeholder="Description (optional)" class="{{ $inputClass }} col-span-2 sm:col-span-4">
                        </form>
                    </details>
                @endunless
            </div>

            {{-- ===== Parts ===== --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Parts</h2>
                    <span class="text-sm font-semibold text-gray-700">${{ number_format($package->partsTotal(), 2) }}</span>
                </div>
                @if ($ticket->partsUsed->isEmpty())
                    <p class="text-sm text-gray-400 italic">No parts recorded.</p>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($ticket->partsUsed as $part)
                            <div class="py-2.5 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium {{ $part->billable ? 'text-gray-800' : 'text-gray-400 line-through' }}">
                                        {{ $part->description }}
                                        @if ($part->part_number)<span class="font-mono text-xs text-gray-400"> ({{ $part->part_number }})</span>@endif
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Qty {{ $part->quantityLabel() }}
                                        @if ($part->customer_price !== null) × ${{ number_format($part->customer_price, 2) }} @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    @unless ($part->billable)
                                        <span class="text-[11px] font-semibold text-gray-400 uppercase">Non-billable</span>
                                    @endunless
                                    <span class="text-sm font-medium text-gray-900">{{ $part->customer_total !== null ? '$' . number_format($part->customer_total, 2) : '—' }}</span>
                                    @unless ($chargeCreated)
                                        <details class="relative">
                                            <summary class="text-xs font-medium text-blue-600 cursor-pointer select-none">Edit</summary>
                                            <form method="POST" action="{{ route('admin.service-management.tickets.parts.update', [$ticket, $part]) }}"
                                                class="absolute right-0 z-10 mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-2">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="from" value="settlement">
                                                <input type="text" name="description" value="{{ $part->description }}" required class="{{ $inputClass }}">
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input type="number" name="quantity" step="0.01" min="0.01" value="{{ $part->quantity }}" class="{{ $inputClass }}" placeholder="Qty">
                                                    <input type="number" name="customer_price" step="0.01" min="0" value="{{ $part->customer_price }}" class="{{ $inputClass }}" placeholder="Customer $">
                                                </div>
                                                <label class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                                                    <input type="hidden" name="billable" value="0">
                                                    <input type="checkbox" name="billable" value="1" @checked($part->billable) class="rounded border-gray-300">
                                                    Billable
                                                </label>
                                                <button type="submit" class="w-full px-3 py-1.5 rounded-md text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700">Save</button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('admin.service-management.tickets.parts.destroy', [$ticket, $part]) }}"
                                            onsubmit="return confirm('Remove this part?');">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="from" value="settlement">
                                            <button type="submit" class="p-1 text-gray-300 hover:text-red-500" title="Remove"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @unless ($chargeCreated)
                    <details class="mt-3 pt-3 border-t border-gray-100">
                        <summary class="text-sm font-medium text-blue-600 cursor-pointer select-none">+ Add Part</summary>
                        <form method="POST" action="{{ route('admin.service-management.tickets.parts.store', $ticket) }}" class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                            @csrf
                            <input type="hidden" name="from" value="settlement">
                            <input type="text" name="description" required placeholder="Description" class="{{ $inputClass }} col-span-2">
                            <input type="number" name="quantity" step="0.01" min="0.01" value="1" class="{{ $inputClass }}" placeholder="Qty">
                            <input type="number" name="customer_price" step="0.01" min="0" placeholder="Customer $" class="{{ $inputClass }}">
                            <button type="submit" class="px-3 py-1.5 rounded-md text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 col-span-2 sm:col-span-1">Add</button>
                        </form>
                        <p class="text-xs text-gray-400 mt-1">Inventory is not deducted.</p>
                    </details>
                @endunless
            </div>

            {{-- ===== Other charges ===== --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Other Charges</h2>
                    <span class="text-sm font-semibold text-gray-700">${{ number_format($package->otherTotal(), 2) }}</span>
                </div>
                @php $otherChargeLines = $ticket->chargeLines->filter(fn ($l) => !in_array($l->charge_type, [ServiceChargeType::Labor, ServiceChargeType::Parts], true)); @endphp
                @if ($otherChargeLines->isEmpty())
                    <p class="text-sm text-gray-400 italic">No other charges.</p>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($otherChargeLines as $line)
                            <div class="py-2.5 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium {{ $line->billable ? 'text-gray-800' : 'text-gray-400 line-through' }}">
                                        {{ $line->charge_type->label() }} <span class="font-normal text-gray-500">— {{ $line->description }}</span>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Qty {{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }} × ${{ number_format($line->unit_amount, 2) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    @unless ($line->billable)
                                        <span class="text-[11px] font-semibold text-gray-400 uppercase">Non-billable</span>
                                    @endunless
                                    <span class="text-sm font-medium text-gray-900">${{ number_format($line->line_total, 2) }}</span>
                                    @unless ($chargeCreated)
                                        <details class="relative">
                                            <summary class="text-xs font-medium text-blue-600 cursor-pointer select-none">Edit</summary>
                                            <form method="POST" action="{{ route('admin.service-management.tickets.charges.update', [$ticket, $line]) }}"
                                                class="absolute right-0 z-10 mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-2">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="from" value="settlement">
                                                <select name="charge_type" required class="{{ $inputClass }}">
                                                    @foreach (ServiceChargeType::cases() as $type)
                                                        <option value="{{ $type->value }}" @selected($line->charge_type === $type)>{{ $type->label() }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="description" value="{{ $line->description }}" required class="{{ $inputClass }}">
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input type="number" name="quantity" step="0.01" min="0.01" value="{{ $line->quantity }}" class="{{ $inputClass }}">
                                                    <input type="number" name="unit_amount" step="0.01" min="0" value="{{ $line->unit_amount }}" required class="{{ $inputClass }}">
                                                </div>
                                                <label class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                                                    <input type="hidden" name="billable" value="0">
                                                    <input type="checkbox" name="billable" value="1" @checked($line->billable) class="rounded border-gray-300">
                                                    Billable
                                                </label>
                                                <button type="submit" class="w-full px-3 py-1.5 rounded-md text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700">Save</button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('admin.service-management.tickets.charges.destroy', [$ticket, $line]) }}"
                                            onsubmit="return confirm('Remove this charge line?');">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="from" value="settlement">
                                            <button type="submit" class="p-1 text-gray-300 hover:text-red-500" title="Remove"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @unless ($chargeCreated)
                    <details class="mt-3 pt-3 border-t border-gray-100">
                        <summary class="text-sm font-medium text-blue-600 cursor-pointer select-none">+ Add Charge</summary>
                        <form method="POST" action="{{ route('admin.service-management.tickets.charges.store', $ticket) }}" class="mt-2 grid grid-cols-2 sm:grid-cols-5 gap-2 text-sm">
                            @csrf
                            <input type="hidden" name="from" value="settlement">
                            <select name="charge_type" required class="{{ $inputClass }}">
                                @foreach (ServiceChargeType::cases() as $type)
                                    @continue(in_array($type, [ServiceChargeType::Labor, ServiceChargeType::Parts], true))
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="description" required placeholder="Description" class="{{ $inputClass }} col-span-2 sm:col-span-2">
                            <input type="number" name="quantity" step="0.01" min="0.01" value="1" class="{{ $inputClass }}" placeholder="Qty">
                            <input type="number" name="unit_amount" step="0.01" min="0" required placeholder="Unit $" class="{{ $inputClass }}">
                            <button type="submit" class="px-3 py-1.5 rounded-md text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 col-span-2 sm:col-span-5 sm:w-24">Add</button>
                        </form>
                    </details>
                @endunless
            </div>

            {{-- ===== Credits ===== --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Credits</h2>
                    <span class="text-sm font-semibold text-emerald-700">−${{ number_format($package->creditsTotal(), 2) }}</span>
                </div>
                @if ($package->credits()->isEmpty())
                    <p class="text-sm text-gray-400 italic">
                        No credits apply. Diagnostic fees and parts deposits appear here when paid and marked creditable.
                    </p>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($package->credits() as $credit)
                            <div class="py-2.5 flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-gray-800">{{ $credit['label'] }}</p>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-emerald-700">−${{ number_format($credit['amount'], 2) }}</span>
                                    @unless ($chargeCreated)
                                        {{-- Removing a credit = marking the underlying fee/deposit non-creditable --}}
                                        <form method="POST"
                                            action="{{ $credit['key'] === 'diagnostic_fee'
                                                ? route('admin.service-management.tickets.diagnostic.update', $ticket)
                                                : route('admin.service-management.tickets.deposit.update', $ticket) }}"
                                            onsubmit="return confirm('Remove this credit from the settlement?');">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="from" value="settlement">
                                            <input type="hidden" name="{{ $credit['key'] === 'diagnostic_fee' ? 'diagnostic_fee_creditable' : 'parts_deposit_creditable' }}" value="0">
                                            <button type="submit" class="p-1 text-gray-300 hover:text-red-500" title="Remove credit"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <p class="text-xs text-gray-400 mt-3">Credit amounts come from the diagnostic fee and parts deposit — edit those on the ticket to change them.</p>
            </div>
        </div>

        {{-- ===== Settlement summary (calculated only) ===== --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 lg:sticky lg:top-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Settlement Summary</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Labor Total</dt>
                        <dd class="font-medium text-gray-900">${{ number_format($package->laborTotal(), 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Parts Total</dt>
                        <dd class="font-medium text-gray-900">${{ number_format($package->partsTotal(), 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Other Charges</dt>
                        <dd class="font-medium text-gray-900">${{ number_format($package->otherTotal(), 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 pt-2 border-t border-gray-200">
                        <dt class="text-gray-600 font-medium">Subtotal</dt>
                        <dd class="font-semibold text-gray-900">${{ number_format($package->subtotal(), 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Credits</dt>
                        <dd class="font-medium text-emerald-700">−${{ number_format($package->creditsTotal(), 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 pt-2 border-t-2 border-gray-300">
                        <dt class="text-gray-800 font-semibold">Amount to Create</dt>
                        <dd class="text-lg font-bold text-gray-900">${{ number_format($package->finalAmount(), 2) }}</dd>
                    </div>
                </dl>
                <p class="text-xs text-gray-400 mt-3">
                    Totals are calculated from the line items and cannot be edited directly.
                </p>

                <div class="mt-4 pt-4 border-t border-gray-100">
                    @if ($chargeCreated)
                        <a href="{{ route('admin.service-management.tickets.show', $ticket) }}"
                            class="block w-full text-center px-3 py-2 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 transition">
                            View Financial Settlement on Ticket
                        </a>
                    @elseif ($ticket->canCreateCustomerCharge())
                        <form method="POST" action="{{ route('admin.service-management.tickets.settlement.store', $ticket) }}"
                            onsubmit="return confirm('Create a customer charge of ${{ number_format($package->finalAmount(), 2) }}? Billing then moves to the order — this cannot be edited here afterward.');">
                            @csrf
                            <button type="submit"
                                class="w-full px-3 py-2.5 rounded-lg text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                Create Customer Charge — ${{ number_format($package->finalAmount(), 2) }}
                            </button>
                        </form>
                        <p class="text-xs text-gray-400 mt-2">Hands the settlement to Order Extra Payments. No payment is processed.</p>
                    @else
                        <button type="button" disabled
                            class="w-full px-3 py-2.5 rounded-lg text-sm font-semibold bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed">
                            Create Customer Charge
                        </button>
                        <p class="text-xs text-gray-400 mt-2">
                            Blocked: {{ $ticket->chargeCreationBlockers()->implode(' · ') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
