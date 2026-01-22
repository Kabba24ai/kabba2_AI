<!-- New Refund Wrapper -->
<div id="refundModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-purple-600 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-5 h-5 mr-2 text-blue-600">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Process Refund</h2>
                </div>
                <button id="closeRefundModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto">

                {{ html()->form()->id('refundForm')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ])->open() }}

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Refund Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 h-[35px] pl-3 flex items-center text-gray-500">$</span>
                        <!-- <input id="ramount" type="text" placeholder="0.00" maxlength="8" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"> -->
                        {!! html()->text('ramount')->attributes([
                        'placeholder' => '0',
                        'autocomplete' => 'off',
                        'data-digit-input' => 'true',
                        'data-parsley-maxlength' => 8,
                        'maxlength' => 8,
                        ])
                        ->class('pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm')
                        ->placeholder('0.00')->required() !!}
                    </div>
                </div>
                <!-- Refund Reason -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Refund Reason</label>

                    {!! html()->select('reason', [
                    '' => 'Select refund reason',
                  'Billing Overcharge' => 'Billing Overcharge',
                    'Customer Cancellation' => 'Customer Cancellation',
                    'Damage Waiver Protection' => 'Damage Waiver Protection',
                    'Damaged Item' => 'Damaged Item',
                    'Duplicate Charge' => 'Duplicate Charge',
                    'Other' => 'Other',
                    'Wrong Item Shipped' => 'Wrong Item Shipped',

                    ])
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                    ->required() !!}

                </div>

                <!-- Person Responsible -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible</label>

                    {!! html()
                    ->select('responsible_person',
                    $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                    old('responsible_person')
                    )
                    ->id('responsible_person')
                    ->class([
                    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700',
                    'border-red-500' => $errors->has('responsible_person'),
                    ])
                    ->required()
                    !!}

                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference (Optional)</label>
                    <div class="relative">
                        <input class="px-3 py-2 w-full border border-gray-300 rounded-md text-sm" name="reference" type="text" placeholder="Enter reference number or ID...">
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                    <textarea class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="notes" id="notes" rows="3" placeholder="Describe the reason for this refund..."></textarea>
                </div>

                <div class="flex  gap-2 pb-4">
                    <button type="button" id="cancelRefundBtn" class="px-4 py-2 flex-1 text-sm rounded border border-gray-300 bg-white text-gray-700">
                        Cancel
                    </button>
                    <button type="submit" id="submitRefundBtn" class="relative flex-1 px-4 py-2 text-sm rounded bg-blue-600 text-white flex items-center justify-center gap-2">
                        <span id="refundBtnText">Add Discount</span>
                        <svg id="refundBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>

                </div>
                {{ html()->form()->close() }}
                <!-- </form> -->
            </div>
        </div>
    </div>
</div>

@push('js')

