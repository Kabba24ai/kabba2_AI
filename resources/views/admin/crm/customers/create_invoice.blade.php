@extends('admin.layouts.app')

@section('title', 'Orders')

@push('css')
@endpush

@section('content')

    @include('flash::message')


<div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <a href="https://admin.kabba.local/crm/customers" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path></svg>                
                <span class="text-sm font-medium">Back to Invoice</span>
            </a>
            <div class="hidden sm:block h-6 border-l border-gray-300"></div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Invoice</h1>
                <p class="text-sm text-gray-500">Invoice <span id="invoiceNumberDisplay">#INV-2025-920 </span> for Acme Corp</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">          
            <button type="cancel" name="action" value="close" class="inline-flex items-center px-6 py-2 rounded-md text-gray-700 bg-white text-sm font-medium shadow transition"> Cancel
            </button>

            <button type="submit" name="action" value="save_new" disabled="" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-sm font-medium shadow transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save w-4 h-4 mr-2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Create Invoice
            </button>
        </div>
    </div>
</div>

<div class="min-h-screen bg-gray-50 mt-6">
    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left side -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Invoice Details -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="flex items-center gap-2 text-lg font-semibold mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-5 h-5"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
                    Invoice Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number</label>
                        <input id="invoiceNumberInput" type="text" class=" w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="INV-2025-153">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Date</label>
                        <input class="w-full border rounded-md datepicker px-3 py-2 text-sm bg-white text-gray-700 border-gray-300" type="text" name="invoice_date" id="invoice_date" placeholder="MM-DD-YYYY" autocomplete="off">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <input class="w-full border rounded-md datepicker px-3 py-2 text-sm bg-white text-gray-700 border-gray-300" type="text" name="due_date" id="due_date" placeholder="MM-DD-YYYY" autocomplete="off">
                    </div>
                </div>
            </div>

            <!-- Add Items -->
            <div class="bg-white rounded-lg shadow p-5"> 
                <h3 class="text-lg font-semibold mb-4">Add Items to Invoice</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                    <a href="javascript:void(0)" id="openChargeModal" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-4 h-4"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>    
                        Charge
                    </a>
                    <button class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-4 h-4"><circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path></svg>
                        Discount
                    </button>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-4 h-4"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                        Refund
                    </button>
                    <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md text-sm font-medium flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart w-4 h-4"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path></svg>
                        From Order
                    </button>
                </div>
                <!-- EMPTY STATE (when no items) -->
                <div id="emptyState" class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package w-12 h-12 text-gray-400 mx-auto mb-4"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                    <p class="text-sm">No items added to this invoice yet.</p>
                    <p class="text-sm text-gray-400 mt-1">Use the buttons above to add charges, discounts, refunds, or items from existing orders.</p>
                </div>
                <!-- TABLE STATE (hidden by default until items exist) -->
                <div id="itemsTable" class="overflow-x-auto hidden rounded-lg shadow border border-gray-200">
                    <table class="min-w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Description</th>
                                <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700">Qty</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-700">Unit Price</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-700">Total</th>
                                <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceItems" class="divide-y divide-gray-200">
                            <!-- rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoice Notes -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-semibold mb-4">Invoice Notes</h3>
                <textarea rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter any additional notes for this invoice..."></textarea>
            </div>
        </div>

        <!-- Right side -->
        <div class="space-y-6">
            <!-- Bill To -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="flex items-center gap-2 text-lg font-semibold mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building w-5 h-5 mr-2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>
                Bill To
                </h3>
                <p class="font-semibold">Acme Corp</p>
                <p class="text-gray-600">John Doe</p>

                <div class="flex items-center text-sm text-gray-600 gap-2 mt-2">
                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                    john.doe@acmecorp.com
                </div>
                <div class="flex items-center text-sm text-gray-600 gap-2 mt-2">
                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                    (555) 012-3456
                </div>
                <div class="flex items-center text-sm text-gray-600 gap-2 mt-2">
                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                    123 Main St, New York, NY 10001
                </div>
                <div class="flex items-center mt-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check w-4 h-4 mr-2 text-green-600"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
                    <span class="text-sm font-medium text-green-600">Tax Exempt</span>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- New Charge Wrapper -->
