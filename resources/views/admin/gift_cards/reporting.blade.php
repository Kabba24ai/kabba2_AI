@extends('admin.gift_cards.layouts.workspace', ['pageTitle' => 'Gift Card Reporting'])

@section('gc-heading', 'Gift Card Reporting')
@section('gc-subheading',
    'The two sides of the programme side by side: cash taken when a card is funded, revenue recognised when it is
    redeemed. Each dollar is counted exactly once across a card’s life.')

@section('gc-body')

    {{-- ── The accounting model, stated plainly ────────────────────────────
         This panel is not decoration. The commonest way to get gift cards
         wrong is to treat redemption as cash or to exclude it from revenue,
         and both mistakes are easy to make when the two events are only ever
         seen apart. --}}
    <div class="gc-card p-5 mb-4">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">How gift cards are accounted for</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                <div class="text-xs font-semibold text-emerald-900 uppercase tracking-wide">At funding</div>
                <div class="text-sm text-emerald-900 mt-1.5">
                    <strong>Cash in, no revenue.</strong> Real money is received and nothing has been sold yet.
                    No sales tax and no product revenue are created. Reconciles in
                    <span class="font-medium">Stream E</span> with zero base and zero tax.
                </div>
            </div>
            <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3">
                <div class="text-xs font-semibold text-sky-900 uppercase tracking-wide">At redemption</div>
                <div class="text-sm text-sky-900 mt-1.5">
                    <strong>Revenue and tax, no new cash.</strong> The underlying order is taxed in full — a gift card
                    pays the price, it does not change it. The row stays visible in
                    <span class="font-medium">Stream A</span> with a zero settlement expectation.
                </div>
            </div>
        </div>
    </div>

    {{-- ── Purchased: money owed ──────────────────────────────────────────── --}}
    <h2 class="text-sm font-semibold text-gray-900 mb-2">Purchased cards — liability</h2>
    <p class="text-xs text-gray-500 mb-3">Real cash was received. This value is owed to customers until they spend it.</p>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="gc-tile">
            <div class="l">Funding Cash</div>
            <div class="n gc-num">${{ number_format($kpis['funding_cash'], 2) }}</div>
            <div class="s">received, net of voids</div>
        </div>
        <div class="gc-tile">
            <div class="l">Value Issued</div>
            <div class="n gc-num">${{ number_format($kpis['purchased_issued'], 2) }}</div>
        </div>
        <div class="gc-tile">
            <div class="l">Value Redeemed</div>
            <div class="n gc-num">${{ number_format($kpis['purchased_redeemed'], 2) }}</div>
            <div class="s">became order revenue</div>
        </div>
        <div class="gc-tile" style="border-color:#bfdbfe; background:#eff6ff;">
            <div class="l">Outstanding Liability</div>
            <div class="n gc-num">${{ number_format($kpis['purchased_outstanding'], 2) }}</div>
            <div class="s">still owed to customers</div>
        </div>
    </div>

    {{-- ── Granted: money given away ───────────────────────────────────────── --}}
    <h2 class="text-sm font-semibold text-gray-900 mb-2">Granted cards — promotional value</h2>
    <p class="text-xs text-gray-500 mb-3">
        Nobody paid for these. They are an expense, never a debt, and are never summed with the figures above.
    </p>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="gc-tile">
            <div class="l">Value Issued</div>
            <div class="n gc-num">${{ number_format($kpis['granted_issued'], 2) }}</div>
        </div>
        <div class="gc-tile">
            <div class="l">Promotional Value Used</div>
            <div class="n gc-num">${{ number_format($kpis['granted_redeemed'], 2) }}</div>
            <div class="s">redeemed against orders</div>
        </div>
        <div class="gc-tile" style="border-color:#e9d5ff; background:#faf5ff;">
            <div class="l">Outstanding Promotional</div>
            <div class="n gc-num">${{ number_format($kpis['granted_outstanding'], 2) }}</div>
            <div class="s">issued but not yet used</div>
        </div>
        <div class="gc-tile">
            <div class="l">Cash Received</div>
            <div class="n gc-num">$0.00</div>
            <div class="s">by definition — none was taken</div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        <div class="gc-card">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Recent Funding Events</h2>
                <p class="text-xs text-gray-500 mt-0.5">Cash in, no revenue. These reconcile against a settlement.</p>
            </div>
            <div class="gc-scroll">
                <table class="gc-table">
                    <thead>
                        <tr><th>When</th><th>Card</th><th>Tender</th><th>Reference</th><th class="text-right">Cash</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($fundingEvents as $event)
                            <tr class="{{ $event->funding_voided_at ? 'opacity-50' : '' }}">
                                <td class="gc-num">{{ $event->created_at?->format('M j, Y') }}</td>
                                <td class="gc-mono">
                                    <a href="{{ route('admin.gift-cards.show', $event->giftCard->card_number) }}"
                                       class="text-indigo-600 hover:text-indigo-800">{{ $event->giftCard->card_number }}</a>
                                </td>
                                <td>{{ $event->funding_payment_method?->label() ?? '—' }}</td>
                                <td class="gc-mono text-xs text-gray-500">{{ $event->funding_transaction_id ?: '—' }}</td>
                                <td class="text-right gc-num">
                                    ${{ number_format((float) $event->funding_cash_amount, 2) }}
                                    @if ($event->funding_voided_at)
                                        <span class="gc-badge bg-red-100 text-red-800 ml-1">Voided</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="gc-empty">No gift cards have been sold yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="gc-card">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Recent Redemption Events</h2>
                <p class="text-xs text-gray-500 mt-0.5">Revenue and tax recognised. No new external cash.</p>
            </div>
            <div class="gc-scroll">
                <table class="gc-table">
                    <thead>
                        <tr><th>When</th><th>Card</th><th>Class</th><th>Order</th><th class="text-right">Applied</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($redemptionEvents as $event)
                            <tr>
                                <td class="gc-num">{{ $event->created_at?->format('M j, Y') }}</td>
                                <td class="gc-mono">
                                    <a href="{{ route('admin.gift-cards.show', $event->giftCard->card_number) }}"
                                       class="text-indigo-600 hover:text-indigo-800">{{ $event->giftCard->card_number }}</a>
                                </td>
                                <td>
                                    <span class="gc-badge {{ $event->giftCard->issuance_class->createsLiability() ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $event->giftCard->issuance_class->label() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($event->order)
                                        <a href="{{ route('admin.order-management.orders.edit', $event->order->unique_id) }}"
                                           class="text-indigo-600 hover:text-indigo-800">{{ $event->order->order_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-right gc-num">${{ number_format(abs((float) $event->amount), 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="gc-empty">No gift cards have been redeemed yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="text-xs text-gray-500 mt-4">
        Period reporting stays where it belongs: the Sales Summary and the Payment Reconciliation Ledger own the
        canonical figures, and this page does not restate their formulas. The Sales Tax Report is deliberately
        unchanged — a gift-card-funded sale is a taxable sale.
    </p>
@endsection
