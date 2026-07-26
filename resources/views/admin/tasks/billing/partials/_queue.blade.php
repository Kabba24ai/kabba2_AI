{{-- Billing Operations — the charge-resolution queue, shared by the Fuel and
     Damage workspaces. One alert per row, rendered entirely through the
     shared billing components (status badge, origin badge, action bar) so
     this surface and the Order Details Billing Engine present identically.

     Parameterized by $chargeType ('fuel' | 'damage'); everything else is
     charge-type-agnostic. Capability comes from CHARGE STATE + business
     rules, never origin:
       - OrderProduct rows (action-mode 'op') use the canonical dashboard
         endpoints (which take the row's type param), exactly as before.
       - CRM/manual rows with a Billing Engine bridge (action-mode 'charge')
         use the canonical billing-charges.* endpoints.
       - CRM rows WITHOUT a bridge row stay link-only: there is no canonical
         BillingCharge to operate on (business rule, not origin).
       - Adjust is withheld from CRM rows (billing-charges adjust would desync
         the CustomerAccount ledger amount these rows bill from).
       - Refund renders only on completed rows whose PAID BillingCharge still
         has a remaining refundable balance (enriched by the controller).

     Damage rows may additionally carry $alert['source_type'] / ['source_link']
     — the authoritative origin (Customer Checklist / Service Ticket / Manual)
     resolved by the controller. Fuel rows never set these, so this partial
     renders identically for fuel. --}}

@php
    $chargeType = $chargeType ?? 'fuel';
    $ui = [
        'fuel'   => ['noun' => 'Fuel Charge',   'empty' => 'fuel charge',   'amountColor' => 'text-orange-600'],
        'damage' => ['noun' => 'Damage Charge', 'empty' => 'damage charge', 'amountColor' => 'text-rose-600'],
    ][$chargeType] ?? ['noun' => 'Charge', 'empty' => 'charge', 'amountColor' => 'text-gray-700'];
@endphp

@if ($alerts->isEmpty())
    <div class="text-center py-16 text-gray-400">
        <p class="text-sm">No {{ $ui['empty'] }} alerts found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach ($alerts as $alert)
            @php
                $isCrm = ($alert['source'] ?? null) === 'crm';
                $isTerminal = ($alert['terminal_status'] ?? null) !== null;
                $bcUniqueId = $alert['billing_charge_unique_id'] ?? null;
                // action_mode / origin may be set explicitly by the controller
                // (e.g. Service Ticket damage charges are charge-mode with a
                // 'service' origin); otherwise derived from source.
                $actionMode = $alert['action_mode'] ?? ($isCrm ? 'charge' : 'op');
                $origin = $alert['origin'] ?? ($isCrm ? 'manual' : 'checklist');
                $dataSource = $alert['source'] ?? ($isCrm ? 'crm' : 'op');
                $ageDays = \App\Services\BillingChargePresenter::ageDays((int) ($alert['_sort_ts'] ?? 0));
                $latestNotes = collect($alert['notes'] ?? []);
                $amountNumeric = (float) str_replace(['$', ','], '', ($alert['amountOwed'] ?? '$0.00') === 'Pending' ? '0' : ($alert['amountOwed'] ?? '0'));
                $refundRemaining = (float) ($alert['refund_remaining'] ?? 0);

                // An active charge with no established amount ($0) needs pricing
                // first — it stays visible but is NOT collectible until priced.
                $isUnpriced = !$isTerminal && $amountNumeric <= 0;

                // Capability — from state and business rules only (keyed on
                // action mode, not origin).
                $actions = [];
                if (!$isTerminal) {
                    if ($actionMode === 'op') {
                        $actions = ['history', 'notes', 'adjust', 'payment', 'resolve', 'uncollectible'];
                    } elseif ($bcUniqueId) {
                        $actions = ['notes', 'payment', 'resolve', 'uncollectible'];
                        if (!empty($alert['order_db_id'])) {
                            array_unshift($actions, 'history');
                        }
                    }
                    // No collection on an unpriced charge — withhold payment
                    // until a positive amount is established (via adjust).
                    if ($isUnpriced) {
                        $actions = array_values(array_diff($actions, ['payment']));
                    }
                } else {
                    if (!empty($alert['order_db_id'])) {
                        $actions[] = 'history';
                    }
                    if ($actionMode === 'op' || $bcUniqueId) {
                        $actions[] = 'notes';
                    }
                    if ($refundRemaining > 0 && $bcUniqueId) {
                        $actions[] = 'refund';
                    }
                }
            @endphp

            <div class="border border-gray-200 rounded-xl bg-white p-4 hover:shadow-md transition"
                 data-charge-row
                 data-action-mode="{{ $actionMode }}"
                 data-type="{{ $chargeType }}"
                 data-source="{{ $dataSource }}"
                 data-op-id="{{ $alert['order_product']['id'] ?? '' }}"
                 data-op-uid="{{ $alert['order_product']['unique_id'] ?? '' }}"
                 data-order-db-id="{{ $alert['order_db_id'] ?? '' }}"
                 data-order-uid="{{ $alert['orderId'] ?? '' }}"
                 data-order-number="{{ $alert['order_number'] ?? '' }}"
                 data-customer-id="{{ $alert['customer']['id'] ?? '' }}"
                 data-customer-name="{{ $alert['customerName'] }}"
                 data-amount="{{ number_format($amountNumeric, 2, '.', '') }}"
                 data-amount-total="{{ number_format($amountNumeric, 2, '.', '') }}"
                 data-cards='@json(collect($alert['customer']['cards'] ?? [])->values())'
                 data-bc-id="{{ $bcUniqueId ?? '' }}"
                 data-ca-id="{{ $alert['customer_account_id'] ?? '' }}"
                 data-refund-remaining="{{ number_format($refundRemaining, 2, '.', '') }}">

                <div class="flex justify-between gap-4">
                    {{-- Left: context --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $alert['customerName'] }}</h3>

                            <x-admin.billing.charge-status-badge :status="$alert['terminal_status'] ?? 'pending'" />

                            <x-admin.billing.charge-origin-badge :origin="$origin" />

                            @if ($latestNotes->isNotEmpty())
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-yellow-50 text-yellow-700 border border-yellow-200"
                                      title="{{ $latestNotes->first()['note'] ?? '' }}">
                                    <x-heroicon-o-chat-bubble-left class="w-3.5 h-3.5" />
                                    {{ $latestNotes->count() }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-2 text-sm flex flex-wrap items-center gap-x-4 gap-y-1 text-gray-600">
                            <span>
                                <span class="font-semibold text-gray-700">Order:</span>
                                @if (!empty($alert['orderLink']) && !empty($alert['order_number']))
                                    <a href="{{ $alert['orderLink'] }}" target="_blank" class="text-blue-600 hover:underline font-medium">
                                        {{ $alert['order_number'] }}
                                    </a>
                                @elseif ($isCrm)
                                    <span class="text-gray-500">CRM / Credit Balance</span>
                                @else
                                    <span class="text-gray-500">—</span>
                                @endif
                            </span>

                            {{-- Damage source linkage (Customer Checklist / Service Ticket /
                                 Manual). Controller-resolved; absent on fuel rows and on
                                 damage rows with no canonical source. --}}
                            @if (!empty($alert['source_type']))
                                <span>
                                    <span class="font-semibold text-gray-700">Source:</span>
                                    @if (!empty($alert['source_link']))
                                        <a href="{{ $alert['source_link'] }}" target="_blank" class="text-blue-600 hover:underline font-medium">
                                            {{ $alert['source_type'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-500">{{ $alert['source_type'] }}</span>
                                    @endif
                                </span>
                            @endif

                            @if (!empty($alert['equipment']['name']))
                                <span>
                                    <span class="font-semibold text-gray-700">Equipment:</span>
                                    {{ $alert['equipment']['name'] }}
                                </span>
                            @endif

                            <span>
                                <span class="font-semibold text-gray-700">Date:</span>
                                {{ $alert['date'] ?? '—' }}
                            </span>

                            @if ($ageDays !== null)
                                <span>
                                    <span class="font-semibold text-gray-700">Age:</span>
                                    {{ $ageDays }} {{ Str::plural('day', $ageDays) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Middle: amount (or an explicit Needs Pricing state) --}}
                    <div class="flex flex-col items-end justify-center shrink-0 min-w-[90px]">
                        @if ($isUnpriced)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                                  data-needs-pricing>
                                Needs Pricing
                            </span>
                        @else
                            <div class="text-lg font-bold {{ $ui['amountColor'] }}">{{ $alert['amountOwed'] }}</div>
                        @endif
                        <div class="text-xs text-gray-400">{{ $ui['noun'] }}</div>
                    </div>

                    {{-- Right: canonical action bar --}}
                    <div class="flex flex-col justify-center items-end gap-1.5 border-l border-gray-100 pl-4 shrink-0">
                        @if (!empty($actions))
                            <x-admin.billing.charge-actions :actions="$actions" />
                        @endif

                        @if ($isCrm && !empty($alert['crmLink']))
                            <a href="{{ $alert['crmLink'] }}"
                               class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:underline whitespace-nowrap">
                                Manage in CRM
                                <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($alerts->hasPages())
        <div class="mt-6" data-pagination>
            {{ $alerts->links() }}
        </div>
    @endif
@endif