<div id="chargeModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <div class="text-red-600 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-plus w-4 h-4">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">New Charge</h2>
                </div>
                <button id="closeChargeModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto">
                <form class="space-y-8" id="chargeForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input id="amount" type="text" placeholder="0.00" maxlength="8" class="pl-7 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm" >
                        </div>

                        <div id="clueBox" class="hidden mt-3 bg-blue-50 border border-blue-200 rounded-md p-4 text-sm space-y-1">
                            <p class="font-semibold text-gray-700">Calculation Preview</p>
                            <div class="flex justify-between">
                                <span class="text-blue-700">Amount:</span>
                                <span id="amountPreview" class="text-blue-900">$0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-700">Sales Tax:</span>
                                <span id="taxPreview" class="text-blue-900">$0.00</span>
                            </div>
                            <div class="flex justify-between font-semibold border-t border-blue-300 pt-1">
                                <span class="text-blue-700">Total Balance Change:</span>
                                <span class="text-blue-900" id="totalPreview">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <!-- Charge Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Charge Reason </label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" name="reason" id="reason" required="">
                            <option value="">Select charge reason</option>
                            <option value="New Rental">New Rental</option>
                            <option value="Rental Extension">Rental Extension</option>
                            <option value="Damages">Damages</option>
                            <option value="Fuel Charge">Fuel Charge</option>
                            <option value="Cleaning Charge">Cleaning Charge</option>
                            <option value="Missing Items">Missing Items</option>
                            <option value="Product Purchase">Product Purchase</option>
                        </select>
                    </div>

                    <!-- Person Responsible -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible </label>
                         <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="responsible_person" id="responsible_person" required="">
                            <option value="">Select person responsible</option>
                            <option value="1">Raj Chotaliya</option>
                            <option value="2">Jigar Khatri</option>
                            <option value="3">Nipa Soni</option>
                            <option value="4">Gary Jezorski</option>
                            <option value="5">Akshay Vyas</option>
                            <option value="9">Jigar khatri</option>
                        </select>
                    </div>

                     <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Reference (Optional)</label>
                        <div class="relative">
                              <input class="px-3 py-2 w-full border border-gray-300 rounded-md text-sm" type="text" placeholder="Enter reference number or ID..." >
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700" name="notes" id="notes" rows="3" placeholder="Describe the reason for this charge..."></textarea>
                    </div>

                    <div class="flex  gap-2 pb-4">
                        <button type="button" id="cancelChargeBtn" class="px-4 py-2 flex-1 text-sm rounded border border-gray-300 bg-white text-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="submitChargeBtn" class="relative flex-1 px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 flex items-center justify-center gap-2">
                            <span id="chargeBtnText">Add Charge</span>
                            <svg id="chargeBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    @endsection

@push('js')
<script>
  const invoiceInput = document.getElementById("invoiceNumberInput");
  const invoiceDisplay = document.getElementById("invoiceNumberDisplay");

  invoiceInput.addEventListener("input", function() {
    const value = this.value.trim();
    invoiceDisplay.textContent = value ? `#${value}` : "#INV-2025-920";
  });
</script>

