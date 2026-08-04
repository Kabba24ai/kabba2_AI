{{--
    Confirmation forms for the administrative actions.

    Each one posts to its own route and requires a reason. There is no
    "are you sure?" that submits without one — the reason IS the confirmation,
    because it is what makes the action reviewable afterwards.

    Driven by the `action` property on the surrounding x-data in show.blade.php.
--}}

@php
    $modals = [
        'suspend' => [
            'title' => 'Suspend this gift card',
            'blurb' => 'Blocks the card without destroying its value. The balance stays exactly where it is and the card can be reinstated later. Use this while a dispute or suspected fraud is open — not to write a card off.',
            'route' => route('admin.gift-cards.suspend', $card->card_number),
            'cta' => 'Suspend Card',
            'tone' => 'amber',
            'placeholder' => 'Reported stolen by the recipient on 3 August.',
        ],
        'reinstate' => [
            'title' => 'Reinstate this gift card',
            'blurb' => 'Returns the card to circulation with its balance intact. If it was spent to zero while suspended it will correctly return as fully redeemed rather than active.',
            'route' => route('admin.gift-cards.reinstate', $card->card_number),
            'cta' => 'Reinstate Card',
            'tone' => 'emerald',
            'placeholder' => 'Dispute resolved in the customer’s favour.',
        ],
        'cancel' => [
            'title' => 'Cancel this gift card',
            'blurb' => 'Permanent. Any remaining value is written off as an explicit negative entry in the ledger, naming you and your reason — money never simply disappears from a card.',
            'route' => route('admin.gift-cards.cancel', $card->card_number),
            'cta' => 'Cancel Card',
            'tone' => 'red',
            'placeholder' => 'Issued in error — duplicate of GC-1234-5678.',
        ],
        'replace' => [
            'title' => 'Replace this gift card',
            'blurb' => 'Moves the remaining balance onto a brand-new card and retires this one, so a recovered original cannot be spent alongside its replacement. The new card keeps this card’s purchased or granted classification.',
            'route' => route('admin.gift-cards.replace', $card->card_number),
            'cta' => 'Replace Card',
            'tone' => 'indigo',
            'placeholder' => 'Card damaged in the post; customer called to request a replacement.',
        ],
    ];

    $tones = [
        'amber' => 'border-amber-200 bg-amber-50 text-amber-900',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'red' => 'border-red-200 bg-red-50 text-red-900',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-900',
    ];
@endphp

@foreach ($modals as $key => $modal)
    <div x-show="action === '{{ $key }}'" x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/50"
         @keydown.escape.window="action = null">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg" @click.outside="action = null">
            <form method="POST" action="{{ $modal['route'] }}">
                @csrf
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">{{ $modal['title'] }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5 gc-mono">{{ $card->card_number }} · ${{ number_format($balance, 2) }} remaining</p>
                </div>

                <div class="px-5 py-4">
                    <div class="rounded-lg border px-3 py-2.5 text-xs mb-4 {{ $tones[$modal['tone']] }}">
                        {{ $modal['blurb'] }}
                    </div>

                    <label class="gc-label">Reason <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required minlength="5" maxlength="500" class="gc-input"
                              placeholder="{{ $modal['placeholder'] }}"></textarea>
                    <div class="gc-hint">Recorded permanently against this card. Write it for whoever reviews it next.</div>
                </div>

                <div class="px-5 py-4 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" class="gc-btn gc-btn-ghost" @click="action = null">Cancel</button>
                    <button type="submit" class="gc-btn {{ $modal['tone'] === 'red' ? 'gc-btn-danger' : 'gc-btn-primary' }}">
                        {{ $modal['cta'] }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach

{{-- Adjustment has its own shape: a direction and an amount as well as a reason. --}}
<div x-show="action === 'adjust'" x-cloak
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/50"
     @keydown.escape.window="action = null">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg" @click.outside="action = null">
        <form method="POST" action="{{ route('admin.gift-cards.adjust', $card->card_number) }}"
              x-data="{ direction: 'increase' }">
            @csrf
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Adjust balance by hand</h3>
                <p class="text-xs text-gray-500 mt-0.5 gc-mono">{{ $card->card_number }} · ${{ number_format($balance, 2) }} remaining</p>
            </div>

            <div class="px-5 py-4">
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-900 mb-4">
                    Adding value creates spendable money from nothing — the same power as granting a card, in a
                    different shape. Removing value cannot take the balance below zero.
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <label class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer"
                           :class="direction === 'increase' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300'">
                        <input type="radio" name="direction" value="increase" x-model="direction" checked>
                        <span class="text-sm font-medium">Add value</span>
                    </label>
                    <label class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer"
                           :class="direction === 'decrease' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300'">
                        <input type="radio" name="direction" value="decrease" x-model="direction">
                        <span class="text-sm font-medium">Remove value</span>
                    </label>
                </div>

                <label class="gc-label">Amount <span class="text-red-500">*</span></label>
                <div class="relative mb-4">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">$</span>
                    <input type="number" name="amount" step="0.01" min="0.01" max="10000" required class="gc-input pl-7">
                </div>

                <label class="gc-label">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="2" required minlength="5" maxlength="500" class="gc-input"
                          placeholder="Goodwill top-up approved by store manager after a repeat delivery failure."></textarea>

                <label class="gc-label mt-3">Note (optional)</label>
                <input type="text" name="note" maxlength="500" class="gc-input" placeholder="Approval reference, ticket number…">
            </div>

            <div class="px-5 py-4 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" class="gc-btn gc-btn-ghost" @click="action = null">Cancel</button>
                <button type="submit" class="gc-btn gc-btn-primary">Apply Adjustment</button>
            </div>
        </form>
    </div>
</div>
