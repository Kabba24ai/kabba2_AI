@extends('admin.gift_cards.layouts.workspace', ['pageTitle' => 'Gift Card Transactions'])

@section('gc-heading', 'Gift Card Transactions')
@section('gc-subheading',
    'Every movement across every card, newest first. Amounts are shown as they are stored — a debit reads as a debit.')

@section('gc-body')

    <form method="GET" action="{{ route('admin.gift-cards.transactions') }}" class="gc-card p-4 mb-4">
        <div class="flex items-end gap-3 flex-wrap">
            <div class="min-w-[16rem]">
                <label class="gc-label">Movement type</label>
                <select name="type" class="gc-input">
                    <option value="">All movements</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="gc-btn gc-btn-primary">Apply</button>
            @if ($filters['type'])
                <a href="{{ route('admin.gift-cards.transactions') }}" class="gc-btn gc-btn-ghost">Clear</a>
            @endif
            <span class="ml-auto text-xs text-gray-500">{{ number_format($transactions->total()) }} movement(s)</span>
        </div>
    </form>

    <div class="gc-card">
        <div class="gc-scroll">
            <table class="gc-table">
                <thead>
                    <tr>
                        <th>When</th><th>Card</th><th>Class</th><th>Movement</th>
                        <th class="text-right">Amount</th><th class="text-right">Balance After</th>
                        <th>Order</th><th>Cash?</th><th>Reason</th><th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $txn)
                        <tr>
                            <td class="gc-num">{{ $txn->created_at?->format('M j, Y g:ia') }}</td>
                            <td class="gc-mono">
                                <a href="{{ route('admin.gift-cards.show', $txn->giftCard->card_number) }}"
                                   class="text-indigo-600 hover:text-indigo-800">{{ $txn->giftCard->card_number }}</a>
                            </td>
                            <td>
                                <span class="gc-badge {{ $txn->giftCard->issuance_class->createsLiability() ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $txn->giftCard->issuance_class->label() }}
                                </span>
                            </td>
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
                            <td>
                                {{-- The single most important column for reconciliation:
                                     did external money move, or only stored value? --}}
                                @if ($txn->bringsExternalCash())
                                    <span class="gc-badge bg-emerald-100 text-emerald-800">Cash in</span>
                                @else
                                    <span class="text-xs text-gray-400">No</span>
                                @endif
                            </td>
                            <td class="max-w-xs"><span class="text-xs text-gray-600">{{ $txn->reason ?: ($txn->note ?: '—') }}</span></td>
                            <td class="text-xs text-gray-500">{{ $txn->createdBy?->full_name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="gc-empty">No gift card movements recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $transactions->links() }}</div>
        @endif
    </div>
@endsection
