{{--
    Goodwill Adjustment — manager-authorised pre-tax concession (FD-002).

    A SEPARATE partial rather than more inline markup in edit.blade.php, which
    is already 8,000+ lines. Everything Goodwill needs is self-contained here.

    WORKFLOW POSITION. This appears only AFTER real tender has been recorded
    through the existing Receive Payment flow, which this file does not touch.
    Goodwill is applied against money that has actually settled — it is never
    bundled into the payment submit, because that would put a live gateway
    charge inside a transaction a Goodwill refusal could roll back.

    The panel is hidden for states the writer would refuse anyway (no payment
    yet, already paid, AR posted, invoiced). That is a COURTESY, not the
    enforcement: every one of those conditions is re-checked server-side inside
    the service's transaction under the order row lock. Hiding a button is not
    a security control.
--}}
@php
    use App\Enums\Orders\GoodwillReasonCode;

    $gwAdjustment = \App\Models\Orders\OrderGoodwillAdjustment::where('order_id', $order->id)
        ->whereNull('reversed_at')
        ->latest('id')
        ->first();

    $gwAccepted = (float) $order->total_paid;
    $gwBalance  = (float) $order->balance_due;

    // Known-unsupported states, mirrored from GoodwillAdjustmentService::applyBlocker().
    $gwHasAr = \Illuminate\Support\Facades\DB::table('customer_accounts')
        ->where('order_id', $order->id)->whereNull('deleted_at')->exists();

    $gwHasInvoice = $order->invoice_id !== null
        || \Illuminate\Support\Facades\DB::table('customer_accounts')
            ->where('order_id', $order->id)->whereNotNull('invoice_id')->whereNull('deleted_at')->exists();

    $gwBlockedReason = match (true) {
        $gwHasAr      => 'This order has been posted to the customer\'s account. Use a credit memo or account adjustment instead.',
        $gwHasInvoice => 'This order is on an issued invoice. Issue a credit memo against the invoice instead.',
        default       => null,
    };

    $gwEligible = ! $gwAdjustment && ! $gwBlockedReason && $gwAccepted > 0 && $gwBalance > 0.005;
@endphp

@if($gwAdjustment)
    {{-- Applied: show what management approved, and offer a reversal. --}}
    <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4" id="goodwill-applied-panel">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold text-amber-900">Goodwill Adjustment Applied</h3>
                <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-sm text-amber-900">
                    <dt>Amount waived</dt>
                    <dd class="text-right font-semibold">{{ \App\Helpers\CustomHelper::formatCurrency($gwAdjustment->goodwill_amount) }}</dd>

                    <dt>Reason</dt>
                    <dd class="text-right">{{ $gwAdjustment->reason_code?->label() }}</dd>

                    @if($gwAdjustment->reason_note)
                        <dt>Note</dt>
                        <dd class="text-right">{{ $gwAdjustment->reason_note }}</dd>
                    @endif

                    <dt>Approved by</dt>
                    <dd class="text-right">{{ $gwAdjustment->approvedBy?->full_name ?? '—' }}</dd>

                    <dt>Applied</dt>
                    <dd class="text-right">{{ $gwAdjustment->created_at?->format(config('app.date.date_time_format')) }}</dd>

                    <dt>Payments accepted</dt>
                    <dd class="text-right">{{ \App\Helpers\CustomHelper::formatCurrency($gwAdjustment->payments_accepted) }}</dd>
                </dl>
                <p class="mt-2 text-xs text-amber-800">
                    Goodwill is not a payment. The order's product cost was reduced before tax so the
                    revised total equals the tender actually received.
                </p>
            </div>

            <button type="button" id="goodwill-reverse-open"
                class="shrink-0 rounded border border-amber-400 px-3 py-1.5 text-sm font-medium text-amber-900 hover:bg-amber-100">
                Reverse
            </button>
        </div>
    </div>
@elseif($gwBlockedReason)
    <div class="mt-4 rounded-lg border border-gray-300 bg-gray-50 p-4 text-sm text-gray-600">
        <span class="font-medium">Goodwill Adjustment unavailable.</span> {{ $gwBlockedReason }}
    </div>
