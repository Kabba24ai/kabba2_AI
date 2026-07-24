@extends('admin.layouts.app')

@section('title', 'Customer Damage')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Customer Damage Staging — the review bridge between the return
         checklist and disposition. Field personnel only record
         observations; every disposition here runs through the canonical
         Billing / Service Ticket paths (CustomerDamageStagingService). --}}
    <div class="max-w-6xl mx-auto px-4 py-6">

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Customer Damage</h1>
                <p class="text-sm text-gray-500 mt-0.5">Review field-reported damage and choose a disposition — no action, charge, or service ticket.</p>
            </div>
            <button type="button" id="cd-new"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                <x-heroicon-o-plus class="w-4 h-4" />
                Report Damage
            </button>
        </div>

        {{-- Mini dashboard --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            @foreach ([
                ['New', $summary['new'], 'text-orange-600'],
                ['Awaiting Review', $summary['in_review'], 'text-blue-600'],
                ['Service Tickets', $summary['ticket_linked'], 'text-indigo-600'],
                ['Resolved Today', $summary['resolved_today'], 'text-green-600'],
            ] as [$label, $value, $color])
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="text-2xl font-bold {{ $color }}">{{ $value }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $label }}</div>
                </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-5">
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <select name="filter" onchange="this.form.submit()"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    @foreach ([
                        'active' => 'Active (queue)', 'new' => 'New', 'in_review' => 'In Review',
                        'ticket_created' => 'Service Ticket Created', 'charge_created' => 'Charge Created',
                        'no_action' => 'No Action', 'all' => 'All',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Order #, customer, or equipment"
                       class="flex-1 min-w-[220px] border border-gray-300 rounded-md px-3 py-2 text-sm">
                <button type="submit" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">Search</button>
            </form>
        </div>

        {{-- Queue --}}
        <div class="space-y-3">
            @forelse ($records as $record)
                @php
                    $statusPill = match ($record->status) {
                        'in_review' => ['In Review', 'bg-blue-50 text-blue-700 border-blue-200'],
                        'disposed'  => match ($record->disposition) {
                            'no_action'      => ['No Action', 'bg-gray-100 text-gray-500 border-gray-200'],
                            'charge_customer'=> ['Charged', 'bg-green-50 text-green-700 border-green-200'],
                            'service_ticket' => ['Ticketed', 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                            default          => ['Disposed', 'bg-gray-100 text-gray-500 border-gray-200'],
                        },
                        default     => ['New', 'bg-orange-50 text-orange-700 border-orange-200'],
                    };
                    $ageDays = \App\Services\BillingChargePresenter::ageDays($record->reported_at?->timestamp);
                @endphp
                <div class="border border-gray-200 rounded-xl bg-white p-4 hover:shadow-md transition"
                     data-cd-row
                     data-uid="{{ $record->unique_id }}"
                     data-status="{{ $record->status }}"
                     data-customer="{{ $record->customer?->full_name ?? '—' }}"
                     data-order="{{ $record->order?->order_number ?? '—' }}"
                     data-order-url="{{ $record->order ? route('admin.order-management.orders.edit', $record->order->unique_id) : '' }}"
                     data-equipment="{{ $record->equipment?->equipment_name ?? '—' }}"
                     data-source="{{ $record->source_type }}"
                     data-reported-by="{{ $record->reportedBy?->full_name ?? '—' }}"
                     data-reported-at="{{ $record->reported_at?->format('M j, Y g:ia') }}"
                     data-observation="{{ $record->observation }}"
                     data-note="{{ $record->disposition_note }}"
                     data-has-charge="{{ $record->billing_charge_id ? '1' : '' }}"
                     data-charge-label="{{ $record->billingCharge ? ($record->billingCharge->status?->label() . ' — $' . number_format((float) $record->billingCharge->amount + (float) $record->billingCharge->tax_amount, 2)) : '' }}"
                     data-has-ticket="{{ $record->service_ticket_id ? '1' : '' }}"
                     data-ticket-label="{{ $record->serviceTicket?->ticket_number ?? '' }}"
                     data-ticket-url="{{ $record->serviceTicket ? route('admin.service-management.tickets.show', $record->serviceTicket) : '' }}">

                    <div class="flex justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-semibold text-gray-900">{{ $record->customer?->full_name ?? '—' }}</h3>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border {{ $statusPill[1] }}">{{ $statusPill[0] }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $record->source_type === 'checklist' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-blue-50 text-blue-700 border-blue-200' }}">
                                    {{ $record->source_type === 'checklist' ? 'Checklist' : 'Manual' }}
                                </span>
                                @if ($record->service_ticket_id)
                                    <a href="{{ route('admin.service-management.tickets.show', $record->serviceTicket) }}"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 hover:underline">
                                        <x-heroicon-o-ticket class="w-3.5 h-3.5" /> {{ $record->serviceTicket?->ticket_number }}
                                    </a>
                                @endif
                                @if ($record->billing_charge_id)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-green-50 text-green-700 border border-green-200"
                                          title="Linked damage charge">
                                        <x-heroicon-o-currency-dollar class="w-3.5 h-3.5" /> Charge
                                    </span>
                                @endif
                            </div>

                            <div class="mt-2 text-sm flex flex-wrap items-center gap-x-4 gap-y-1 text-gray-600">
                                <span><span class="font-semibold text-gray-700">Order:</span>
                                    @if ($record->order)
                                        <a href="{{ route('admin.order-management.orders.edit', $record->order->unique_id) }}" target="_blank"
                                           class="text-blue-600 hover:underline font-medium">{{ $record->order->order_number }}</a>
                                    @else — @endif
                                </span>
                                <span><span class="font-semibold text-gray-700">Equipment:</span> {{ $record->equipment?->equipment_name ?? '—' }}</span>
                                <span><span class="font-semibold text-gray-700">Reported:</span> {{ $record->reported_at?->format('M j, Y') }}</span>
                                @if ($ageDays !== null && !$record->isDisposed())
                                    <span><span class="font-semibold text-gray-700">Age:</span> {{ $ageDays }} {{ Str::plural('day', $ageDays) }}</span>
                                @endif
                            </div>

                            <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ Str::limit($record->observation, 180) }}</p>
                        </div>

                        <div class="flex flex-col justify-center border-l border-gray-100 pl-4 shrink-0">
                            <button type="button" data-cd-review
                                    class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium">
                                Review
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 text-gray-400 bg-white border border-gray-200 rounded-xl">
                    <p class="text-sm">No customer damage records found.</p>
                </div>
            @endforelse
        </div>

        @if ($records->hasPages())
            <div class="mt-6">{{ $records->links() }}</div>
        @endif
    </div>

    {{-- ── Review / disposition modal ─────────────────────────────────── --}}
    <div id="cd-review-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4 py-8 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Review — <span id="cd-r-context"></span></h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" data-cd-close>&times;</button>
            </div>

            <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm mb-4">
                <div><span class="text-gray-500">Customer:</span> <span id="cd-r-customer" class="font-medium text-gray-900"></span></div>
                <div><span class="text-gray-500">Order:</span> <a id="cd-r-order" target="_blank" class="font-medium text-blue-600 hover:underline"></a></div>
                <div><span class="text-gray-500">Equipment:</span> <span id="cd-r-equipment" class="font-medium text-gray-900"></span></div>
                <div><span class="text-gray-500">Source:</span> <span id="cd-r-source" class="font-medium text-gray-900"></span></div>
                <div><span class="text-gray-500">Reported by:</span> <span id="cd-r-reported-by" class="font-medium text-gray-900"></span></div>
                <div><span class="text-gray-500">Reported:</span> <span id="cd-r-reported-at" class="font-medium text-gray-900"></span></div>
            </div>

            <div class="mb-3">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Field observation</div>
                <div id="cd-r-observation" class="text-sm text-gray-800 whitespace-pre-line bg-gray-50 border border-gray-200 rounded-md px-3 py-2 max-h-40 overflow-y-auto"></div>
            </div>

            <div id="cd-r-links" class="mb-4 space-y-1 text-sm"></div>

            <div id="cd-r-notes-wrap" class="mb-4 hidden">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Review notes</div>
                <div id="cd-r-notes" class="text-sm text-gray-700 whitespace-pre-line bg-gray-50 border border-gray-200 rounded-md px-3 py-2 max-h-28 overflow-y-auto"></div>
            </div>

            {{-- Disposition chooser --}}
            <div id="cd-actions" class="border-t border-gray-100 pt-4">
                <div class="flex flex-wrap gap-2 mb-4">
                    <button type="button" data-cd-tab="no_action" class="cd-tab px-3 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700">No Action Required</button>
                    <button type="button" data-cd-tab="charge_customer" class="cd-tab px-3 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700">Charge Customer</button>
                    <button type="button" data-cd-tab="service_ticket" class="cd-tab px-3 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700">Create Service Ticket</button>
                    <button type="button" data-cd-tab="in_review" class="cd-tab px-3 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700">Keep In Review</button>
                </div>

                <div id="cd-pane-note" class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note <span id="cd-note-required" class="text-red-500 hidden">*</span></label>
                    <textarea id="cd-note" rows="2" maxlength="2000" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                              placeholder="Why is this the right disposition?"></textarea>
                    <x-admin.billing.note-preset-select id="cd-note-presets" type="resolution"
                        :presets="$resolutionPresets" target-id="cd-note" />
                </div>

                <div id="cd-pane-charge" class="hidden mb-3 space-y-3">
                    <div id="cd-charge-existing" class="hidden text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">
                        A damage charge is already linked from the checklist — no second charge will be created.
                        Price and collect it through the Billing Engine on the order.
                    </div>
                    <div id="cd-charge-form" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Charge Amount <span class="text-red-500">*</span></label>
                            <x-admin.billing.currency-input id="cd-amount" />
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="cd-tax" value="add" checked class="accent-teal-500">
                                <span class="text-sm text-gray-700">Add Sales Tax</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="cd-tax" value="free" class="accent-teal-500">
                                <span class="text-sm text-gray-700">No Tax</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="cd-tax" value="reverse" class="accent-teal-500">
                                <span class="text-sm text-gray-700">Tax Included</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div id="cd-pane-ticket" class="hidden mb-3 text-sm text-gray-600 bg-indigo-50 border border-indigo-200 rounded-md px-3 py-2">
                    Creates a <span class="font-semibold">Customer Damage</span> service ticket through the canonical Service intake,
                    prefilled with this order, customer, equipment, and observation. Diagnosis continues on the ticket.
                </div>

                <div id="cd-error" class="hidden mb-3 text-sm text-red-600"></div>

                <div class="flex justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-cd-close>Cancel</button>
                    <button type="button" id="cd-submit"
                            class="px-4 py-2 text-sm rounded-md bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-40 disabled:cursor-not-allowed">
                        Confirm Disposition
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Manual creation modal ──────────────────────────────────────── --}}
    <div id="cd-new-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4 py-8 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Report Customer Damage</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" data-cd-close>&times;</button>
            </div>
            <p class="text-xs text-gray-500 mb-4">
                Customer Damage is order-based. If the damage can't be tied to an order, use the Service intake or Rental Ready workflow instead.
            </p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Order <span class="text-red-500">*</span></label>
                <div id="cd-order-selected" class="hidden items-center justify-between gap-2 border border-blue-200 bg-blue-50/60 rounded-md px-3 py-2 text-sm">
                    <span id="cd-order-label" class="text-gray-800"></span>
                    <button type="button" id="cd-order-clear" class="text-xs text-blue-600 hover:underline shrink-0">Change</button>
                </div>
                <div id="cd-order-search-wrap" class="relative">
                    <input type="text" id="cd-order-search" autocomplete="off" placeholder="Search by order # or customer…"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <div id="cd-order-results" class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Damaged Item <span class="text-red-500">*</span></label>
                <select id="cd-op" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" disabled>
                    <option value="">Select the order first…</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Observation <span class="text-red-500">*</span></label>
                <textarea id="cd-observation" rows="3" maxlength="2000"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                          placeholder="What was observed at return?"></textarea>
            </div>

            <div id="cd-new-error" class="hidden mb-3 text-sm text-red-600"></div>

            <div class="flex justify-end gap-2">
                <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-cd-close>Cancel</button>
                <button type="button" id="cd-new-save"
                        class="px-4 py-2 text-sm rounded-md bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-40 disabled:cursor-not-allowed">
                    Record Damage
                </button>
            </div>
        </div>
    </div>

