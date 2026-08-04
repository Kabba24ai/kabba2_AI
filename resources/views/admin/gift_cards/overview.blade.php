@extends('admin.gift_cards.layouts.workspace', ['pageTitle' => 'Gift Cards'])

@section('gc-heading', 'Gift Cards')
@section('gc-subheading',
    'The stored-value programme at a glance. Every figure below is summed from the gift card ledger — nothing here is
    estimated or sampled.')

@section('gc-actions')
    @if (\App\Services\GiftCards\GiftCardPermissions::canSell(auth()->user()))
        <a href="{{ route('admin.gift-cards.purchased.create') }}" class="gc-btn gc-btn-primary">Sell a Gift Card</a>
    @endif
@endsection

@section('gc-body')

    @if ($kpis['total_cards'] === 0)
        {{-- Zero state. Says what will appear here, so an empty programme
             reads as "nothing has happened yet" rather than "this is broken". --}}
        <div class="gc-card p-10 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center mb-4">
                <x-heroicon-o-gift class="w-6 h-6 text-indigo-600" />
            </div>
            <h2 class="text-lg font-semibold text-gray-900">No gift cards yet</h2>
            <p class="text-sm text-gray-500 mt-2 max-w-lg mx-auto">
                Once a card is sold or granted, this page shows the money the business holds against outstanding
                cards, the value given away, and the cash actually received — with every movement traceable to a
                ledger entry.
            </p>
            <div class="mt-5 flex items-center justify-center gap-2">
                @if (\App\Services\GiftCards\GiftCardPermissions::canSell(auth()->user()))
                    <a href="{{ route('admin.gift-cards.purchased.create') }}" class="gc-btn gc-btn-primary">Sell the first card</a>
                @endif
                @if (\App\Services\GiftCards\GiftCardPermissions::canGrant(auth()->user()))
                    <a href="{{ route('admin.gift-cards.granted.create') }}" class="gc-btn gc-btn-ghost">Grant a card</a>
                @endif
            </div>
        </div>
    @else

        {{-- ── Position ────────────────────────────────────────────────────
             Purchased and granted are shown side by side and never summed.
             One is money the business OWES, the other money it GAVE AWAY;
             a combined figure would be neither. --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
            <div class="gc-tile">
                <div class="l">Active Cards</div>
                <div class="n gc-num">{{ number_format($kpis['active_cards']) }}</div>
                <div class="s">of {{ number_format($kpis['total_cards']) }} issued</div>
            </div>
            <div class="gc-tile">
                <div class="l">Purchased Outstanding</div>
                <div class="n gc-num">${{ number_format($kpis['purchased_outstanding'], 2) }}</div>
                <div class="s">liability — owed to customers</div>
            </div>
            <div class="gc-tile">
                <div class="l">Granted Outstanding</div>
                <div class="n gc-num">${{ number_format($kpis['granted_outstanding'], 2) }}</div>
                <div class="s">promotional — not a debt</div>
            </div>
            <div class="gc-tile">
                <div class="l">Funding Cash</div>
                <div class="n gc-num">${{ number_format($kpis['funding_cash'], 2) }}</div>
                <div class="s">real money received, net of voids</div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <div class="gc-tile">
                <div class="l">Purchased Issued</div>
                <div class="n gc-num">${{ number_format($kpis['purchased_issued'], 2) }}</div>
            </div>
            <div class="gc-tile">
                <div class="l">Purchased Redeemed</div>
                <div class="n gc-num">${{ number_format($kpis['purchased_redeemed'], 2) }}</div>
            </div>
            <div class="gc-tile">
                <div class="l">Granted Issued</div>
                <div class="n gc-num">${{ number_format($kpis['granted_issued'], 2) }}</div>
            </div>
            <div class="gc-tile">
                <div class="l">Granted Redeemed</div>
                <div class="n gc-num">${{ number_format($kpis['granted_redeemed'], 2) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

            <div class="gc-card">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Recent Cards</h2>
                    <a href="{{ route('admin.gift-cards.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View all</a>
                </div>
                <div class="gc-scroll">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>Card</th><th>Class</th><th>Recipient</th>
                                <th class="text-right">Balance</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentCards as $card)
                                <tr>
                                    <td class="gc-mono">
                                        <a href="{{ route('admin.gift-cards.show', $card->card_number) }}"
                                           class="text-indigo-600 hover:text-indigo-800">{{ $card->card_number }}</a>
                                    </td>
                                    <td>{{ $card->issuance_class->label() }}</td>
                                    <td>{{ $card->recipient_name ?: ($card->recipient?->full_name ?: '—') }}</td>
                                    <td class="text-right gc-num">${{ number_format((float) $card->cached_balance, 2) }}</td>
                                    <td><span class="gc-badge {{ $card->status->badgeColor() }}">{{ $card->status->label() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="gc-empty">No cards yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="gc-card">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Recent Transactions</h2>
                    <a href="{{ route('admin.gift-cards.transactions') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View all</a>
                </div>
                <div class="gc-scroll">
                    <table class="gc-table">
                        <thead>
                            <tr><th>When</th><th>Card</th><th>Movement</th><th class="text-right">Amount</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($recentTransactions as $txn)
                                <tr>
                                    <td class="gc-num">{{ $txn->created_at?->format('M j, g:ia') }}</td>
                                    <td class="gc-mono">
                                        <a href="{{ route('admin.gift-cards.show', $txn->giftCard->card_number) }}"
                                           class="text-indigo-600 hover:text-indigo-800">{{ $txn->giftCard->card_number }}</a>
                                    </td>
                                    <td>{{ $txn->type->label() }}</td>
                                    {{-- Signed as stored: a debit reads as a debit. --}}
                                    <td class="text-right gc-num {{ (float) $txn->amount < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                        {{ (float) $txn->amount < 0 ? '−' : '+' }}${{ number_format(abs((float) $txn->amount), 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="gc-empty">No transactions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    @endif
@endsection