<script>
    window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalWrapper = document.getElementById('chargeModalWrapper');
    const openBtn = document.getElementById('openChargeModal');
    const closeBtn = document.getElementById('closeChargeModalBtn');
    const cancelBtn = document.getElementById('cancelChargeBtn');
    const form = document.getElementById('chargeForm');

    const emptyState = document.getElementById("emptyState");
    const itemsTable = document.getElementById("itemsTable");
    const invoiceItems = document.getElementById("invoiceItems");

    const amountInput = document.getElementById("amount");
    const clueBox = document.getElementById("clueBox");
    const amountPreview = document.getElementById("amountPreview");
    const taxPreview = document.getElementById("taxPreview");
    const totalPreview = document.getElementById("totalPreview");

    // open/close modal
    openBtn.addEventListener('click', () => modalWrapper.style.display = 'flex');
    const closeModal = () => { modalWrapper.style.display = 'none'; form.reset(); clueBox.classList.add("hidden"); };
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // preview calculation
    amountInput.addEventListener("input", function () {
        this.value = this.value.replace(/[^0-9.]/g, "");
        const amount = parseFloat(this.value) || 0;
        const taxRate = 0.098;
        const tax = amount * taxRate;
        const total = amount + tax;
        amountPreview.textContent = `$${amount.toFixed(2)}`;
        taxPreview.textContent = `$${tax.toFixed(2)}`;
        totalPreview.textContent = `$${total.toFixed(2)}`;
        clueBox.classList.toggle("hidden", this.value.trim() === "");
    });

    // handle submit
    form.addEventListener("submit", e => {
        e.preventDefault();
        const desc = document.getElementById("reason").value || "New Charge";
        const notes = document.getElementById("notes").value.trim();
        const price = parseFloat(amountInput.value) || 0;
        const qty = 1;
        const taxRate = 0.098;
        const total = price + (price * taxRate);

        if (price <= 0) { alert("Enter valid amount"); return; }

        const row = document.createElement("tr");
        row.innerHTML = `
            <td class="px-4 py-3">
                <div class="font-medium text-gray-900">${desc}</div>
                ${notes ? `<div class="text-gray-500 text-sm">${notes}</div>` : ""}
            </td>
            <td class="px-4 py-3 text-center text-sm">${qty}</td>
            <td class="px-4 py-3 text-right text-sm">$${price.toFixed(2)}</td>
            <td class="px-4 py-3 text-right font-semibold text-sm">$${total.toFixed(2)}</td>
            <td class="px-4 py-3 text-center h-full items-center justify-center gap-3">
                <button type="button" class="text-blue-600 edit-btn mr-2"><x-heroicon-o-pencil class="w-4 h-4" /></button>
                <button type="button" class="text-red-600 delete-btn"><x-heroicon-o-trash class="w-4 h-4" /></button>
            </td>
        `;

        row.querySelector(".delete-btn").addEventListener("click", () => {
            row.remove();
            if (!invoiceItems.children.length) {
                itemsTable.classList.add("hidden");
                emptyState.classList.remove("hidden");
            }
        });

        invoiceItems.appendChild(row);
        emptyState.classList.add("hidden");
        itemsTable.classList.remove("hidden");

        closeModal();
    });
});
</script>
<!-- <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('chargeModalWrapper');
        const openBtn = document.getElementById('openChargeModal');
        const closeBtn = document.getElementById('closeChargeModalBtn');
        const cancelBtn = document.getElementById('cancelChargeBtn');

        openBtn.addEventListener('click', () => {
            modalWrapper.style.display = 'flex';
        });

        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

    });
</script> -->

<script>
  const amountInput = document.getElementById("amount");
  const clueBox = document.getElementById("clueBox");
  const amountPreview = document.getElementById("amountPreview");
  const taxPreview = document.getElementById("taxPreview");
  const totalPreview = document.getElementById("totalPreview");

  amountInput.addEventListener("input", function () {
    this.value = this.value.replace(/[^0-9.]/g, ""); // only digits & dot

    const amount = parseFloat(this.value) || 0;
    const taxRate = 0.098; // 9.8%
    const tax = amount * taxRate;
    const total = amount + tax;

    amountPreview.textContent = `$${amount.toFixed(2)}`;
    taxPreview.textContent = `$${tax.toFixed(2)}`;
    totalPreview.textContent = `$${total.toFixed(2)}`;

    // Show or hide the clue box
    if (this.value.trim() !== "") {
      clueBox.classList.remove("hidden");
    } else {
      clueBox.classList.add("hidden");
    }
  });
</script>

<script>
    function addItemToInvoice() {
        document.getElementById("emptyState").classList.add("hidden");
        document.getElementById("itemsTable").classList.remove("hidden");
}
</script>
@endpush