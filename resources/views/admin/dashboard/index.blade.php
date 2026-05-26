@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div id="dashboard">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Dashboard Overview</h1>
            <p class="text-sm text-gray-600 mt-1">Rental & Sales Management System</p>
        </div>

        <!-- Section 1 -->
        <div class="mb-6">
            <livewire:dashboard.schedule-section />
        </div>

        <!-- Section 2: Customer Alerts -->
        <div class="mb-6">
            <livewire:dashboard.alerts-section />
              @include('admin.dashboard.partials._alerts_section')
        </div>

        <!-- Section 3: Sales Trend Analysis -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-300">
             @include('admin.dashboard.partials._sales_trend_analysis')
        </div>

        <!-- Section 4: Maintenance & Damaged Items Tracking -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-6">
            @include('admin.dashboard.partials._maintenance_and_damaged')
        </div>
    <livewire:dashboard.maintenance-charts />
    </div>

@endsection
@push('js')

<script>
    window.APP_CURRENCY = "{{ config('app.currency.code') }}";
</script>
<script>
function goToEquipment(type) {
    const url = `{{ route('admin.checklist-management.equipment-management.index') }}?type=${type}`;
    // window.open(url);
        window.location.href = url;
}
</script>


<script>
const salesDataFromServer = @json($salesData);
   //  REAL DATA from backend
    window.damageAlerts = @json($damagedOrderAlerts);

    window.fuelAlerts = @json($fuelChargeAlerts);


    window.AUTH_USER_ID = {{ auth()->id() }};

    window.chartData = @json($chartData);

