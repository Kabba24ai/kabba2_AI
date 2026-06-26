@extends('admin.layouts.app')

@section('title', 'Operation Report')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Reports</p>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Operation</h3>
        </div>
    </div>

    <div class="bg-[#e5e5e5] rounded-2xl border border-gray-300 p-4 sm:p-6 md:p-8">
        <div class="max-w-4xl mx-auto">
            <h4 class="text-center text-lg sm:text-4xl font-bold text-gray-900 mb-1">{{ $chartTitle ?? 'Rental Ready Fulfillment Rate' }}</h4>
            <p class="text-center text-sm text-gray-700 mb-5">Based on {{ $totalAssigned }} assigned equipment</p>
            <div id="operation-pie-chart" class="w-full" style="min-height:420px;"></div>
            <div id="operation-pie-legend" class="flex flex-wrap justify-center gap-4 mt-4"></div>
        </div>
    </div>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const pieData      = @json($chartData);
    const labels       = pieData.map(item => item.label);
    const counts       = pieData.map(item => Number(item.count || 0));
    const totalAssigned = Number(@json($totalAssigned));
    const COLORS       = ['#2EBE20', '#3B82F6', '#EBCF2A', '#EF4444'];

    const chartEl  = document.getElementById('operation-pie-chart');
    const legendEl = document.getElementById('operation-pie-legend');

    if (!chartEl || typeof ApexCharts === 'undefined') {
        return;
    }

    if (totalAssigned === 0) {
        chartEl.innerHTML = '<div class="text-center text-gray-600 py-20 text-base">No assigned equipment found for active orders.</div>';
        return;
    }

    // Build custom legend
    if (legendEl) {
        legendEl.innerHTML = pieData.map(function (row, i) {
            const color = COLORS[i] || '#6B7280';
            const pct   = row.value || 0;
            return '<div class="flex items-center gap-2 bg-white rounded-xl px-4 py-2 shadow-sm">'
                + '<span style="width:14px;height:14px;border-radius:50%;background:' + color + ';display:inline-block;flex-shrink:0;"></span>'
                + '<span class="text-sm font-semibold text-gray-800">' + row.label + '</span>'
                + '<span class="text-sm text-gray-500">' + row.count + ' &nbsp;|&nbsp; ' + pct + '%</span>'
                + '</div>';
        }).join('');
    }

    const chart = new ApexCharts(chartEl, {
        chart: {
            type: 'pie',
            height: 420,
            toolbar: { show: false }
        },
        series: counts,
        labels: labels,
        colors: COLORS,
        legend: { show: false },
        stroke: {
            show: true,
            width: 2,
            colors: ['#ffffff']
        },
        dataLabels: {
            enabled: true,
            textAnchor: 'middle',
            distributed: false,
            style: {
                fontSize: '15px',
                fontWeight: '700',
                colors: ['#ffffff']
            },
            formatter: function (value, opts) {
                const idx   = opts.seriesIndex;
                const label = labels[idx] || '';
                const pct   = Number(value).toFixed(1);
                return [label, pct + '%'];
            },
            dropShadow: {
                enabled: true,
                blur: 3,
                opacity: 0.4
            }
        },
        plotOptions: {
            pie: {
                dataLabels: {
                    offset: -20,
                    minAngleToShowLabel: 10
                }
            }
        },
        tooltip: {
            custom: function ({ seriesIndex }) {
                const row   = pieData[seriesIndex] || {};
                const count = Number(row.count || 0);
                const pct   = Number(row.value || 0).toFixed(1);
                return '<div style="padding:8px 12px;font-size:13px;">'
                    + '<div style="font-weight:600;color:#111;">' + (row.label || '') + '</div>'
                    + '<div style="color:#555;">' + count + ' equipment &nbsp;(' + pct + '%)</div>'
                    + '</div>';
            }
        },
        states: {
            hover:  { filter: { type: 'none' } },
            active: { filter: { type: 'none' } }
        },
        responsive: [
            {
                breakpoint: 768,
                options: {
                    chart: { height: 340 },
                    dataLabels: { style: { fontSize: '12px' } }
                }
            }
        ]
    });

    chart.render();
});
</script>
@endpush
