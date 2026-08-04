@extends('admin.gift_cards.layouts.workspace', ['pageTitle' => 'All Gift Cards'])

@section('gc-heading', 'All Gift Cards')
@section('gc-subheading', 'Every card issued, purchased or granted. Search by card number, recipient, sender or purchaser.')

@section('gc-actions')
    @if (\App\Services\GiftCards\GiftCardPermissions::canSell(auth()->user()))
        <a href="{{ route('admin.gift-cards.purchased.create') }}" class="gc-btn gc-btn-primary">Sell a Gift Card</a>
    @endif
@endsection

@section('gc-body')

    <form method="GET" action="{{ route('admin.gift-cards.index') }}" class="gc-card p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <label class="gc-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" class="gc-input"
                       placeholder="Card number, recipient, sender or purchaser" autocomplete="off">
            </div>
            <div>
                <label class="gc-label">Issuance</label>
                <select name="class" class="gc-input">
                    <option value="">Purchased and granted</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->value }}" @selected($filters['class'] === $class->value)>{{ $class->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="gc-label">Status</label>
                <select name="status" class="gc-input">
                    <option value="">Any status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-2">
            <button type="submit" class="gc-btn gc-btn-primary">Apply</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.gift-cards.index') }}" class="gc-btn gc-btn-ghost">Clear</a>
            @endif
            <span class="ml-auto text-xs text-gray-500">{{ number_format($cards->total()) }} card(s)</span>
        </div>
    </form>

    <div class="gc-card">
        <div class="gc-scroll">
            <table class="gc-table">
                <thead>
                    <tr>
                        <th>Card Number</th>
                        <th>Issuance</th>
                        <th>Recipient</th>
                        <th class="text-right">Original</th>
                        <th class="text-right">Balance</th>
                        <th>Status</th>
                        <th>Store</th>
                        <th>Activated</th>
                        <th>Last Movement</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cards as $card)
                        <tr>
                            <td class="gc-mono">
                                <a href="{{ route('admin.gift-cards.show', $card->card_number) }}"
                                   class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $card->card_number }}</a>
                            </td>
                            <td>
                                {{-- Purchased is a liability, granted is an expense. The
                                     distinction is the most important thing on this row. --}}
                                <span class="gc-badge {{ $card->issuance_class->createsLiability() ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $card->issuance_class->label() }}
                                </span>
                            </td>
                            <td>{{ $card->recipient_name ?: ($card->recipient?->full_name ?: '—') }}</td>
                            <td class="text-right gc-num text-gray-500">${{ number_format((float) $card->original_value, 2) }}</td>
                            <td class="text-right gc-num font-semibold">${{ number_format((float) $card->cached_balance, 2) }}</td>
                            <td><span class="gc-badge {{ $card->status->badgeColor() }}">{{ $card->status->label() }}</span></td>
                            <td>{{ $card->issuedByStore?->store_name ?: '—' }}</td>
                            <td class="gc-num">{{ $card->activated_at?->format('M j, Y') ?: '—' }}</td>
                            <td class="gc-num">
                                {{ $card->transactions_max_created_at
                                    ? \Carbon\Carbon::parse($card->transactions_max_created_at)->format('M j, Y')
                                    : '—' }}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.gift-cards.show', $card->card_number) }}"
                                   class="text-xs text-indigo-600 hover:text-indigo-800">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="gc-empty">
                                @if (array_filter($filters))
                                    No cards match those filters.
                                @else
                                    No gift cards have been issued yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cards->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $cards->links() }}</div>
        @endif
    </div>
@endsection
