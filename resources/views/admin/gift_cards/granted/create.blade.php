@extends('admin.gift_cards.layouts.workspace', ['pageTitle' => 'Grant a Gift Card'])

@section('gc-heading', 'Grant a Gift Card')
@section('gc-subheading',
    'Issues stored value the business is giving away. No customer payment is taken and no liability is opened — this is a
    promotional expense, and it is classified that way from the moment it exists.')

@section('gc-body')
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

        <form method="POST" action="{{ route('admin.gift-cards.granted.store') }}" class="xl:col-span-3 gc-card p-5">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

            {{-- The most important sentence on the page. Selling and granting
                 look alike and are not; this banner is what tells them apart
                 at a glance. --}}
            <div class="rounded-lg border border-purple-200 bg-purple-50 px-4 py-3 mb-5">
                <div class="text-sm font-semibold text-purple-900">Granted Gift Card — No Customer Payment</div>
                <p class="text-xs text-purple-800 mt-1">
                    You are creating spendable value that nobody paid for. It will still be fully taxed when redeemed,
                    but it is reported as promotional value given away, never as money owed.
                </p>
            </div>

            <h3 class="text-sm font-semibold text-gray-900 mb-3">Value and reason</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="gc-label">Amount <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">$</span>
                        <input type="number" name="amount" step="0.01" min="0.01" max="10000"
                               class="gc-input pl-7" required value="{{ old('amount') }}" placeholder="50.00">
                    </div>
                </div>

                <div>
                    <label class="gc-label">Reason category <span class="text-red-500">*</span></label>
                    <select name="grant_reason_category" class="gc-input" required>
                        <option value="">Choose a reason…</option>
                        @foreach ($reasonCategories as $value => $label)
                            <option value="{{ $value }}" @selected(old('grant_reason_category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="gc-label">Internal note <span class="text-red-500">*</span></label>
                    <textarea name="grant_note" class="gc-input" rows="3" required maxlength="500"
                              placeholder="Equipment arrived two days late on order 1042; approved by store manager.">{{ old('grant_note') }}</textarea>
                    <div class="gc-hint">
                        This is the audit record. Write it for someone reviewing the decision months from now.
                    </div>
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

                <div>
                    <label class="gc-label">Recipient customer</label>
                    <input type="number" name="recipient_customer_id" class="gc-input"
                           value="{{ old('recipient_customer_id') }}" placeholder="Customer ID (optional)">
                </div>
            </div>

            <hr class="my-5 border-gray-100">

            <h3 class="text-sm font-semibold text-gray-900 mb-3">What appears on the card</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="gc-label">Recipient name</label>
                    <input type="text" name="recipient_name" class="gc-input" value="{{ old('recipient_name') }}"
                           maxlength="120" placeholder="Daniel Reyes">
                </div>
                <div>
                    <label class="gc-label">From</label>
                    <input type="text" name="sender_name" class="gc-input"
                           value="{{ old('sender_name', \App\Helpers\ConfigurationHelper::getSettings('Company Settings', 'company_name')) }}"
                           maxlength="120">
                    <div class="gc-hint">Defaults to the business — nobody bought this card.</div>
                </div>
                <div class="sm:col-span-2">
                    <label class="gc-label">Personal message</label>
                    <input type="text" name="message" class="gc-input" value="{{ old('message') }}" maxlength="300"
                           placeholder="With our apologies — and our thanks for your patience.">
                </div>
                <div>
                    <label class="gc-label">Recipient email</label>
                    <input type="email" name="recipient_email" class="gc-input" value="{{ old('recipient_email') }}" maxlength="190">
                    <div class="gc-hint">Stored for later delivery. Nothing is emailed in this release.</div>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="gc-btn gc-btn-primary">Grant Gift Card</button>
                <a href="{{ route('admin.gift-cards.overview') }}" class="gc-btn gc-btn-ghost">Cancel</a>
            </div>
        </form>

        <div class="xl:col-span-2">
            <div class="gc-card p-4 sticky top-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-1">Card preview</h3>
                <p class="text-xs text-gray-500 mb-3">
                    A granted card is visually identical to a purchased one — the difference is in the accounts,
                    not on the card.
                </p>
                <x-admin.gift-cards.card-artwork
                    :scale="0.42"
                    :overrides="[
                        'amount' => old('amount') ?: '50',
                        'recipient' => old('recipient_name') ?: '—',
                        'sender' => old('sender_name') ?: \App\Helpers\ConfigurationHelper::getSettings('Company Settings', 'company_name'),
                        'message' => old('message') ?: '',
                        'card_number' => 'Generated on save',
                    ]" />
            </div>
        </div>
    </div>
@endsection