@endsection

@push('js')
<script>
(function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const URLS = {
        store:        @json(route('admin.service-management.customer-damage.store')),
        disposition:  @json(route('admin.service-management.customer-damage.disposition', ':uid')),
        orderProducts:@json(route('admin.service-management.customer-damage.order-products')),
        orders:       @json(route('admin.dashboard.charge-modal.orders')),
    };

    const $ = (id) => document.getElementById(id);
    let activeUid = null;
    let activeTab = 'no_action';
    let submitting = false;

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function toast(msg, ok = true) {
        if (window.notyf) { ok ? notyf.success(msg) : notyf.error(msg); return; }
        alert(msg);
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'The action could not be completed.');
        }
        return data;
    }

    document.querySelectorAll('[data-cd-close]').forEach((b) =>
        b.addEventListener('click', () => {
            $('cd-review-modal').classList.add('hidden');
            $('cd-new-modal').classList.add('hidden');
        }));

    // ── Review modal ────────────────────────────────────────────────────
    function setTab(tab) {
        activeTab = tab;
        document.querySelectorAll('.cd-tab').forEach((b) => {
            const on = b.dataset.cdTab === tab;
            b.classList.toggle('bg-orange-500', on);
            b.classList.toggle('text-white', on);
            b.classList.toggle('border-orange-500', on);
            b.classList.toggle('text-gray-700', !on);
        });
        $('cd-pane-charge').classList.toggle('hidden', tab !== 'charge_customer');
        $('cd-pane-ticket').classList.toggle('hidden', tab !== 'service_ticket');
        $('cd-note-required').classList.toggle('hidden', tab !== 'no_action');
        $('cd-submit').textContent = {
            no_action: 'Close — No Action Required',
            charge_customer: 'Confirm Charge',
            service_ticket: 'Create Service Ticket',
            in_review: 'Keep In Review',
        }[tab];
    }
    document.querySelectorAll('.cd-tab').forEach((b) =>
        b.addEventListener('click', () => setTab(b.dataset.cdTab)));

    document.querySelectorAll('[data-cd-review]').forEach((btn) =>
        btn.addEventListener('click', () => {
            const ds = btn.closest('[data-cd-row]').dataset;
            activeUid = ds.uid;

            $('cd-r-context').textContent = (ds.customer || '') + (ds.order && ds.order !== '—' ? ' · ' + ds.order : '');
            $('cd-r-customer').textContent = ds.customer;
            $('cd-r-order').textContent = ds.order;
            $('cd-r-order').href = ds.orderUrl || '#';
            $('cd-r-equipment').textContent = ds.equipment;
            $('cd-r-source').textContent = ds.source === 'checklist' ? 'Return checklist' : 'Manual report';
            $('cd-r-reported-by').textContent = ds.reportedBy;
            $('cd-r-reported-at').textContent = ds.reportedAt;
            $('cd-r-observation').textContent = ds.observation;

            const links = [];
            if (ds.hasTicket) {
                links.push('<a href="' + esc(ds.ticketUrl) + '" class="text-indigo-600 hover:underline">Service ticket ' + esc(ds.ticketLabel) + '</a>');
            }
            if (ds.hasCharge) {
                links.push('<span class="text-green-700">Linked damage charge: ' + esc(ds.chargeLabel) + '</span>');
            }
            $('cd-r-links').innerHTML = links.map((l) => '<div>' + l + '</div>').join('');

            $('cd-r-notes-wrap').classList.toggle('hidden', !ds.note);
            $('cd-r-notes').textContent = ds.note || '';

            $('cd-note').value = '';
            $('cd-amount').value = '';
            document.querySelector('input[name="cd-tax"][value="add"]').checked = true;
            $('cd-charge-existing').classList.toggle('hidden', !ds.hasCharge);
            $('cd-charge-form').classList.toggle('hidden', !!ds.hasCharge);
            $('cd-error').classList.add('hidden');
            setTab('no_action');

            // Disposed records: review remains available for add-on actions
            // (e.g. charge after ticket) — the server guards what's allowed.
            $('cd-review-modal').classList.remove('hidden');
        }));

    $('cd-submit').addEventListener('click', async () => {
        if (submitting || !activeUid) return;
        const err = $('cd-error');
        err.classList.add('hidden');

        const body = { action: activeTab, note: $('cd-note').value.trim() || null };
        if (activeTab === 'no_action' && !body.note) {
            err.textContent = 'A note is required for No Action.';
            err.classList.remove('hidden');
            return;
        }
        if (activeTab === 'charge_customer' && !$('cd-charge-form').classList.contains('hidden')) {
            body.amount = Number($('cd-amount').value) || null;
            body.sales_tax_type = document.querySelector('input[name="cd-tax"]:checked')?.value || 'add';
            if (!body.amount) {
                err.textContent = 'Enter the charge amount.';
                err.classList.remove('hidden');
                return;
            }
        }

        submitting = true;
        $('cd-submit').disabled = true;
        try {
            const data = await postJson(URLS.disposition.replace(':uid', activeUid), body);
            toast(data.message || 'Saved.');
            setTimeout(() => window.location.reload(), 900);
        } catch (e) {
            err.textContent = e.message;
            err.classList.remove('hidden');
            submitting = false;
            $('cd-submit').disabled = false;
        }
    });

    // ── Manual creation ─────────────────────────────────────────────────
    let newOrderId = null;
    let newSubmitting = false;

    $('cd-new').addEventListener('click', () => {
        newOrderId = null;
        $('cd-order-selected').classList.add('hidden');
        $('cd-order-search-wrap').classList.remove('hidden');
        $('cd-order-search').value = '';
        $('cd-op').innerHTML = '<option value="">Select the order first…</option>';
        $('cd-op').disabled = true;
        $('cd-observation').value = '';
        $('cd-new-error').classList.add('hidden');
        $('cd-new-modal').classList.remove('hidden');
    });

    let searchTimer;
    $('cd-order-search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = $('cd-order-search').value.trim();
        const results = $('cd-order-results');
        if (q.length < 2) { results.classList.add('hidden'); return; }
        searchTimer = setTimeout(async () => {
            const res = await fetch(`${URLS.orders}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            results.innerHTML = (data.results || []).map((o) =>
                `<div data-pick='${esc(JSON.stringify(o))}' class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer">
                    <span class="font-medium">Order ${esc(o.order_number ?? '')}</span>
                    <span class="text-gray-600"> ${esc(o.customer_name ?? '')}</span>
                 </div>`).join('')
                || '<div class="px-3 py-2 text-sm text-gray-400">No matches</div>';
            results.classList.remove('hidden');
            results.querySelectorAll('[data-pick]').forEach((row) =>
                row.addEventListener('click', () => pickOrder(JSON.parse(row.dataset.pick))));
        }, 300);
    });

    async function pickOrder(order) {
        newOrderId = order.id;
        $('cd-order-results').classList.add('hidden');
        $('cd-order-label').textContent = `Order ${order.order_number ?? ''}` + (order.customer_name ? ' · ' + order.customer_name : '');
        $('cd-order-selected').classList.remove('hidden');
        $('cd-order-selected').classList.add('flex');
        $('cd-order-search-wrap').classList.add('hidden');

        $('cd-op').innerHTML = '<option value="">Loading…</option>';
        try {
            const res = await fetch(`${URLS.orderProducts}?order_id=${order.id}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            $('cd-op').innerHTML = '<option value="">Select the damaged item…</option>'
                + (data.results || []).map((p) => `<option value="${p.id}">${esc(p.label)}</option>`).join('');
            $('cd-op').disabled = false;
        } catch (e) {
            $('cd-op').innerHTML = '<option value="">Could not load items</option>';
        }
    }

    $('cd-order-clear').addEventListener('click', () => {
        newOrderId = null;
        $('cd-order-selected').classList.add('hidden');
        $('cd-order-search-wrap').classList.remove('hidden');
        $('cd-op').innerHTML = '<option value="">Select the order first…</option>';
        $('cd-op').disabled = true;
    });

    $('cd-new-save').addEventListener('click', async () => {
        if (newSubmitting) return;
        const err = $('cd-new-error');
        err.classList.add('hidden');

        if (!newOrderId) { err.textContent = 'Select an order.'; err.classList.remove('hidden'); return; }
        if (!$('cd-op').value) { err.textContent = 'Select the damaged item.'; err.classList.remove('hidden'); return; }
        if (!$('cd-observation').value.trim()) { err.textContent = 'Describe what was observed.'; err.classList.remove('hidden'); return; }

        newSubmitting = true;
        $('cd-new-save').disabled = true;
        try {
            const data = await postJson(URLS.store, {
                order_id: newOrderId,
                order_product_id: Number($('cd-op').value),
                observation: $('cd-observation').value.trim(),
            });
            toast(data.message || 'Recorded.');
            setTimeout(() => window.location.reload(), 900);
        } catch (e) {
            err.textContent = e.message;
            err.classList.remove('hidden');
            newSubmitting = false;
            $('cd-new-save').disabled = false;
        }
    });
})();
</script>
@endpush
