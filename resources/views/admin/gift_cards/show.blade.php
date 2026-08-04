@extends('admin.gift_cards.layouts.workspace', ['pageTitle' => 'Gift Card ' . $card->card_number])

@section('gc-heading', $card->card_number)
@section('gc-subheading',
    $card->issuance_class->createsLiability()
        ? 'Purchased card — money was received and this value is owed to the customer.'
        : 'Granted card — merchant-funded promotional value. No customer payment was taken.')

@section('gc-actions')
    <a href="{{ route('admin.gift-cards.index') }}" class="gc-btn gc-btn-ghost">Back to list</a>
@endsection

@section('gc-body')
    <div x-data="{ action: null }">

        {{-- ── The approved artwork, populated with this card ─────────────── --}}
        <div class="gc-card p-5 mb-4">
            <div class="flex items-start justify-between gap-4 flex-wrap mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Card as the customer sees it</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        The approved design at CR80 proportions, showing the current balance.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="gc-badge {{ $card->status->badgeColor() }}">{{ $card->status->label() }}</span>
                    <span class="gc-badge {{ $card->issuance_class->createsLiability() ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                        {{ $card->issuance_class->label() }}
                    </span>
                </div>
            </div>

            <div class="flex justify-center">
                <x-admin.gift-cards.card-artwork :card="$card" :scale="0.72" />
            </div>
        </div>

        {{-- ── Position ───────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div class="gc-tile">
                <div class="l">Current Balance</div>
                <div class="n gc-num">${{ number_format($balance, 2) }}</div>
                <div class="s">summed from the ledger</div>
            </div>
            <div class="gc-tile">
                <div class="l">Original Value</div>
                <div class="n gc-num">${{ number_format((float) $card->original_value, 2) }}</div>
            </div>
            <div class="gc-tile">
                <div class="l">Classification</div>
                <div class="n" style="font-size:1.05rem">
                    {{ $card->issuance_class->createsLiability() ? 'Purchased liability' : 'Promotional value' }}
                </div>
                <div class="s">
                    {{ $card->issuance_class->createsLiability() ? 'owed to the customer' : 'given away — an expense' }}
                </div>
            </div>
            <div class="gc-tile">
                <div class="l">Movements</div>
                <div class="n gc-num">{{ $card->transactions->count() }}</div>
                <div class="s">ledger entries</div>
            </div>
        </div>

        @if ($cacheIsStale)
            {{-- The ledger is canonical. If the cached column disagrees, say so
                 rather than quietly showing whichever value was read first. --}}
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <strong>Cached balance is out of step with the ledger.</strong>
                The figure shown above (${{ number_format($balance, 2) }}) is summed from the ledger and is correct.
                The cached column reads ${{ number_format((float) $card->cached_balance, 2) }}. Any operation on this
                card will rebuild the cache.
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

            {{-- ── Details ────────────────────────────────────────────────── --}}
            <div class="gc-card p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Details</h2>
                <dl class="text-sm space-y-2.5">
                    @php
                        $rows = [
                            'Card number' => $card->card_number,
                            'Recipient' => $card->recipient_name ?: ($card->recipient?->full_name ?: '—'),
                            'Recipient email' => $card->recipient_email ?: '—',
                            'From' => $card->sender_name ?: '—',
                            'Purchaser' => $card->purchaser?->full_name ?: '—',
                            'Issuing store' => $card->issuedByStore?->store_name ?: '—',
                            'Activated' => $card->activated_at?->format('M j, Y g:ia') ?: '—',
                            'Last used' => $card->transactions->where('type', \App\Enums\GiftCards\GiftCardTransactionType::Redemption)->last()?->created_at?->format('M j, Y g:ia') ?: 'Never',
                        ];
                    @endphp
                    @foreach ($rows as $label => $value)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 flex-none">{{ $label }}</dt>
                            <dd class="text-gray-900 text-right {{ $label === 'Card number' ? 'gc-mono' : '' }}">{{ $value }}</dd>
                        </div>
                    @endforeach

                    @if ($card->message)
                        <div class="pt-2 border-t border-gray-100">
                            <dt class="text-gray-500 mb-1">Message</dt>
                            <dd class="text-gray-900 italic">“{{ $card->message }}”</dd>
                        </div>
                    @endif

                    @if ($card->issuance_class->isPromotional())
                        <div class="pt-2 border-t border-gray-100">
                            <dt class="text-gray-500 mb-1">Grant reason</dt>
                            <dd class="text-gray-900">
                                {{ \Illuminate\Support\Str::headline($card->grant_reason_category ?? '—') }}
                                @if ($card->grant_note)
                                    <div class="text-xs text-gray-600 mt-1">{{ $card->grant_note }}</div>
                                @endif
                            </dd>
                        </div>
                    @endif

                    @if ($card->replacedBy)
                        <div class="pt-2 border-t border-gray-100">
                            <dt class="text-gray-500 mb-1">Replaced by</dt>
                            <dd>
                                <a href="{{ route('admin.gift-cards.show', $card->replacedBy->card_number) }}"
                                   class="gc-mono text-indigo-600 hover:text-indigo-800">{{ $card->replacedBy->card_number }}</a>
                            </dd>
                        </div>
                    @endif

                    @if ($card->suspension_reason && $card->status === \App\Enums\GiftCards\GiftCardStatus::Suspended)
                        <div class="pt-2 border-t border-gray-100">
                            <dt class="text-gray-500 mb-1">Suspended because</dt>
                            <dd class="text-gray-900">{{ $card->suspension_reason }}</dd>
                        </div>
                    @endif
                </dl>

                @php
                    // Funding belongs to a purchased card only. A granted card has
                    // no funding row at all — that absence is the accounting fact,
                    // not missing data.
                    $funding = $card->transactions
                        ->firstWhere('type', \App\Enums\GiftCards\GiftCardTransactionType::IssuancePurchased);
                @endphp
                @if ($funding)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <h3 class="text-xs font-semibold text-gray-900 uppercase tracking-wide mb-2">Funding</h3>
                        <dl class="text-sm space-y-2">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Method</dt>
                                <dd class="text-gray-900">{{ $funding->funding_payment_method?->label() ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Cash received</dt>
                                <dd class="text-gray-900 gc-num">${{ number_format((float) $funding->funding_cash_amount, 2) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Reference</dt>
                                <dd class="text-gray-900 gc-mono text-xs">{{ $funding->funding_transaction_id ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                @elseif ($card->issuance_class->isPromotional())
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="rounded-md bg-purple-50 border border-purple-200 px-3 py-2 text-xs text-purple-900">
                            No funding record — nobody paid for this card. That absence is deliberate and is what keeps
                            granted value out of the purchased-card liability.
                        </div>
                    </div>
                @endif
            </div>

            {{-- ── Ledger ─────────────────────────────────────────────────── --}}
            <div class="gc-card xl:col-span-2">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Transaction Ledger</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Append-only and canonical. Oldest first — this is the story of the card.
                    </p>
                </div>
                <div class="gc-scroll">
                    <table class="gc-table">
                        <thead>
                            <tr>
                                <th>When</th><th>Movement</th>
                                <th class="text-right">Amount</th><th class="text-right">Balance</th>
                                <th>Order</th><th>Reason</th><th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($card->transactions as $txn)
                                <tr>
                                    <td class="gc-num">{{ $txn->created_at?->format('M j, Y g:ia') }}</td>
                                    <td>{{ $txn->type->label() }}</td>
                                    <td class="text-right gc-num {{ (float) $txn->amount < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                        {{ (float) $txn->amount < 0 ? '−' : '+' }}${{ number_format(abs((float) $txn->amount), 2) }}
                                    </td>
                                    <td class="text-right gc-num text-gray-500">${{ number_format((float) $txn->balance_after, 2) }}</td>
                                    <td>
                                        @if ($txn->order)
                                            <a href="{{ route('admin.order-management.orders.edit', $txn->order->unique_id) }}"
                                               class="text-indigo-600 hover:text-indigo-800">{{ $txn->order->order_number }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="max-w-xs">
                                        <span class="text-xs text-gray-600">{{ $txn->reason ?: ($txn->note ?: '—') }}</span>
                                    </td>
                                    <td class="text-xs text-gray-500">{{ $txn->createdBy?->full_name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="gc-empty">No movements recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Administrative actions ──────────────────────────────────────── --}}
        @if ($can['suspend'] || $can['cancel'] || $can['replace'] || $can['adjust'])
            <div class="gc-card p-5 mt-4">
                <h2 class="text-sm font-semibold text-gray-900">Administrative Actions</h2>
                <p class="text-xs text-gray-500 mt-0.5 mb-4">
                    Every action below writes to the ledger through the gift card service. There are no direct
                    balance edits — a change that is not in the ledger did not happen.
                </p>

                <div class="flex flex-wrap gap-2">
                    @if ($can['suspend'])
                        @if ($card->status === \App\Enums\GiftCards\GiftCardStatus::Suspended)
                            <button type="button" class="gc-btn gc-btn-ghost" @click="action = 'reinstate'">Reinstate</button>
                        @elseif (! $card->status->isTerminal())
                            <button type="button" class="gc-btn gc-btn-ghost" @click="action = 'suspend'">Suspend</button>
                        @endif
                    @endif

                    @if ($can['adjust'] && ! $card->status->isTerminal())
                        <button type="button" class="gc-btn gc-btn-ghost" @click="action = 'adjust'">Adjust Balance</button>
                    @endif

                    @if ($can['replace'] && ! $card->status->isTerminal() && $balance > 0)
                        <button type="button" class="gc-btn gc-btn-ghost" @click="action = 'replace'">Replace Card</button>
                    @endif

                    @if ($can['cancel'] && $card->status !== \App\Enums\GiftCards\GiftCardStatus::Cancelled)
                        <button type="button" class="gc-btn gc-btn-danger" @click="action = 'cancel'">Cancel Card</button>
                    @endif
                </div>

                @if ($card->status->isTerminal())
                    <p class="text-xs text-gray-500 mt-3">
                        This card is {{ strtolower($card->status->label()) }} — no further value movement is expected.
                    </p>
                @endif
            </div>

            @include('admin.gift_cards.partials._action_modals', ['card' => $card, 'balance' => $balance])
        @endif
    </div>
@endsection
