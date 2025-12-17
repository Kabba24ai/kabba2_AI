@extends('admin.layouts.app')

@section('title', 'Dashboard')

@push('css')

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

        </div>
    </div>
@endsection
@push('js')
    <script>
        const salesDataFromServer = @json($salesData);
        //  REAL DATA from backend
        window.damageAlerts = @json($damagedOrderAlerts);

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
                return [{
                        id: 1,
                        customerName: 'ABC Events LLC',
                        orderId: 'ORD-2024-001',
                        amountOwed: 'Pending',
                        date: '2024-01-27',
                        type: 'fuel',
                        notes: ''
                    },
                    {
                        id: 2,
                        customerName: 'Wedding Bliss Co',
                        orderId: 'ORD-2024-002',
                        amountOwed: '$45.00',
                        date: '2024-01-26',
                        type: 'fuel',
                        notes: 'Customer disputed charge initially'
                    },
                    {
                        id: 3,
                        customerName: 'Corporate Solutions',
                        orderId: 'ORD-2024-003',
                        amountOwed: 'Pending',
                        date: '2024-01-25',
                        type: 'fuel',
                        notes: ''
                    },
                    {
                        id: 4,
                        customerName: 'Party Time Rentals',
                        orderId: 'ORD-2024-004',
                        amountOwed: '$32.50',
                        date: '2024-01-24',
                        type: 'fuel',
                        notes: 'Awaiting payment confirmation'
                    },
                    {
                        id: 5,
                        customerName: 'Elite Celebrations',
                        orderId: 'ORD-2024-005',
                        amountOwed: 'Pending',
                        date: '2024-01-23',
                        type: 'fuel',
                        notes: 'Need to calculate mileage'
                    },
                    {
                        id: 6,
                        customerName: 'Dream Weddings Inc',
                        orderId: 'ORD-2024-006',
                        amountOwed: '$28.75',
                        date: '2024-01-22',
                        type: 'fuel',
                        notes: ''
                    },
                ];
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

                const list = this.fuelAlerts.slice(0, 5);

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
                    item.querySelector("[data-order-id]").textContent = alert.orderId;

                    // Order click
                    item.querySelector("[data-order-btn]").onclick = () => this.handleOrderClick(alert.orderId);

                    // Edit amount
                    item.querySelector("[data-edit-amount]").onclick = () => {
                        this.editingAlert = alert;
                        this.tempAmount = alert.amountOwed === "Pending" ? "" : alert.amountOwed.replace(
                            "$", "");
                        // this.showAmountModal = true;
                        this.openAmountModal(alert);

                    };

                    // Status dropdown toggle
                    const dropdown = item.querySelector("[data-status-dropdown]");
                    item.querySelector("[data-status-btn]").onclick = () => {
                        dropdown.classList.toggle("hidden");
                    };

                    // Status actions
                    item.querySelector("[data-paid]").onclick = () => this.handleStatusUpdate("fuel", alert.id,
                        "paid");
                    item.querySelector("[data-uncollectible]").onclick = () => this.handleStatusUpdate("fuel",
                        alert.id, "uncollectible");

                    // Edit notes
                    item.querySelector("[data-edit-notes]").onclick = () => {
                        this.editingAlert = alert;
                        // this.tempNotes = alert.notes || "";

                        this.tempNotes = alert.notes || "";

                        this.activeOrderUniqueId = alert.orderId;

                        this.openNotesModal(alert);

                    };

                    // Amount display
                    const amountEl = item.querySelector("[data-amount]");
                    amountEl.textContent = alert.amountOwed;

                    if (alert.amountOwed === "Pending") {
                        amountEl.className =
                            "text-sm font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded-full";
                    } else {
                        amountEl.className = "text-sm font-semibold text-green-700";
                    }

                    // Notes
                    if (alert.notes) {
                        item.querySelector("[data-notes-wrapper]").classList.remove("hidden");
                        item.querySelector("[data-notes]").textContent = alert.notes;
                    }

                    wrapper.appendChild(item);
                });
            }

            openAmountModal(alert) {
                this.editingAlert = alert;
                this.tempAmount = alert.amountOwed === "Pending" ? "" : alert.amountOwed.replace("$", "");

                document.getElementById("amount-input").value = this.tempAmount;
                document.getElementById("amount-modal-title").textContent =
                    alert.amountOwed === "Pending" ? "Add Amount" : "Edit Amount";

                document.getElementById("amount-modal").classList.remove("hidden");
            }

            closeAmountModal() {
                document.getElementById("amount-modal").classList.add("hidden");
            }


            // DamageAlerts

            defaultDamageAlerts() {

                console.log('window.damageAlerts');
                console.log(window.damageAlerts);
                console.log('window.damageAlerts');


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

                const list = this.damageAlerts.slice(0, 5);

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
                    item.querySelector("[data-order-id]").textContent = alert.orderId;

                    // Buttons
                    item.querySelector("[data-order-btn]").onclick = () => {
                        if (alert.orderLink) {
                            // window.location.href = alert.orderLink;
                            window.open(alert.orderLink, "_blank");

                        }
                    };


                    item.querySelector("[data-edit-amount]").onclick = () => {
                        this.editingAlert = alert;
                        this.tempAmount = alert.amountOwed === "Pending" ? "" : alert.amountOwed.replace(
                            "$", "");
                        // this.showAmountModal = true;
                        this.openAmountModal(alert);

                    };

                    const dropdown = item.querySelector("[data-status-dropdown]");
                    item.querySelector("[data-status-btn]").onclick = () => dropdown.classList.toggle("hidden");

                    item.querySelector("[data-paid]").onclick = () => this.handleStatusUpdate("damage", alert
                        .id, "paid");
                    item.querySelector("[data-uncollectible]").onclick = () => this.handleStatusUpdate("damage",
                        alert.id, "uncollectible");

                    item.querySelector("[data-edit-notes]").onclick = () => {
                        this.editingAlert = alert;
                        this.tempNotes = "";
                        // this.showNotesModal = true;
                        this.activeOrderUniqueId = alert.orderId;

                        console.log("Dashboard Notes → Order ID:", this.activeOrderUniqueId);

                        this.openNotesModal(alert);

                    };

                    // Amount color
                    const amountEl = item.querySelector("[data-amount]");
                    amountEl.textContent = alert.amountOwed;
                    amountEl.className =
                        alert.amountOwed === "Pending" ?
                        "text-sm font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded-full" :
                        "text-sm font-semibold text-green-700";

                    // Notes
                    if (alert.notes) {
                        item.querySelector("[data-notes-wrapper]").classList.remove("hidden");
                        item.querySelector("[data-notes]").textContent = alert.notes;
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
                this.tempNotes = "";

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
                    previousTotalSales > 0 ?
                    ((totalSales - previousTotalSales) / previousTotalSales) * 100 :
                    0;

                const dailyAverage =
                    this.salesPeriod === "rolling30" ?
                    totalSales / 30 :
                    totalSales / data.current.length;

                return {
                    totalSales,
                    previousTotalSales,
                    growthRate,
                    dailyAverage
                };
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


            /** ------------------------
             *      CURRENCY FORMATTER
             --------------------------*/
            formatCurrency(val) {
                return (
                    "$" +
                    val.toLocaleString("en-US", {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0,
                    })
                );
            }

            /** ------------------------
             *         ALERTS
             --------------------------*/
            handleStatusUpdate(type, id, status) {
                if (type === "fuel") {
                    this.fuelAlerts = this.fuelAlerts.filter((a) => a.id !== id);

                    this.updateFuelAlertHeader();
                    this.updateFuelAlertBadge();
                    this.renderFuelAlerts();

                } else {
                    this.damageAlerts = this.damageAlerts.filter((a) => a.id !== id);
                    this.updateDamageAlertHeader(); // NEW
                    this.updateDamageAlertBadge(); // NEW
                    this.renderDamageAlerts();
                }
            }

            saveAmount() {
                if (!this.editingAlert) return;

                const formatted = this.tempAmount ?
                    `$${parseFloat(this.tempAmount).toFixed(2)}` :
                    "Pending";

                const list =
                    this.editingAlert.type === "fuel" ?
                    this.fuelAlerts :
                    this.damageAlerts;

                const index = list.findIndex((a) => a.id === this.editingAlert.id);
                if (index !== -1) list[index].amountOwed = formatted;

                this.editingAlert = null;
                this.tempAmount = "";
                this.closeAmountModal();
                this.renderFuelAlerts();
                this.renderDamageAlerts();

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
                    "{{ route('admin.order-management.orders.notes.store', ':unique_id') }}"
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
                    series: [{
                            name: "Current Period",
                            data: data.current
                        },
                        {
                            name: "Previous Period",
                            data: data.previous
                        },
                    ],
                    chart: {
                        height: 400,
                        type: "line",
                        toolbar: {
                            show: false
                        },
                    },
                });

                this.salesChart.render();
            }

            updateSalesChart() {
                if (!this.salesChart) return;
                const data = this.getSalesData();

                this.salesChart.updateSeries([{
                        name: "Current Period",
                        data: data.current
                    },
                    {
                        name: "Previous Period",
                        data: data.previous
                    },
                ]);
            }

            initMaintenanceChart() {
                const el = document.getElementById("maintenanceChart");
                if (!el) return;

                this.maintenanceChart = new ApexCharts(el, {
                    series: [{
                            name: "Due",
                            data: window.chartData.maintenance.due
                        },
                        {
                            name: "Completed",
                            data: window.chartData.maintenance.completed
                        },
                    ],
                    xaxis: {
                        categories: window.chartData.labels,
                    },
                    chart: {
                        height: 300,
                        type: "line",
                        toolbar: {
                            show: false
                        }
                    },
                });

                this.maintenanceChart.render();
            }

            initDamagedChart() {
                const el = document.getElementById("damagedChart");
                if (!el) return;

                this.damagedChart = new ApexCharts(el, {
                    series: [{
                            name: "Due",
                            data: window.chartData.damaged.due
                        },
                        {
                            name: "Completed",
                            data: window.chartData.damaged.completed
                        },
                    ],
                    xaxis: {
                        categories: window.chartData.labels,
                    },
                    chart: {
                        height: 300,
                        type: "line",
                        toolbar: {
                            show: false
                        }
                    },
                });

                this.damagedChart.render();
            }
        }

        /** ------------------------
         *      MOUNT THE DASHBOARD
         --------------------------*/
        document.addEventListener("DOMContentLoaded", () => {
            window.dashboard = new Dashboard(document.getElementById("dashboard"));
        });



        // JS listens to Livewire events

        document.addEventListener('livewire:init', () => {
            Livewire.on('alerts-updated', ({
                alerts
            }) => {
                dashboard.damageAlerts = alerts;
                dashboard.updateDamageAlertHeader();
                dashboard.updateDamageAlertBadge();
                dashboard.renderDamageAlerts();
            });
        });
    </script>
@endpush
