{{-- Dashboard V2 Phase 1A — the Fuel Charge queue. One alert per row.
     OrderProduct rows carry the full action set (canonical dashboard
     endpoints); CRM rows are deliberately read-only in Phase 1 — they stay
     visible so the count reconciles with the dashboard card, but their
     synchronization problems belong to Phase 2. --}}
@php
    $terminalBadge = fn (?string $s): array => match ($s) {
        'resolved'      => ['Resolved', 'bg-green-50 text-green-700 border-green-200'],
        'completed'     => ['Completed', 'bg-green-50 text-green-700 border-green-200'],
        'uncollectible' => ['Uncollectible', 'bg-gray-100 text-gray-500 border-gray-200'],
        default         => ['Active', 'bg-orange-50 text-orange-700 border-orange-200'],
    };
@endphp

@if ($alerts->isEmpty())
    <div class="text-center py-16 text-gray-400">
        <p class="text-sm">No fuel charge alerts found.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach ($alerts as $alert)
            @php
                $isCrm = ($alert['source'] ?? null) === 'crm';
                [$badgeLabel, $badgeClass] = $terminalBadge($alert['terminal_status'] ?? null);
                $ageDays = isset($alert['_sort_ts']) && $alert['_sort_ts'] > 0
                    ? max(0, (int) floor((now()->timestamp - (int) $alert['_sort_ts']) / 86400))
                    : null;
                $latestNotes = collect($alert['notes'] ?? []);
                $amountNumeric = (float) str_replace(['$', ','], '', $alert['amountOwed'] === 'Pending' ? '0' : $alert['amountOwed']);
            @endphp

            <div class="border border-gray-200 rounded-xl bg-white p-4 hover:shadow-md transition"
                 data-alert-row
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
                 data-cards='@json(collect($alert['customer']['cards'] ?? [])->values())'
                 data-customer-account-id="{{ $alert['customer_account_id'] ?? '' }}">

                <div class="flex justify-between gap-4">
                    {{-- Left: context --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $alert['customerName'] }}</h3>

                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                {{ $badgeLabel }}
                            </span>

                            @if ($isCrm)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                    Manual
                                </span>
                            @endif

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

                    {{-- Right: actions --}}
                    <div class="flex flex-col justify-center border-l border-gray-100 pl-4 shrink-0">
                        @if ($isCrm)
                            <a href="{{ $alert['crmLink'] ?? $alert['orderLink'] }}"
                               class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:underline whitespace-nowrap">
                                Manage in CRM
                                <x-heroicon-o-arrow-right class="w-4 h-4" />
                            </a>
                        @elseif (($alert['terminal_status'] ?? null) === null)
                            <div class="flex items-center gap-1.5">
                                <button type="button" data-action="history" title="Charge History"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:text-blue-600 hover:border-blue-300 transition">
                                    <x-heroicon-o-clock class="w-4 h-4" />
                                </button>
                                <button type="button" data-action="notes" title="Notes"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:text-yellow-600 hover:border-yellow-300 transition">
                                    <x-heroicon-o-chat-bubble-left class="w-4 h-4" />
                                </button>
                                <button type="button" data-action="adjust" title="Adjust Amount"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:text-purple-600 hover:border-purple-300 transition">
                                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                                </button>
                                <button type="button" data-action="payment" title="Collect Payment"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:text-green-600 hover:border-green-300 transition">
                                    <x-heroicon-o-credit-card class="w-4 h-4" />
                                </button>
                                <button type="button" data-action="resolve" title="Resolve"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:text-emerald-600 hover:border-emerald-300 transition">
                                    <x-heroicon-o-check-circle class="w-4 h-4" />
                                </button>
                                <button type="button" data-action="uncollectible" title="Mark Uncollectible"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:text-red-600 hover:border-red-300 transition">
                                    <x-heroicon-o-no-symbol class="w-4 h-4" />
                                </button>
                            </div>
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
