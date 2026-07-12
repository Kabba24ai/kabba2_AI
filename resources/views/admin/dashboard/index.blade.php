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

        <!-- Section 2: Task Manager Alerts -->
        <div class="mb-6">
             @include('admin.dashboard.partials._alerts_activities')
        </div>

        <!-- Section 3: Charge Alerts -->
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center">
                    <x-heroicon-o-banknotes class="w-5 h-5 text-red-600" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Charge Alerts</h2>
                    <p class="text-sm text-gray-500">Outstanding fuel and damage charges that need attention</p>
                </div>
            </div>
            <livewire:dashboard.alerts-section />
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

            // charts
            this.salesChart = null;
            this.maintenanceChart = null;
            this.damagedChart = null;

            // initialize
            this.init();
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
    Livewire.on('maintenance-charts-updated', ({ chartData }) => {
        if (!window.dashboardApp) return;

        window.dashboardApp.updateMaintenanceChart(chartData);
        window.dashboardApp.updateDamagedChart(chartData);
    });
});


/* ── Charge Alerts donut charts (Dashboard V2 Phase 1B) ───────────────────
   One ApexCharts donut per card (Outstanding vs Completed Today). Canvases
   are wire:ignore, so we own their lifecycle: render once, then updateSeries
   on the 30s poll WITHOUT re-animating. Instances are cached by element id so
   Livewire refreshes never leak duplicate charts/canvases. */
window.chargeDonuts = window.chargeDonuts || {};

function buildChargeDonutSeries(outstanding, completed) {
    const empty = (outstanding + completed) === 0;
    return {
        empty,
        series: empty ? [1] : [outstanding, completed],
    };
}

function renderChargeDonut(id) {
    const el = document.getElementById(id);
    if (!el || typeof ApexCharts === 'undefined') return;

    const outstanding = parseInt(el.dataset.outstanding || '0', 10);
    const completed   = parseInt(el.dataset.completed || '0', 10);
    const accent      = el.dataset.color || '#f97316';
    const { empty, series } = buildChargeDonutSeries(outstanding, completed);

    // Rebuild cleanly if one already exists for this id.
    if (window.chargeDonuts[id]) {
        try { window.chargeDonuts[id].destroy(); } catch (e) {}
        delete window.chargeDonuts[id];
    }

    const chart = new ApexCharts(el, {
        chart: { type: 'donut', height: 200, animations: { enabled: true } },
        series: series,
        labels: empty ? ['No Active Alerts'] : ['Outstanding', 'Completed Today'],
        colors: empty ? ['#e5e7eb'] : [accent, '#d1d5db'],
        legend: { show: false },
        dataLabels: { enabled: false },
        stroke: { width: 0 },
        plotOptions: { pie: { donut: { size: '72%' } } },
        tooltip: { enabled: !empty },
        states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } },
    });
    chart.render();
    window.chargeDonuts[id] = chart;
}

function updateChargeDonut(id, outstanding, completed) {
    const el = document.getElementById(id);
    if (!el) return;
    // Keep data attrs current (used for first render / re-render).
    el.dataset.outstanding = outstanding;
    el.dataset.completed = completed;

    const chart = window.chargeDonuts[id];
    if (!chart) { renderChargeDonut(id); return; }

    const { empty, series } = buildChargeDonutSeries(outstanding, completed);
    // A change to/from the empty state changes label/color/tooltip config, so
    // re-render; otherwise just swap series without a distracting re-animation.
    const wasEmpty = chart.w?.config?.series?.length === 1
        && chart.w?.config?.labels?.[0] === 'No Active Alerts';
    if (empty !== wasEmpty) {
        renderChargeDonut(id);
    } else {
        chart.updateSeries(series, false);
    }
}

document.addEventListener('livewire:init', () => {
    // Initial render from server-rendered data attributes.
    renderChargeDonut('fuel-charge-donut');
    renderChargeDonut('damage-charge-donut');

    // Live updates on mount dispatch + every poll.
    Livewire.on('charge-alerts-updated', (payload) => {
        const charts = (payload && (payload.charts ?? (Array.isArray(payload) ? payload[0]?.charts : null))) || {};
        Object.keys(charts).forEach((id) => {
            updateChargeDonut(id, charts[id].outstanding || 0, charts[id].completed || 0);
        });
    });
});





</script>
@endpush