/**
 * Vanilla JavaScript Dashboard Controller
 * Replaces Alpine.js completely.
 */
    class Dashboard {
        constructor(root) {

            // store reference to the root element
            this.root = root;

            // state
            this.salesPeriod = "rolling30";
            this.fuelAlerts = [...this.defaultFuelAlerts()];
            this.damageAlerts = [...this.defaultDamageAlerts()];
            this.showAmountModal = false;
            this.showNotesModal = false;
            this.showUpdateDropdown = null;
            this.editingAlert = null;
            this.tempAmount = "";
            this.tempNotes = "";

            // charts
            this.salesChart = null;
            this.maintenanceChart = null;
            this.damagedChart = null;



            this.activeOrderUniqueId = null;


            // initialize
            this.init();
        }

        /** ------------------------
         *      DEFAULT DATA
         --------------------------*/
        defaultFuelAlerts() {
        
            // Prefer backend data
            if (Array.isArray(window.fuelAlerts) && window.fuelAlerts.length) {
                return window.fuelAlerts;
            }

            // Optional fallback (dev / empty state)
            return [];
        }

        updateFuelAlertHeader() {
            const count = this.fuelAlerts.length;

            document.getElementById("fuel-alert-count").textContent = count;

            document.getElementById("fuel-alert-s").textContent = count === 1 ? "" : "s";
        }

        updateFuelAlertBadge() {
                const count = this.fuelAlerts.length;

                const badge = document.getElementById("fuel-alert-badge");

                // Update the text
                badge.textContent = count > 0 ? `${count} Active` : "All Clear";

                // Update colors
                if (count > 0) {
                    badge.classList.remove("bg-green-100", "text-green-800");
                    badge.classList.add("bg-red-100", "text-red-800");
                } else {
                    badge.classList.remove("bg-red-100", "text-red-800");
                    badge.classList.add("bg-green-100", "text-green-800");
                }
            }

        renderFuelAlerts() {
            const wrapper = document.getElementById("fuel-alert-list-wrapper");
            const template = document.getElementById("fuel-alert-item-template").content;

            wrapper.innerHTML = ""; // clear previous items

            const list = this.fuelAlerts.slice(0, 10);

            if (list.length === 0) {
                wrapper.innerHTML = `

            <div class="text-center py-8 text-gray-500">
                <div class="text-gray-400 mb-2">
                    <svg fill="currentColor" class="w-10 h-10 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                        <path d="M320 576C178.6 576 64 461.4 64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576zM438 209.7C427.3 201.9 412.3 204.3 404.5 215L285.1 379.2L233 327.1C223.6 317.7 208.4 317.7 199.1 327.1C189.8 336.5 189.7 351.7 199.1 361L271.1 433C276.1 438 282.9 440.5 289.9 440C296.9 439.5 303.3 435.9 307.4 430.2L443.3 243.2C451.1 232.5 448.7 217.5 438 209.7z"/>
                    </svg>
                </div>
                <p class="text-gray-500">No pending alerts</p>
            </div>

                `;
                return;
            }

            list.forEach(alert => {
                const item = document.importNode(template, true);

                item.querySelector("[data-customer]").textContent = alert.customerName;
                item.querySelector("[data-order-id]").textContent = alert.order_number;

                // Order click
                // item.querySelector("[data-order-btn]").onclick = () => this.handleOrderClick(alert.orderId);

                item.querySelector("[data-order-btn]").onclick = () => {
                    if (alert.orderLink) {
                        // window.location.href = alert.orderLink;
                        // window.open(alert.orderLink, "_blank");

                                window.location.href = alert.orderLink;


                    }
                };

                // Edit amount
                item.querySelector("[data-edit-amount]").onclick = () => {
                    this.editingAlert = alert;
                    this.tempAmount = alert.amountOwed === "Pending" ? "" : alert.amountOwed.replace("$", "");
                    // this.showAmountModal = true;
                    this.openAmountModal(alert);

                };

                // Status dropdown toggle
                const dropdown = item.querySelector("[data-status-dropdown]");
                item.querySelector("[data-status-btn]").onclick = () => {
                    dropdown.classList.toggle("hidden");
                };

                // Status actions
                item.querySelector("[data-paid]").onclick = () => this.handleStatusUpdate("fuel", alert.id, "paid");

                item.querySelector("[data-uncollectible]").onclick = () => this.handleStatusUpdate("fuel", alert.id, "uncollectible");

                // Edit notes
                // item.querySelector("[data-edit-notes]").onclick = () => {
                //     this.editingAlert = alert;
                //     // this.tempNotes = alert.notes || "";

                //     this.tempNotes = alert.notes || "";
                    
                //     this.activeOrderUniqueId = alert.orderId;

                //     this.openNotesModal(alert);

                // };


                item.querySelector("[data-edit-notes]").onclick = () => {
                    this.editingAlert = alert;
                    this.tempNotes = "";
                    // this.showNotesModal = true;
                    this.activeOrderUniqueId = alert.orderId;

                    this.openNotesModal(alert);

                };

                // Amount display
                const amountEl = item.querySelector("[data-amount]");
                amountEl.textContent = alert.amountOwed;
                amountEl.className =
                    alert.amountOwed === "Pending"
                        ? "text-sm font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded-full"
                        : "text-sm font-semibold text-green-700";

                           // Notes (multiple notes, same design, clean)
                if (Array.isArray(alert.notes) && alert.notes.length > 0) {
                    const container = item.querySelector("[data-notes-container]");

                    container.classList.remove("hidden");
                    container.innerHTML = ""; // clear old notes

                    alert.notes.forEach(note => {
                        const noteEl = document.createElement("div");
                        noteEl.className = "p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800";
                        noteEl.innerHTML = `
                            <strong>Notes:</strong> ${note.note}
                        `;

                        container.appendChild(noteEl);
                    });
                }

                // if (alert.amountOwed === "Pending") {
                //     amountEl.className = "text-sm font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded-full";
                // } else {
                //     amountEl.className = "text-sm font-semibold text-green-700";
                // }

                // // Notes
                // if (alert.notes) {
                //     item.querySelector("[data-notes-wrapper]").classList.remove("hidden");
                //     item.querySelector("[data-notes]").textContent = alert.notes;
                // }

                wrapper.appendChild(item);
            });
        }

         openAmountModal(alert) {
            console.log(alert);
            
            this.editingAlert = alert;

            const input   = document.getElementById("amount-input");
            const baseEl  = document.getElementById("base-damage-amount");
            const currEl  = document.getElementById("current-damage-amount");
            const prevEl  = document.getElementById("preview-damage-amount");
                const titleEl = document.getElementById("amount-modal-title");


                
    const isFuel = alert.type === "fuel";

    

       let baseAmount, currentAmount;

if (isFuel) {
    baseAmount = Number(alert.order_product.base_fuel_charge ?? 0);
    currentAmount = Number(alert.order_product.current_fuel_charge ?? baseAmount);
} else {
    baseAmount = Number(alert.order_product.base_damage_charge ?? 0);
    currentAmount = Number(alert.order_product.current_damage_charge ?? baseAmount);
}


            // Fill static values
            baseEl.textContent = `$${baseAmount.toFixed(2)}`;
            currEl.textContent = `$${currentAmount.toFixed(2)}`;
            prevEl.textContent = `$${currentAmount.toFixed(2)}`;

            // Adjustment input must ALWAYS start empty
            input.value = "";
            input.digits = "";

            // Live preview
            input.oninput = () => {
                const change = parseFloat(input.value || 0);
                const next   = Math.max(0, currentAmount + change);
                prevEl.textContent = `$${next.toFixed(2)}`;
            };

            // Title is always adjustment-based
            // document.getElementById("amount-modal-title").textContent =
            //     "Adjust Damage Charge";

            titleEl.textContent = isFuel
        ? "Adjust Fuel Charge"
        : "Adjust Damage Charge";

            document.getElementById("amount-modal").classList.remove("hidden");
        }

        closeAmountModal() {
            document.getElementById("amount-modal").classList.add("hidden");
        }

        // DamageAlerts

         defaultDamageAlerts() {
            // Prefer backend data
            if (Array.isArray(window.damageAlerts) && window.damageAlerts.length) {
                return window.damageAlerts;
            }

            // Optional fallback (dev / empty state)
            return [];
        }


        renderDamageAlerts() {
            const wrapper = document.getElementById("damage-alert-list-wrapper");
            const template = document.getElementById("damage-alert-item-template").content;

            wrapper.innerHTML = ""; // Clear previous

            const list = this.damageAlerts.slice(0, 10);

            if (list.length === 0) {
                wrapper.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <div class="text-gray-400 mb-2">
                            <svg fill="currentColor" class="w-10 h-10 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                <path d="M320 576C178.6 576 64 461.4 64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576zM438 209.7C427.3 201.9 412.3 204.3 404.5 215L285.1 379.2L233 327.1C223.6 317.7 208.4 317.7 199.1 327.1C189.8 336.5 189.7 351.7 199.1 361L271.1 433C276.1 438 282.9 440.5 289.9 440C296.9 439.5 303.3 435.9 307.4 430.2L443.3 243.2C451.1 232.5 448.7 217.5 438 209.7z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500">No pending alerts</p>
                    </div>
                `;
                return;
            }

            list.forEach(alert => {
                const item = document.importNode(template, true);

                // Fill text
                item.querySelector("[data-customer]").textContent = alert.customerName;
                item.querySelector("[data-order-id]").textContent = alert.order_number;

                // Buttons
                item.querySelector("[data-order-btn]").onclick = () => {
                    if (alert.orderLink) {
                        // window.location.href = alert.orderLink;

                        // window.open(alert.orderLink, "_blank");

                        window.location.href = alert.orderLink;

                    }
                };


                item.querySelector("[data-edit-amount]").onclick = () => {
                    this.editingAlert = alert;
                    this.tempAmount = alert.amountOwed === "Pending" ? "" : alert.amountOwed.replace("$", "");
                    // this.showAmountModal = true;
                        this.openAmountModal(alert);

                };


                const viewBtn = item.querySelector('[data-view-charges]');
                    if (viewBtn) {
                    viewBtn.addEventListener('click', () => {
                        // pass the alert object
                        this.openChargesModal(alert);
                    });
                    }



                const dropdown = item.querySelector("[data-status-dropdown]");
                item.querySelector("[data-status-btn]").onclick = () => dropdown.classList.toggle("hidden");

                item.querySelector("[data-paid]").onclick = () => this.handleStatusUpdate("damage", alert.id, "paid");
                item.querySelector("[data-uncollectible]").onclick = () => this.handleStatusUpdate("damage", alert.id, "uncollectible");

                item.querySelector("[data-edit-notes]").onclick = () => {
                    this.editingAlert = alert;
                    this.tempNotes = "";
                    // this.showNotesModal = true;
                    this.activeOrderUniqueId = alert.orderId;

                    this.openNotesModal(alert);

                };

                // Amount color
                const amountEl = item.querySelector("[data-amount]");
                amountEl.textContent = alert.amountOwed;
                amountEl.className =
                    alert.amountOwed === "Pending"
                        ? "text-sm font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded-full"
                        : "text-sm font-semibold text-green-700";


              
                // Notes (multiple notes, same design, clean)
                if (Array.isArray(alert.notes) && alert.notes.length > 0) {
                    const container = item.querySelector("[data-notes-container]");

                    container.classList.remove("hidden");
                    container.innerHTML = ""; // clear old notes

                    alert.notes.forEach(note => {
                        const noteEl = document.createElement("div");
                        noteEl.className = "p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800";
                        noteEl.innerHTML = `
                            <strong>Notes:</strong> ${note.note}
                        `;

                        container.appendChild(noteEl);
                    });
                }




                wrapper.appendChild(item);
            });
        }

        updateDamageAlertHeader() {
            const count = this.damageAlerts.length;

            document.getElementById("damage-alert-count").textContent = count;
            document.getElementById("damage-alert-s").textContent = count === 1 ? "" : "s";
        }


        updateDamageAlertBadge() {
            const count = this.damageAlerts.length;
            const badge = document.getElementById("damage-alert-badge");

            badge.textContent = count > 0 ? `${count} Active` : "All Clear";

            if (count > 0) {
                badge.classList.remove("bg-green-100", "text-green-800");
                badge.classList.add("bg-red-100", "text-red-800");
            } else {
                badge.classList.remove("bg-red-100", "text-red-800");
                badge.classList.add("bg-green-100", "text-green-800");
            }
        }

        openNotesModal(alert) {
            // this.editingAlert = alert;
            this.tempNotes =  "";

            document.getElementById("notes-input").value = this.tempNotes;
            document.getElementById("notes-modal-title").textContent =
                alert.notes ? "Add Notes" : "Add Notes";

            document.getElementById("notes-modal").classList.remove("hidden");
        }

        closeNotesModal() {
            document.getElementById("notes-modal").classList.add("hidden");
        }


    /** ------------------------
     *      COMPUTED METRICS
     --------------------------*/
    get currentMetrics() {
        const data = salesDataFromServer[this.salesPeriod];
        const totalSales = data.totalSales || 0;
        const previousTotalSales = data.previousTotalSales || 0;
        const growthRate =
            previousTotalSales > 0
                ? ((totalSales - previousTotalSales) / previousTotalSales) * 100
                : 0;

        const dailyAverage =
            this.salesPeriod === "rolling30"
                ? totalSales / 30
                : totalSales / data.current.length;

        return { totalSales, previousTotalSales, growthRate, dailyAverage };
    }

    updatePeriodButtons() {
        const periods = ["rolling30", "currentMonth", "lastMonth"];

        periods.forEach(period => {
            const btn = document.getElementById(`btn-${period}`);
            btn.classList.remove("bg-blue-600", "text-white", "bg-gray-100", "text-gray-700");

            if (this.salesPeriod === period) {
                btn.classList.add("bg-blue-600", "text-white");
            } else {
                btn.classList.add("bg-gray-100", "text-gray-700");
            }
        });
    }


    /** ------------------------
     *          INIT
     --------------------------*/
    init() {
        this.initSalesChart();
        this.initMaintenanceChart();
        this.initDamagedChart();

         // Update all UI pieces
        this.updateFuelAlertHeader();
        this.updateFuelAlertBadge();
        this.renderFuelAlerts();

        this.updateDamageAlertHeader();
        this.updateDamageAlertBadge();
        this.renderDamageAlerts();

        this.updateSalesMetrics();
        this.updatePeriodButtons();
    }


    changeSalesPeriod(period) {
        this.salesPeriod = period;
        this.updatePeriodButtons();
        this.updateSalesMetrics();
        this.updateSalesChart();
    }

    formatCurrency(value) {
        if (value === null || value === undefined) return `${window.APP_CURRENCY}0.00`;

        return (
            window.APP_CURRENCY +
            Number(value).toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        );
    }


    /** ------------------------
     *         ALERTS
     --------------------------*/
    handleStatusUpdate(type, id, status) {

        
      // UNCOLLECTIBLE → confirm first
if (status === "uncollectible") {

    const label = type === "fuel" ? "Fuel" : "Damage";

    //  get correct alert object
    const alertItem =
        type === "fuel"
            ? this.fuelAlerts.find(a => a.id === id)
            : this.damageAlerts.find(a => a.id === id);

    if (!alertItem || !alertItem.order_product?.id) {
        notyf.error("Unable to process this payment.");
        return;
    }

    window.showConfirm(
        `Are you sure you want to mark this ${label} payment as uncollectible?`,
        'Mark Payment Uncollectible'
    ).then((result) => {
        if (result.isConfirmed) {

            //  send real order_product.id
            this.markPaymentUncollectible(
                type,
                alertItem.order_product.id, // correct ID
                id 
            );
        }
    });

    return;
}



        if (type === "fuel") {
            // this.fuelAlerts = this.fuelAlerts.filter((a) => a.id !== id);

        //        this.updateFuelAlertHeader();
        // this.updateFuelAlertBadge();
        // this.renderFuelAlerts();

         this.selectedfuelAlert = this.fuelAlerts.find(a => a.id === id);  
              // Open payment modal
                this.openPaymentModal(this.selectedfuelAlert);

        } else {
          
              this.selectedDamageAlert = this.damageAlerts.find(a => a.id === id);  
              // Open payment modal
                this.openPaymentModal(this.selectedDamageAlert);

        }
    }

    
 openChargesModal(alert) {
    const modal = document.getElementById('ChargesModal');
    const loading = document.getElementById('chargesModalLoading');
    const empty = document.getElementById('chargesModalEmpty');
    const header = document.getElementById('chargesModalHeader');
    const tbody = document.getElementById('chargesTableBody');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // reset UI
    tbody.innerHTML = '';
    empty.classList.add('hidden');
    loading.classList.remove('hidden');

    header.textContent = `Order #${alert.orderId || '-'} • ${alert.customer?.name || ''}`;

    const orderProductId = alert?.order_product?.id;
    if (!orderProductId) {
      loading.classList.add('hidden');
      notyf.error('Order product id not found');
      return;
    }

    this.fetchCharges(orderProductId)
      .then(data => {
        loading.classList.add('hidden');

        const rows = data?.checklist?.rows || [];

       const hasChecklist = rows.length > 0;
        const hasDamage = data?.damage?.final > 0;
        const hasHours = (data?.hour_tracking?.rows || []).length > 0;

        if (!hasChecklist && !hasDamage && !hasHours) {
        empty.classList.remove('hidden');
        return;
        }


        rows.forEach(row => {
          tbody.insertAdjacentHTML('beforeend', `
            <tr class="border-b">
              <td class="py-2 px-2">${this.escapeHtml(row.item)}</td>

              <td class="py-2 px-2">
                <div class="max-w-[200px] whitespace-normal break-words">
                  ${this.escapeHtml(row.delivered ?? 'Admin Override')}
                </div>
              </td>

              <td class="py-2 px-2">
                <div class="max-w-[200px] whitespace-normal break-words">
                  ${this.escapeHtml(row.returned ?? '-')}
                </div>
              </td>

              <td class="py-2 px-2 text-right text-red-600">
                ${this.formatCurrency(row.amount)}
              </td>
            </tr>
          `);
        });


        // DAMAGE SUMMARY ROW (same as Blade)
    if (data?.damage?.final > 0) {
    tbody.insertAdjacentHTML('beforeend', `
        <tr class="border-b bg-gray-50 font-medium">
        <td class="py-2 px-2">Damage Amount Initialized</td>

        <td class="py-2 px-2">
            Base (${this.formatCurrency(data.damage.base)})
        </td>

        <td class="py-2 px-2 ${
            data.damage.adjustment < 0 ? 'text-red-600' : 'text-green-600'
        }">
            Adjust (
            ${data.damage.adjustment >= 0 ? '+' : '-'}
            ${this.formatCurrency(Math.abs(data.damage.adjustment))}
            )
        </td>

        <td class="py-2 px-2 text-right text-gray-900">
            ${this.formatCurrency(data.damage.final)}
        </td>
        </tr>
    `);
    }

    // GRAND TOTAL ROW (same as Blade)
    tbody.insertAdjacentHTML('beforeend', `
    <tr class="font-bold border-t bg-gray-100">
        <td colspan="3" class="py-3 px-2 text-right text-gray-800">
        Grand Total:
        </td>
        <td class="py-3 px-2 text-right text-gray-900">
        ${this.formatCurrency(data.grand_total)}
        </td>
    </tr>
    `);



      })
      .catch(() => {
        loading.classList.add('hidden');
        notyf.error('Failed to load charges');
      });
  }


  fetchCharges(orderProductId) {
    const url =
      "{{ route('admin.dashboard.extra-charges.show', ':id') }}"
        .replace(':id', orderProductId);

    return fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document
          .querySelector('meta[name="csrf-token"]')
          .getAttribute('content'),
      },
    }).then(res => res.json());
  }


  closeChargesModal() {
    const modal = document.getElementById('ChargesModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }


  escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

   markPaymentUncollectible(type, orderProductId, alertId) {

    const url =
        "{{ route('admin.dashboard.extra-charges.uncollectible', ':id') }}"
            .replace(':id', orderProductId);

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content')
        },
        body: JSON.stringify({
            type: type // fuel | damage
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            notyf.success(data.message || 'Marked as uncollectible');

            // Remove alert from UI
            if (type === "fuel") {
                this.fuelAlerts = this.fuelAlerts.filter(a => a.id !== alertId);
                this.renderFuelAlerts();
                this.updateFuelAlertBadge();
            } else {
                this.damageAlerts = this.damageAlerts.filter(a => a.id !== alertId);
                this.renderDamageAlerts();
                this.updateDamageAlertBadge();
            }
        } else {
            notyf.error(data.message || 'Something went wrong');
        }
    })
    .catch(() => {
        notyf.error('Failed to mark payment as uncollectible');
    });
}





    openPaymentModal(alert) {

        window.resetCreditCardState();

        const modal = document.getElementById('PaymentModal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';

        // Set customer ID
        document.getElementById('customer_id').value = alert.customer.id;
        document.getElementById('order_id').value = alert.orderId;
        document.getElementById('order_product_id').value = alert.order_product.unique_id;
        document.getElementById('type').value = alert.type;

        // console.log(alert.customer.id);
        // Prefill amount
        const amountInput = document.querySelector('#recordpayment input[name="amount"]');

        if (amountInput) {
            const raw = alert.amountOwed.replace("$", "");

            if (raw && raw !== 'Pending') {
                const cleaned = raw.replace(/[^0-9.]/g, '');
                const num = Number(cleaned);

                if (Number.isFinite(num) && num > 0) {
                    amountInput.digits = Math.round(num * 100).toString();
                    amountInput.value = num.toFixed(2);
                } else {
                    amountInput.digits = "";
                    amountInput.value = "";
                }
            } else {
                amountInput.digits = "";
                amountInput.value = "";
            }
        }


        // Populate cards
        const cardSelect = document.getElementById('existing_card_id');
        const cardOption = document.getElementById('cardOption');
        const cardOnFileDropdown = document.getElementById('cardOnFileDropdown');
        const paymentType = document.getElementById('payment_type');

       cardSelect.innerHTML = `<option value="">-- Select a saved card --</option>`;

        if (paymentType.value === 'CreditCard' && Array.isArray(alert.customer.cards) && alert.customer.cards.length > 0) {
            alert.customer.cards.forEach(card => {
                cardSelect.insertAdjacentHTML(
                    'beforeend',
                    `<option value="${card.id}">${card.label}</option>`
                );
            });

            cardOption.value = 'CardOnFile';


            cardOnFileDropdown.classList.remove('hidden');
            
        } else {
            cardOption.value = 'NewCard';
            cardOnFileDropdown.classList.add('hidden');
        }

        this.selectedDamageAlert = alert;

    }

    closePaymentModal() {
        const modal = document.getElementById('PaymentModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';

        // reset selection
        this.selectedDamageAlert = null;
    }


    removeSelectedDamageAlert() {
        if (!this.selectedDamageAlert) return;

        this.damageAlerts = this.damageAlerts.filter(
            a => a.id !== this.selectedDamageAlert.id
        );

        this.updateDamageAlertHeader();
        this.updateDamageAlertBadge();
        this.renderDamageAlerts();

        this.selectedDamageAlert = null;
    }





    saveAmount() {
            if (!this.editingAlert) {
            notyf.error("Alert not found.");
            return;
        }

        const amount = document.getElementById("amount-input").value.trim();

        
        if (!amount || isNaN(amount)) {
            notyf.error("Please enter a valid amount.");
            return;
        }


    
     const url =
        "{{ route('admin.dashboard.amount.update', ':unique_id') }}"
            .replace(":unique_id", this.editingAlert.order_product.unique_id);

        const saveBtn = document.querySelector("#amount-modal #save-btn-amount");
        const originalText = saveBtn.textContent;

        saveBtn.disabled = true;
        saveBtn.textContent = "Saving...";

        apiFetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                amount: parseFloat(amount),
                type: this.editingAlert.type, // fuel | damage
                user_id: window.AUTH_USER_ID,
            }),
        })
        .then((res) => {
            if (res && res.success) {
                notyf.success(res.message || "Amount updated");

                // Update local UI state
                this.editingAlert.amountOwed = `$${parseFloat(amount).toFixed(2)}`;

                this.closeAmountModal();
                this.renderFuelAlerts();
                this.renderDamageAlerts();
            } else {
                notyf.error(res?.message || "Failed to save amount");
            }
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
        });
    }

    saveNotes() {
        if (!this.activeOrderUniqueId) {
            notyf.error("Order not found.");
            return;
        }

        const note = document.getElementById("notes-input").value.trim();
        if (!note) {
            notyf.error("Please enter a note.");
            return;
        }

        const url =
            "{{ route('admin.dashboard.notes.store', ':unique_id') }}"
            .replace(':unique_id', this.activeOrderUniqueId);

        const saveBtn = document.querySelector("#notes-modal button.bg-blue-600");
        const originalText = saveBtn.textContent;

        saveBtn.disabled = true;
        saveBtn.textContent = "Saving...";

        apiFetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                note: note,
                user_id: window.AUTH_USER_ID,
            }),
        })
            .then((res) => {
                if (res && res.success) {
                    notyf.success(res.message || "Note added");

                    //  Update alert locally (UI sync)
                    this.editingAlert.notes = note;

                    this.closeNotesModal();
                    this.renderDamageAlerts();
                    this.renderFuelAlerts();
                } else {
                    notyf.error(res?.message || "Failed to save note");
                }
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            });
}


    /** ------------------------
     *         CHARTS
     --------------------------*/
    getSalesData() {
        return salesDataFromServer[this.salesPeriod];
    }

    updateSalesMetrics() {
        const m = this.currentMetrics;

        // total sales
        document.getElementById("total-sales").textContent =
            this.formatCurrency(m.totalSales);

        // previous total
        document.getElementById("previous-total").textContent =
            this.formatCurrency(m.previousTotalSales);

        // daily average
        document.getElementById("daily-average").textContent =
            this.formatCurrency(m.dailyAverage);

        // growth value
        const growthValue = document.getElementById("growth-rate-value");
        growthValue.textContent = Math.abs(m.growthRate).toFixed(1) + "%";

        // wrapper color
        const wrapper = document.getElementById("growth-rate-wrapper");
        wrapper.classList.remove("text-emerald-800", "text-red-800");
        wrapper.classList.add(m.growthRate >= 0 ? "text-emerald-800" : "text-red-800");

        // icon
        const iconEl = document.getElementById("growth-icon");

        if (m.growthRate >= 0) {
            iconEl.innerHTML = `
                <svg fill="currentColor" class="w-6 h-6" viewBox="0 0 640 640">
                    <path d="M416 224C398.3 224 384 209.7 384 192C384 174.3 398.3 160 416 160L576 160C593.7 160 608 174.3 608 192L608 352C608 369.7 593.7 384 576 384C558.3 384 544 369.7 544 352L544 269.3L374.6 438.7C362.1 451.2 341.8 451.2 329.3 438.7L224 333.3L86.6 470.6C74.1 483.1 53.8 483.1 41.3 470.6C28.8 458.1 28.8 437.8 41.3 425.3L201.3 265.3C213.8 252.8 234.1 252.8 246.6 265.3L352 370.7L498.7 224L416 224z"/>
                </svg>`;
        } else {
            iconEl.innerHTML = `
                <svg fill="currentColor" class="w-6 h-6" viewBox="0 0 640 640">
                    <path d="M416 416C398.3 416 384 430.3 384 448C384 465.7 398.3 480 416 480L576 480C593.7 480 608 465.7 608 448L608 288C608 270.3 593.7 256 576 256C558.3 256 544 270.3 544 288L544 370.7L374.6 201.3C362.1 188.8 341.8 188.8 329.3 201.3L224 306.7L86.6 169.4C74.1 156.9 53.8 156.9 41.3 169.4C28.8 181.9 28.8 202.2 41.3 214.7L201.3 374.7C213.8 387.2 234.1 387.2 246.6 374.7L352 269.3L498.7 416L416 416z"/>
                </svg>`;
        }
    }


    initSalesChart() {
        const el = document.getElementById("salesChart");
        if (!el) return;

        const data = this.getSalesData();

        this.salesChart = new ApexCharts(el, {
            series: [
                { name: "Current Period", data: data.current },
                { name: "Previous Period", data: data.previous },
            ],
            chart: {
                height: 400,
                type: "line",
                toolbar: { show: false },
                zoom: { enabled: false },
            },
            xaxis: {
                categories: data.categories,
            },
            yaxis: {
                labels: {
                    formatter: (val) => this.formatCurrency(val),
                },
            },
            tooltip: {
                y: {
                    formatter: (val) => this.formatCurrency(val),
                },
            },
        });


        this.salesChart.render();
    }

    updateSalesChart() {
        if (!this.salesChart) return;
        const data = this.getSalesData();
 this.salesChart.updateOptions({
        xaxis: {
            categories: data.categories, 
        },
    });

        this.salesChart.updateSeries([
            { name: "Current Period", data: data.current },
            { name: "Previous Period", data: data.previous },
        ]);
    }

    initMaintenanceChart() {
        const el = document.getElementById("maintenanceChart");
        if (!el) return;

        this.maintenanceChart = new ApexCharts(el, {
            series: [
                { name: "Due", data: window.chartData.maintenance.due },
                { name: "Completed", data:  window.chartData.maintenance.completed },
            ], 
            xaxis: {
                categories: window.chartData.labels,
            },
            chart: { height: 300, type: "line", toolbar: { show: false } },
        });

        this.maintenanceChart.render();
    }

    initDamagedChart() {
        const el = document.getElementById("damagedChart");
        if (!el) return;

        this.damagedChart = new ApexCharts(el, {
            series: [
                { name: "Due", data: window.chartData.damaged.due },
                { name: "Completed", data: window.chartData.damaged.completed },
            ],
             xaxis: {
                categories: window.chartData.labels,
            },
            chart: { height: 300, type: "line", toolbar: { show: false } },
        });

        this.damagedChart.render();
    }



    updateMaintenanceChart(chartData) {
    if (!this.maintenanceChart) return;

        this.maintenanceChart.updateOptions({
            xaxis: { categories: chartData.labels }
        });

        this.maintenanceChart.updateSeries([
            { name: "Due", data: chartData.maintenance.due },
            { name: "Completed", data: chartData.maintenance.completed },
        ]);
    }

    updateDamagedChart(chartData) {
        if (!this.damagedChart) return;

        this.damagedChart.updateOptions({
            xaxis: { categories: chartData.labels }
        });

        this.damagedChart.updateSeries([
            { name: "Due", data: chartData.damaged.due },
            { name: "Completed", data: chartData.damaged.completed },
        ]);
    }


}


document.addEventListener("DOMContentLoaded", () => {
    window.dashboardApp = new Dashboard(
        document.getElementById("dashboard")
    );
});


document.addEventListener('livewire:init', () => {
    Livewire.on('alerts-updated', ({ alerts , fuelAlerts  }) => {

        if (!window.dashboardApp) return;
        // Damage
        window.dashboardApp.damageAlerts = alerts;
        window.dashboardApp.updateDamageAlertHeader();
        window.dashboardApp.updateDamageAlertBadge();
        window.dashboardApp.renderDamageAlerts();

        
        // Fuel
        window.dashboardApp.fuelAlerts = fuelAlerts;
        window.dashboardApp.updateFuelAlertHeader();
        window.dashboardApp.updateFuelAlertBadge();
        window.dashboardApp.renderFuelAlerts();
    });
});


document.addEventListener('livewire:init', () => {
    Livewire.on('maintenance-charts-updated', ({ chartData }) => {
        if (!window.dashboardApp) return;

        window.dashboardApp.updateMaintenanceChart(chartData);
        window.dashboardApp.updateDamagedChart(chartData);
    });
});





</script>
@endpush
