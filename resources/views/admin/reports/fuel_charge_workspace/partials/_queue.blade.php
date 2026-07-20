{{-- Billing Charge Operations Commonization — the Fuel Charge queue.
     One alert per row, rendered entirely through the shared billing
     components (status badge, origin badge, action bar) so this surface
     and the Order Details Billing Engine present identically.

     Capability comes from CHARGE STATE + business rules, never origin:
       - OrderProduct rows (action-mode 'op') use the canonical dashboard
         endpoints, exactly as before.
       - CRM/manual rows with a Billing Engine bridge (action-mode
         'charge') use the canonical billing-charges.* endpoints — the
         Phase-1 read-only treatment is retired (approved decision).
       - CRM rows WITHOUT a bridge row stay link-only: there is no
         canonical BillingCharge to operate on (business rule, not origin).
       - Adjust is withheld from CRM rows for now: the billing-charges
         adjust endpoint changes only billing_charges.amount and would
         desync the CustomerAccount ledger amount these rows bill from —
         a Phase 2 CRM-synchronization item, documented in the mission
         report.
       - Refund renders only on completed rows whose PAID BillingCharge
         still has a remaining refundable balance (enriched by the
         controller); the linked-refund endpoint re-validates everything. --}}

@if ($alerts->isEmpty())
    <div class="text-center py-16 text-gray-400">
        <p class="text-sm">No fuel charge alerts found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach ($alerts as $alert)
            @php
                $isCrm = ($alert['source'] ?? null) === 'crm';
                $isTerminal = ($alert['terminal_status'] ?? null) !== null;
                $bcUniqueId = $alert['billing_charge_unique_id'] ?? null;
                $actionMode = $isCrm ? 'charge' : 'op';
                $ageDays = \App\Services\BillingChargePresenter::ageDays((int) ($alert['_sort_ts'] ?? 0));
                $latestNotes = collect($alert['notes'] ?? []);
                $amountNumeric = (float) str_replace(['$', ','], '', $alert['amountOwed'] === 'Pending' ? '0' : $alert['amountOwed']);
                $refundRemaining = (float) ($alert['refund_remaining'] ?? 0);

                // Capability — from state and business rules only.
                $actions = [];
                if (!$isTerminal) {
                    if (!$isCrm) {
                        $actions = ['history', 'notes', 'adjust', 'payment', 'resolve', 'uncollectible'];
                    } elseif ($bcUniqueId) {
                        // Manual charge with a canonical BillingCharge: full
                        // lifecycle via the billing-charges endpoints.
                        // (Adjust withheld — see header comment.)
                        $actions = ['notes', 'payment', 'resolve', 'uncollectible'];
                        if (!empty($alert['order_db_id'])) {
                            array_unshift($actions, 'history');
                        }
                    }
                } else {
                    if (!empty($alert['order_db_id'])) {
                        $actions[] = 'history';
                    }
                    if (!$isCrm || $bcUniqueId) {
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
                 data-type="fuel"
                 data-source="{{ $isCrm ? 'crm' : 'op' }}"
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

                            <x-admin.billing.charge-origin-badge :origin="$isCrm ? 'manual' : 'checklist'" />

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

                    {{-- Middle: amount --}}
                    <div class="flex flex-col items-end justify-center shrink-0 min-w-[90px]">
                        <div class="text-lg font-bold text-orange-600">{{ $alert['amountOwed'] }}</div>
                        <div class="text-xs text-gray-400">Fuel Charge</div>
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