@elseif($gwEligible)
    <div class="mt-4 rounded-lg border border-gray-300 bg-white p-4" id="goodwill-offer-panel">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">Accept as Payment in Full</h3>
                <p class="mt-1 text-sm text-gray-600">
                    {{ \App\Helpers\CustomHelper::formatCurrency($gwAccepted) }} has been collected and
                    {{ \App\Helpers\CustomHelper::formatCurrency($gwBalance) }} remains. A manager may waive the
                    remainder as a Goodwill Adjustment, reducing the product cost before tax.
                </p>
            </div>
            <button type="button" id="goodwill-open"
                class="shrink-0 rounded bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700">
                Apply Goodwill
            </button>
        </div>
    </div>

    {{-- Confirmation modal. Figures are NEVER computed here: they come from
         the preview endpoint, which calls the same calculate() the writer
         calls, so what the manager approves is what gets committed. --}}
    <div id="goodwill-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-lg rounded-lg bg-white shadow-xl">
            <div class="border-b px-5 py-3">
                <h3 class="text-base font-semibold text-gray-900">Apply Goodwill Adjustment</h3>
            </div>

            <form id="goodwill-form" class="px-5 py-4">
                @csrf
                <input type="hidden" name="idempotency_token" id="goodwill-idempotency">
                <input type="hidden" name="expected_accepted_cents" id="goodwill-expected-cents">

                <div id="goodwill-preview" class="rounded border border-gray-200 bg-gray-50 p-3 text-sm">
                    <p class="text-gray-500">Calculating…</p>
                </div>

                <div id="goodwill-fields" class="mt-4 hidden space-y-3">
                    <div>
                        <label for="goodwill-reason" class="block text-sm font-medium text-gray-700">Reason <span class="text-red-500">*</span></label>
                        <select id="goodwill-reason" name="reason_code" required
                            class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">Select a reason…</option>
                            @foreach(GoodwillReasonCode::options() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="goodwill-note-wrap" class="hidden">
                        <label for="goodwill-note" class="block text-sm font-medium text-gray-700">
                            Note <span class="text-red-500">*</span>
                        </label>
                        <textarea id="goodwill-note" name="reason_note" rows="2" maxlength="1000"
                            class="mt-1 w-full rounded border-gray-300 text-sm"
                            placeholder="Required when the reason is Other."></textarea>
                    </div>

                    <div>
                        <label for="goodwill-manager" class="block text-sm font-medium text-gray-700">
                            Authorising manager <span class="text-red-500">*</span>
                        </label>
                        <select id="goodwill-manager" name="approved_by" required
                            class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">Select a manager…</option>
                            @foreach(($goodwillManagers ?? collect()) as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->full_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Authority to receive a payment does not imply authority to reduce revenue.
                            The server verifies this independently.
                        </p>
                    </div>
                </div>

                <p id="goodwill-error" class="mt-3 hidden rounded bg-red-50 px-3 py-2 text-sm text-red-700"></p>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" id="goodwill-cancel"
                        class="rounded border px-3 py-1.5 text-sm">Cancel</button>
                    <button type="submit" id="goodwill-submit" disabled
                        class="rounded bg-amber-600 px-3 py-1.5 text-sm font-medium text-white disabled:opacity-50">
                        Confirm Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

@if($gwAdjustment)
    <div id="goodwill-reverse-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white shadow-xl">
            <div class="border-b px-5 py-3">
                <h3 class="text-base font-semibold text-gray-900">Reverse Goodwill Adjustment</h3>
            </div>
            <form id="goodwill-reverse-form" class="px-5 py-4">
                @csrf
                <p class="text-sm text-gray-600">
                    The original balance of
                    {{ \App\Helpers\CustomHelper::formatCurrency($gwAdjustment->original_grand_total) }}
                    will be restored. The customer's payments are not touched, and a superseding
                    receipt will be issued.
                </p>
                <label for="goodwill-reverse-reason" class="mt-3 block text-sm font-medium text-gray-700">
                    Reason <span class="text-red-500">*</span>
                </label>
                <textarea id="goodwill-reverse-reason" name="reversal_reason" rows="2" required minlength="3" maxlength="1000"
                    class="mt-1 w-full rounded border-gray-300 text-sm"></textarea>

                <p id="goodwill-reverse-error" class="mt-3 hidden rounded bg-red-50 px-3 py-2 text-sm text-red-700"></p>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" id="goodwill-reverse-cancel" class="rounded border px-3 py-1.5 text-sm">Cancel</button>
                    <button type="submit" class="rounded bg-red-600 px-3 py-1.5 text-sm font-medium text-white">Reverse</button>
                </div>
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const previewUrl = @json(route('admin.order-management.orders.goodwill.preview', $order->unique_id));
    const applyUrl   = @json(route('admin.order-management.orders.goodwill.apply', $order->unique_id));
    const reverseUrl = @json(route('admin.order-management.orders.goodwill.reverse', $order->unique_id));

    const money = (n) => '$' + Number(n).toFixed(2);

    const modal   = document.getElementById('goodwill-modal');
    const openBtn = document.getElementById('goodwill-open');

    if (openBtn && modal) {
        const form     = document.getElementById('goodwill-form');
        const preview  = document.getElementById('goodwill-preview');
        const fields   = document.getElementById('goodwill-fields');
        const submit   = document.getElementById('goodwill-submit');
        const errorBox = document.getElementById('goodwill-error');
        const reason   = document.getElementById('goodwill-reason');
        const noteWrap = document.getElementById('goodwill-note-wrap');
        const note     = document.getElementById('goodwill-note');

        const showError = (msg) => { errorBox.textContent = msg; errorBox.classList.remove('hidden'); };

        // Only "Other" forces a note, matching GoodwillReasonCode::requiresNote().
        reason.addEventListener('change', () => {
            const needsNote = reason.value === 'other';
            noteWrap.classList.toggle('hidden', !needsNote);
            note.required = needsNote;
        });

        openBtn.addEventListener('click', async () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            errorBox.classList.add('hidden');

            // One token per modal-open, reused across retries of this submit.
            document.getElementById('goodwill-idempotency').value =
                (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random());

            try {
                const res  = await fetch(previewUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (!data.success) {
                    preview.innerHTML = '<p class="text-red-700">' + data.message + '</p>';
                    return;
                }

                const p = data.preview;
                document.getElementById('goodwill-expected-cents').value = data.accepted_cents;

                // Every figure the operator approves, including the protected
                // components Goodwill must NOT reduce.
                let rows = ''
                    + row('Payment accepted', money(p.accepted))
                    + row('Original product cost', money(p.original_basis))
                    + row('Goodwill adjustment', '−' + money(p.goodwill), 'text-amber-700 font-semibold')
                    + row('Revised product cost', money(p.revised_basis))
                    + row('Sales tax', money(p.revised_tax));

                if (p.revised_special_tax > 0 || p.original_special_tax > 0) {
                    rows += row('Special tax', money(p.revised_special_tax));
                }
                if (p.protected_fees > 0) {
                    rows += row('Added fees (not reduced)', money(p.protected_fees));
                }
                if (p.discount > 0) {
                    rows += row('Discount', '−' + money(p.discount));
                }

                rows += row('Revised order total', money(p.revised_total), 'border-t pt-1 font-semibold');

                preview.innerHTML = '<dl class="space-y-1">' + rows + '</dl>';
                fields.classList.remove('hidden');
                submit.disabled = false;
            } catch (e) {
                preview.innerHTML = '<p class="text-red-700">Could not calculate the adjustment. Reload and try again.</p>';
            }
        });

        function row(label, value, cls) {
            return '<div class="flex justify-between ' + (cls || '') + '">'
                 + '<dt>' + label + '</dt><dd>' + value + '</dd></div>';
        }

        const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        document.getElementById('goodwill-cancel').addEventListener('click', close);

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            submit.disabled = true;
            errorBox.classList.add('hidden');

            try {
                const res = await fetch(applyUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: new FormData(form),
                });
                const data = await res.json();

                if (!data.success) {
                    // Typed refusal from the service, shown verbatim. Nothing
                    // was recorded — neither payment nor adjustment.
                    showError(data.message);
                    submit.disabled = false;
                    return;
                }

                // Reload into the final paid state; the adjusted receipt is
                // already the current one.
                window.location.reload();
            } catch (err) {
                showError('The adjustment could not be submitted. Nothing was changed. Please retry.');
                submit.disabled = false;
            }
        });
    }

    const revModal = document.getElementById('goodwill-reverse-modal');
    const revOpen  = document.getElementById('goodwill-reverse-open');

    if (revOpen && revModal) {
        const revForm  = document.getElementById('goodwill-reverse-form');
        const revError = document.getElementById('goodwill-reverse-error');

        revOpen.addEventListener('click', () => {
            revModal.classList.remove('hidden');
            revModal.classList.add('flex');
        });
        document.getElementById('goodwill-reverse-cancel').addEventListener('click', () => {
            revModal.classList.add('hidden');
            revModal.classList.remove('flex');
        });

        revForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            revError.classList.add('hidden');

            const res  = await fetch(reverseUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: new FormData(revForm),
            });
            const data = await res.json();

            if (!data.success) {
                revError.textContent = data.message;
                revError.classList.remove('hidden');
                return;
            }

            window.location.reload();
        });
    }
})();
</script>
@endpush
