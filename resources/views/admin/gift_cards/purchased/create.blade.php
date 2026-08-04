@extends('admin.gift_cards.layouts.workspace', ['pageTitle' => 'Sell a Gift Card'])

@section('gc-heading', 'Sell a Gift Card')
@section('gc-subheading',
    'Records money already taken and issues stored value of equal amount. No sales tax and no product revenue are created
    here — the sale is recognised later, when the card is redeemed against an order.')

@section('gc-body')
    <div x-data="{
        amount: '{{ old('amount') }}',
        method: '{{ old('funding_method', 'Cash') }}',
        recipient: '{{ addslashes(old('recipient_name')) }}',
        sender: '{{ addslashes(old('sender_name')) }}',
        message: '{{ addslashes(old('message')) }}',
        submitting: false,
    }">
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

            {{-- ── Form ──────────────────────────────────────────────────── --}}
            <form method="POST" action="{{ route('admin.gift-cards.purchased.store') }}"
                  class="xl:col-span-3 gc-card p-5" @submit="submitting = true">
                @csrf
                {{-- Reused across retries of this one submission, so a
                     double-click issues one card rather than two. --}}
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 mb-5">
                    <div class="text-sm font-semibold text-blue-900">Purchased Gift Card — Customer Paid</div>
                    <p class="text-xs text-blue-800 mt-1">
                        This opens a liability: the business now owes goods against money it has received.
                        Choose the method the customer <em>actually</em> paid with.
                    </p>
                </div>

                <h3 class="text-sm font-semibold text-gray-900 mb-3">Value and funding</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="gc-label">Amount <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">$</span>
                            <input type="number" name="amount" x-model="amount" step="0.01" min="0.01" max="10000"
                                   class="gc-input pl-7" required value="{{ old('amount') }}" placeholder="100.00">
                        </div>
                        <div class="gc-hint">The card is worth exactly what was paid. No tax is taken out.</div>
                    </div>

                    <div>
                        <label class="gc-label">How the customer paid <span class="text-red-500">*</span></label>
                        <select name="funding_method" x-model="method" class="gc-input" required>
                            @foreach ($fundingMethods as $fm)
                                <option value="{{ $fm->value }}" @selected(old('funding_method') === $fm->value)>{{ $fm->label() }}</option>
                            @endforeach
                        </select>
                        {{-- Said plainly because the word "card" here means the
                             tender the customer used, not this gift card, and
                             because no gateway call happens on this screen. --}}
                        <div class="gc-hint">
                            Recorded, not charged — take the payment first, then record it here.
                        </div>
                    </div>

                    <div>
                        <label class="gc-label">Reference / transaction ID</label>
                        <input type="text" name="funding_transaction_id" class="gc-input"
                               value="{{ old('funding_transaction_id') }}" placeholder="Processor ref, cheque no., receipt no.">
                        <div class="gc-hint">What this funding reconciles against on a settlement or deposit.</div>
                    </div>

                    <div>
                        <label class="gc-label">Issuing store</label>
                        <select name="issued_by_store_id" class="gc-input">
                            <option value="">Not specified</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected((string) old('issued_by_store_id') === (string) $store->id)>{{ $store->store_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr class="my-5 border-gray-100">

                <h3 class="text-sm font-semibold text-gray-900 mb-3">What appears on the card</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="gc-label">Recipient name</label>
                        <input type="text" name="recipient_name" x-model="recipient" class="gc-input"
                               value="{{ old('recipient_name') }}" maxlength="120" placeholder="Daniel Reyes">
                    </div>
                    <div>
                        <label class="gc-label">From</label>
                        <input type="text" name="sender_name" x-model="sender" class="gc-input"
                               value="{{ old('sender_name') }}" maxlength="120" placeholder="The Whitfield Family">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="gc-label">Personal message</label>
                        <input type="text" name="message" x-model="message" class="gc-input"
                               value="{{ old('message') }}" maxlength="300"
                               placeholder="For the projects you keep talking about.">
                    </div>
                    <div>
                        <label class="gc-label">Recipient email</label>
                        <input type="email" name="recipient_email" class="gc-input"
                               value="{{ old('recipient_email') }}" maxlength="190">
                        <div class="gc-hint">Stored for later delivery. Nothing is emailed in this release.</div>
                    </div>
                    <div>
                        <label class="gc-label">Purchasing customer</label>
                        <input type="number" name="purchaser_customer_id" class="gc-input"
                               value="{{ old('purchaser_customer_id') }}" placeholder="Customer ID (optional)">
                        <div class="gc-hint">Optional — a card can be bought without an account.</div>
                    </div>
                </div>

                <hr class="my-5 border-gray-100">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="gc-label">PIN (optional)</label>
                        <input type="text" name="pin" class="gc-input" inputmode="numeric" maxlength="6"
                               placeholder="4–6 digits">
                        <div class="gc-hint">Required at redemption if set. Stored hashed, never in plain text.</div>
                    </div>
                    <div>
                        <label class="gc-label">Funding date</label>
                        <input type="datetime-local" name="funded_at" class="gc-input" value="{{ old('funded_at') }}">
                        <div class="gc-hint">Leave blank for now. Controls the period the cash lands in.</div>
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="gc-btn gc-btn-primary" :disabled="submitting"
                            x-text="submitting ? 'Issuing…' : 'Issue Gift Card'">Issue Gift Card</button>
                    <a href="{{ route('admin.gift-cards.overview') }}" class="gc-btn gc-btn-ghost">Cancel</a>
                </div>
            </form>

            {{-- ── Live preview of the approved artwork ───────────────────── --}}
            <div class="xl:col-span-2">
                <div class="gc-card p-4 sticky top-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">Card preview</h3>
                    <p class="text-xs text-gray-500 mb-3">
                        The approved design with your values. The card number is generated on save.
                    </p>

                    {{-- Rendered server-side at the approved proportions, then
                         updated in place as the operator types. The artwork is
                         never rebuilt in JavaScript — only its text is
                         replaced — so the preview cannot drift from the
                         component the detail page renders. --}}
                    <div id="gc-preview">
                        <x-admin.gift-cards.card-artwork
                            :scale="0.42"
                            :overrides="[
                                'amount' => old('amount') ?: '100',
                                'recipient' => old('recipient_name') ?: '—',
                                'sender' => old('sender_name') ?: '—',
                                'message' => old('message') ?: '',
                                'card_number' => 'Generated on save',
                            ]" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            // Keep the preview in step with the form. Text nodes only — the
            // artwork itself is server-rendered and stays untouched.
            (function () {
                const preview = document.getElementById('gc-preview');
                if (!preview) return;

                const bind = (inputName, selector, fallback, transform) => {
                    const input = document.querySelector(`[name="${inputName}"]`);
                    const target = preview.querySelector(selector);
                    if (!input || !target) return;
                    input.addEventListener('input', () => {
                        const raw = input.value.trim();
                        target.textContent = raw ? (transform ? transform(raw) : raw) : fallback;
                    });
                };

                bind('amount', '.gcx-figure', '100', v => {
                    const n = parseFloat(v);
                    if (isNaN(n)) return '100';
                    return n % 1 === 0 ? n.toLocaleString('en-US') : n.toFixed(2);
                });
                bind('recipient_name', '.gcx-band-value', '—');

                // Two fields share the .gcx-band-value class; the second is "From".
                const senderInput = document.querySelector('[name="sender_name"]');
                const bandValues = preview.querySelectorAll('.gcx-band-value');
                if (senderInput && bandValues.length > 1) {
                    senderInput.addEventListener('input', () => {
                        bandValues[1].textContent = senderInput.value.trim() || '—';
                    });
                }

                // The message element is absent when empty, so it is created
                // on first keystroke rather than assumed to exist.
                const messageInput = document.querySelector('[name="message"]');
                if (messageInput) {
                    messageInput.addEventListener('input', () => {
                        let el = preview.querySelector('.gcx-message');
                        const text = messageInput.value.trim();
                        if (!el && text) {
                            el = document.createElement('div');
                            el.className = 'gcx-message';
                            preview.querySelector('.gcx-value').appendChild(el);
                        }
                        if (el) el.textContent = text;
                    });
                }
            })();
        </script>
    @endpush
@endsection