<!-- refundModalWrapper -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const refundModal = document.getElementById("refundModalWrapper");
        const refundForm = refundModal.querySelector("form");
        const openBtn = document.getElementById("openRefundModal"); // button that triggers opening
        const closeBtn = document.getElementById("closeRefundModalBtn");
        const cancelBtn = document.getElementById("cancelRefundBtn");

        const itemsTableWrap = document.getElementById("itemsTable");
        const invoiceItemsTBody = document.getElementById("invoiceItems");
        const emptyState = document.getElementById("emptyState");

        const refundamountInput = refundModal.querySelector("#ramount");

        const TAX_RATE = window.SALES_TAX_RATE;

        // ---- Modal Open/Close ----
        if (openBtn) {
            openBtn.addEventListener("click", () => {
                refundModal.style.display = "flex";


                // Remove editing state so modal knows it's adding
                delete refundForm.dataset.editingId;

                // Reset modal title and button text
                document.querySelector("#refundModalWrapper h2").textContent = "Process Refund";
                document.getElementById("refundBtnText").textContent = "Add Refund";

                // Reset form fields
                refundForm.reset();

            });
        }
        const closeModal = () => {
            refundModal.style.display = 'none';
            refundForm.reset();

            // Explicitly reset your custom data property
            if (refundamountInput.digits) {
                refundamountInput.digits = "";
            }

            // Also reset the displayed value and preview
            refundamountInput.value = "";

            // Use a small delay to ensure the DOM has updated
            setTimeout(() => {
                $(refundForm).parsley().reset();
            }, 50);
            clueBox.classList.add("hidden");
        };


        closeBtn.addEventListener("click", closeModal);
        cancelBtn.addEventListener("click", closeModal);

        refundModal.addEventListener("click", (e) => {
            if (e.target === refundModal) closeModal();
        });


        // edit handal
        // Edit refund
        invoiceItemsTBody.addEventListener("click", (e) => {
            const btn = e.target.closest(".edit-btn");
            if (!btn) return;

            const row = btn.closest("tr");
            const itemId = row.dataset.id;

            // find object in array
            const item = invoice_data.find(p => p.id === itemId);
            if (!item) return;
            if (item.type !== 'refund') return;

            // store editing ID in the form itself
            refundForm.dataset.editingId = itemId;

            // Convert unit to number
            const unit = Number(item.unit) || 0;

            // Prefill input
            refundForm.elements["ramount"].value = unit.toFixed(2);
            refundForm.elements["ramount"].digits = String(Math.round(unit * 100));

            // Trigger input event if needed
            refundForm.elements["ramount"].dispatchEvent(new Event("input", {
                bubbles: true
            }));

            // Prefill other fields
            refundForm.elements["reason"].value = item.name;
            refundForm.elements["responsible_person"].value = item.responsible_id;
            refundForm.elements["reference"].value = item.reference;
            refundForm.elements["notes"].value = item.notes || "";

            // Reset modal title and button text
            document.querySelector("#refundModalWrapper h2").textContent = "Edit Refund";
            document.getElementById("refundBtnText").textContent = "Update Refund";

            // Show modal
            refundModal.style.display = "flex";
        });


        // handle submit
        refundForm.addEventListener("submit", e => {
            e.preventDefault();

            // Check if the form is valid using Parsley.js
            if ($(refundForm).parsley().isValid()) {
                const fd = new FormData(refundForm);

                const amountEl = refundModal.querySelector("#ramount");
                const reasonEl = refundModal.querySelector("#reason");
                const notesEl = refundModal.querySelector("#notes");

                const rawAmount = (amountEl.value || "").trim();
                const reason = (reasonEl.value || "").trim();
                const notes = (notesEl.value || "").trim();

                if (!rawAmount || !reason) {
                    notyf.error("Please enter a refund amount and select a reason.");
                    return;
                }

                const responsibleId = fd.get("responsible_person") || "-";
                const reference = fd.get("reference") || "-";

                const taxRate = window.SALES_TAX_RATE;
                const amount = parseFloat(rawAmount.replace(/[^0-9.]/g, "")) || 0;
                const qty = 1;
                const taxPrice = amount * taxRate;
                const total = amount + taxPrice;

                const editingId = refundForm.dataset.editingId;

                if (editingId) {
                    // ---- EDIT MODE ----
                    const item = invoice_data.find(p => p.id === editingId);
                    if (item) {
                        Object.assign(item, {
                            name: reason,
                            qty: qty,
                            unit: amount,
                            tax: taxPrice,
                            total: total,
                            responsible_id: responsibleId,
                            reference: reference,
                            notes: notes,
                        });
                    }

                    const row = invoiceItems.querySelector(`tr[data-id="${editingId}"]`);
                    if (row) {
                        row.innerHTML = `
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">${reason} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-600 text-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-3 h-3">
                                                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                                        <polyline points="16 7 22 7 22 13"></polyline>
                                                    </svg>        <span class="ml-1 capitalize text-xs">refund</span>
                                                                </span> </div>
                                    ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
                                </td>
                                <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${qty}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${amount.toFixed(2)}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${taxPrice.toFixed(2)}</td>
                                <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap">$${total.toFixed(2)}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-3">
                                    <button type="button" class="text-blue-600 edit-btn mr-2" title="Edit"><x-heroicon-o-pencil class="w-4 h-4" /></button>
                                    <button type="button" class="text-red-600 delete-btn" title="Delete"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                    </div>
                                </td>
                                `;
                    }

                    notyf.success("Refund updated");

                } else {
                    // ---- ADD MODE ----

                    // Generate unique id for this row
                    const uniqueId = generateUniqueId("refund");
                    //  Add to global array
                    addInvoiceProduct([{
                        id: uniqueId,
                        name: reason,
                        qty: qty,
                        unit: amount,
                        tax: taxPrice,
                        total: total,
                        responsible_id: responsibleId,
                        reference: reference,
                        notes: notes,
                    }], 'refund');

                    const tr = document.createElement("tr");
                    tr.dataset.type = "refund";
                    tr.dataset.id = uniqueId;
                    tr.innerHTML = `
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="font-medium text-gray-900">${reason} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-600 text-white">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-3 h-3">
                                                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                                                    <polyline points="16 7 22 7 22 13"></polyline>
                                                                </svg>        <span class="ml-1 capitalize text-xs">refund</span>
                                                                            </span> </div>
                                                ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${qty}</td>
                                            <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${amount.toFixed(2)}</td>
                                            <td class="px-4 py-3 text-right text-sm whitespace-nowrap">$${taxPrice.toFixed(2)}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap">$${total.toFixed(2)}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="flex items-center justify-center gap-3">
                                                <button type="button" class="text-blue-600 edit-btn mr-2" title="Edit"><x-heroicon-o-pencil class="w-4 h-4" /></button>
                                                <button type="button" class="text-red-600 delete-btn" title="Delete"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                                </div>
                                            </td>
                                            `;

                    invoiceItemsTBody.appendChild(tr);

                    if (emptyState) emptyState.classList.add("hidden");
                    itemsTableWrap.classList.remove("hidden");

                    refundForm.reset();
                    notyf.success("Refund added");
                }

                closeModal();

                window.updateInvoiceSummary();

            } else {
                console.log("Form is not valid.");
            }

        });

    });
</script>
<!-- refundModalWrapper -->

@endpush