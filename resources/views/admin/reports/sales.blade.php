@extends('layouts.admin.app', ['page' => 'reports'])

@section('title', 'Sales Report')

@section('header_data')
    @php
        $page_title   = 'Sales Report';
        $page_pretitle = 'Reports';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Reports', 'url' => route('admin.reports.sales')],
        ['title' => 'Sales', 'url' => ''],
    ];
@endphp

@section('admin-content')
<div class="page-body">
    <div class="container-xl">
        @include('admin.reports._filter')

        {{-- Summary cards --}}
        <div class="row g-3 mb-4" id="summaryCards">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Total Revenue</div>
                        <div class="h1 mt-1" id="cardRevenue">—</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Total Orders</div>
                        <div class="h1 mt-1" id="cardOrders">—</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Avg Order Value</div>
                        <div class="h1 mt-1" id="cardAov">—</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Daily Revenue</h4>
                <a href="#" class="btn btn-primary btn-sm" id="downloadSalesCsv">Download CSV</a>
            </div>
            <div class="card-body">
                <div id="salesChart" style="height:300px"></div>
            </div>
        </div>

        {{-- Data table --}}
        <div class="card mt-4">
            <div class="card-header">
                <h4 class="card-title">Daily Breakdown</h4>
            </div>
            <div class="card-body p-0">
                <table class="table table-vcenter">
                    <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
                    <tbody id="salesTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
let chart = null;

function fmt(n) {
    return '₹' + parseFloat(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
}

function loadData() {
    const from = $('#dateFrom').val();
    const to   = $('#dateTo').val();
    $.get('{{ route('admin.reports.sales.data') }}', { from, to }, function (res) {
        // Summary
        $('#cardRevenue').text(fmt(res.summary?.total_revenue));
        $('#cardOrders').text(res.summary?.total_orders ?? 0);
        $('#cardAov').text(fmt(res.summary?.avg_order_value));

        // Chart
        const dates    = res.chart.map(r => r.date);
        const revenues = res.chart.map(r => parseFloat(r.revenue));
        if (chart) { chart.destroy(); }
        chart = new ApexCharts(document.querySelector('#salesChart'), {
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            series: [{ name: 'Revenue', data: revenues }],
            xaxis: { categories: dates },
            yaxis: { labels: { formatter: v => '₹' + v.toLocaleString('en-IN') } },
            colors: ['#2563eb'],
            fill: { type: 'gradient' },
            stroke: { curve: 'smooth' },
            dataLabels: { enabled: false },
        });
        chart.render();

        // Table
        let html = '';
        res.chart.forEach(r => {
            html += `<tr><td>${r.date}</td><td>${r.order_count}</td><td>${fmt(r.revenue)}</td></tr>`;
        });
        $('#salesTableBody').html(html || '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>');
    });
}

$(function () {
    const today = new Date().toISOString().split('T')[0];
    const from  = new Date(Date.now() - 29 * 86400000).toISOString().split('T')[0];
    $('#dateFrom').val(from);
    $('#dateTo').val(today);
    loadData();
    $('#applyFilter').on('click', loadData);

    $('#downloadSalesCsv').on('click', function (event) {
        event.preventDefault();

        const from = $('#dateFrom').val();
        const to = $('#dateTo').val();
        const url = new URL('{{ route('admin.reports.sales.export') }}', window.location.origin);

        if (from) url.searchParams.set('from', from);
        if (to) url.searchParams.set('to', to);

        window.location.href = url.toString();
    });
});
</script>
@endpush
