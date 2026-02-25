<div id="discountModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-purple-600 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-5 h-5">
                            <circle cx="12" cy="8" r="6"></circle>
                            <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">Apply Discount</h2>
                </div>
                <button id="closeDiscountModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto">
                <!-- <form class="space-y-8"> -->

                {{ html()->form()->id('discountForm')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-8',
                ])->open() }}


                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Discount Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 h-[35px] pl-3 flex items-center text-gray-500">$</span>

                        <!-- <input id="damount" type="text" placeholder="0.00" maxlength="8" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm"> -->

                        {!! html()->text('damount')->attributes([
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
                <!-- Discount Reason -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Discount Reason </label>

                    {!! html()->select('reason', [
                    '' => 'Select discount reason',
                    'Damage Waiver Protection' => 'Damage Waiver Protection',
                    'Misc. Management Discount' => 'Misc. Management Discount',
                    'Other' => 'Other',
                    'Repeat Customer Discount' => 'Repeat Customer Discount',
                    'Volume Discount' => 'Volume Discount',

                    ])
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                    ->required() !!}

                </div>

                <!-- Person Responsible -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible </label>

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

                    <!-- <textarea class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="notes" id="notes" rows="3" placeholder="Describe the reason for this discount..."></textarea> -->

                    {!! html()->textarea('notes', old('notes'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                    ->rows(3)
                    ->placeholder('Describe the reason for this discount...') !!}

                </div>

                <div class="flex  gap-2 pb-4">
                    <button type="button" id="cancelDiscountBtn" class="px-4 py-2 flex-1 text-sm rounded border border-gray-300 bg-white text-gray-700">
                        Cancel
                    </button>
                    <button type="submit" id="submitDiscountBtn" class="relative flex-1 px-4 py-2 text-sm rounded bg-blue-600 text-white flex items-center justify-center gap-2">
                        <span id="discountBtnText">Add Discount</span>
                        <svg id="discountBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
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

<!-- discount  -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const discountModal = document.getElementById("discountModalWrapper");
        const discountForm = discountModal.querySelector("form");
        const itemsTableWrap = document.getElementById("itemsTable");
        const invoiceItemsTBody = document.getElementById("invoiceItems");
        const emptyState = document.getElementById("emptyState"); // if present

        const discountamountInput = discountModal.querySelector("#damount");

        const openBtn = document.getElementById("openDiscountModal");
        const closeBtn = document.getElementById("closeDiscountModalBtn");
        const cancelBtn = document.getElementById("cancelDiscountBtn");

        // ---- Modal Open/Close ----
        openBtn.addEventListener("click", () => {
            discountModal.style.display = "flex";

            // Remove editing state so modal knows it's adding
            delete discountForm.dataset.editingId;

            // Reset modal title and button text
            document.querySelector("#discountModalWrapper h2").textContent = "Apply Discount";
            document.getElementById("discountBtnText").textContent = "Add Discount";

            // Reset form fields
            discountForm.reset();

        });

        const closeModal = () => {
            discountModal.style.display = 'none';
            discountForm.reset();

            // Explicitly reset your custom data property
            if (discountamountInput.digits) {
                discountamountInput.digits = "";
            }

            // Also reset the displayed value and preview
            discountamountInput.value = "";

            // Use a small delay to ensure the DOM has updated
            setTimeout(() => {
                $(discountForm).parsley().reset();
            }, 50);
            clueBox.classList.add("hidden");
        };

        closeBtn.addEventListener("click", closeModal);
        cancelBtn.addEventListener("click", closeModal);

        // Close modal if clicking outside
        discountModal.addEventListener("click", (e) => {
            if (e.target === discountModal) closeModal();
        });



        // edit handal
        // Edit discount
        invoiceItemsTBody.addEventListener("click", (e) => {
            const btn = e.target.closest(".edit-btn");
            if (!btn) return;

            const row = btn.closest("tr");
            const itemId = row.dataset.id;

            // find object in array
            const item = invoice_data.find(p => p.id === itemId);
            if (!item) return;
            if (item.type !== 'discount') return;

            // store editing ID in the form itself
            discountForm.dataset.editingId = itemId;

            // Convert unit to number
            const unit = Number(item.unit) || 0;

            // Prefill input
            discountForm.elements["damount"].value = unit.toFixed(2);
            discountForm.elements["damount"].digits = String(Math.round(unit * 100));

            // Trigger input event if needed
            discountForm.elements["damount"].dispatchEvent(new Event("input", {
                bubbles: true
            }));

            // Prefill other fields
            discountForm.elements["reason"].value = item.name;
            discountForm.elements["responsible_person"].value = item.responsible_id;
            discountForm.elements["reference"].value = item.reference;
            discountForm.elements["notes"].value = item.notes || "";

            // Update modal title and button text
            document.querySelector("#discountModalWrapper h2").textContent = "Edit Discount";
            document.getElementById("discountBtnText").textContent = "Update Discount";

            // Show modal
            discountModal.style.display = "flex";
        });


        // handle submit
        discountForm.addEventListener("submit", e => {
            e.preventDefault();

            // Check if the form is valid using Parsley.js
            if ($(discountForm).parsley().isValid()) {
                const fd = new FormData(discountForm);

                const amountEl = discountModal.querySelector("#damount");
                const reasonEl = discountModal.querySelector("#reason");
                const notesEl = discountModal.querySelector("#notes");

                const rawAmount = (amountEl.value || "").trim();
                const reason = (reasonEl.value || "").trim();
                const notes = (notesEl.value || "").trim();

                if (!rawAmount || !reason) {
                    notyf.error("Please enter a discount amount and select a reason.");
                    return;
                }

                const responsibleId = fd.get("responsible_person") || "-";
                const reference = fd.get("reference") || "-";


                const amount = parseFloat(rawAmount.replace(/[^0-9.]/g, "")) || 0;
                const qty = 1;
                const total = amount; // adjust if needed

                const editingId = discountForm.dataset.editingId;

                if (editingId) {
                    // ---- EDIT MODE ----
                    const item = invoice_data.find(p => p.id === editingId);
                    if (item) {
                        Object.assign(item, {
                            name: reason,
                            qty: qty,
                            unit: rawAmount,
                            tax: 0,
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
                                            <div class="font-medium text-gray-900">${reason} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-600 text-white">
                                                                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-3 h-3">
                                                                                        <circle cx="12" cy="8" r="6"></circle>
                                                                                        <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                                                                                        </svg>
                                                                                                                                <span class="ml-1 capitalize text-xs">Discount</span>
                                                                        </span></div>
                                            ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${qty}</td>
                                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap text-green-600">- $${amount.toFixed(2)}</td>
                                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap text-green-600">$0.00</td>
                                        <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap text-green-600">- $${total.toFixed(2)}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-3">
                                            <button type="button" class="text-blue-600 edit-btn mr-2" title="Edit"><x-heroicon-o-pencil class="w-4 h-4" /></button>
                                            <button type="button" class="text-red-600 delete-btn" title="Delete"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                            </div>
                                        </td>
                                    `;
                    }

                    notyf.success(" Discount updated ");

                } else {
                    // ---- ADD MODE ----

                    // Generate unique id for this row
                    const uniqueId = generateUniqueId("discount");

                    //  Add to global array
                    addInvoiceProduct([{
                        id: uniqueId,
                        name: reason,
                        qty: qty,
                        unit: rawAmount,
                        tax: 0,
                        total: total,
                        responsible_id: responsibleId,
                        reference: reference,
                        notes: notes,
                    }], 'discount');

                    const tr = document.createElement("tr");

                    tr.dataset.type = "discount";
                    tr.dataset.id = uniqueId;
                    tr.innerHTML = `
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">${reason} <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-600 text-white">
                                                                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-3 h-3">
                                                                                        <circle cx="12" cy="8" r="6"></circle>
                                                                                        <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                                                                                        </svg>
                                                                                                                                <span class="ml-1 capitalize text-xs">Discount</span>
                                                                        </span></div>
                                            ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm whitespace-nowrap">${qty}</td>
                                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap text-green-600">- $${amount.toFixed(2)}</td>
                                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap text-green-600">$0.00</td>
                                        <td class="px-4 py-3 text-right font-semibold text-sm whitespace-nowrap text-green-600">- $${total.toFixed(2)}</td>
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

                    discountForm.reset();

                    notyf.success("Charge added");
                }

                closeModal();

                window.updateInvoiceSummary();

            } else {
                console.log("Form is not valid.");
            }


        });

    });
</script>
<!-- discount  -->
@endpush