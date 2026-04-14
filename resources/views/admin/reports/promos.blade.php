@extends('layouts.admin.app', ['page' => 'reports'])

@section('title', 'Promo Code Report')

@section('header_data')
    @php
        $page_title    = 'Promo Code Report';
        $page_pretitle = 'Reports';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Reports',         'url' => route('admin.reports.sales')],
        ['title' => 'Promo Codes',     'url' => ''],
    ];
@endphp

@section('admin-content')
<div class="page-body">
    <div class="container-xl">

        @include('admin.reports._filter')

        {{-- Summary cards --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Orders with Promo</div>
                        <div class="h1 mt-1" id="cardOrders">—</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Total Discount Given</div>
                        <div class="h1 mt-1 text-danger" id="cardDiscount">—</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Unique Codes Used</div>
                        <div class="h1 mt-1" id="cardCodes">—</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daily discount trend chart --}}
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title">Daily Discount Trend</h4>
            </div>
            <div class="card-body">
                <div id="promoChart" style="height:280px"></div>
            </div>
        </div>

        {{-- Per-code breakdown table --}}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Per Promo Code Breakdown</h4>
            </div>
            <div class="card-body p-0">
                <table class="table table-vcenter table-hover">
                    <thead>
                        <tr>
                            <th>Promo Code</th>
                            <th class="text-end">Uses</th>
                            <th class="text-end">Total Discount</th>
                            <th class="text-end">Avg Discount</th>
                            <th class="text-end">Gross Revenue</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody id="promoTableBody"></tbody>
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
    return '₹' + parseFloat(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function loadData() {
    const from = $('#dateFrom').val();
    const to   = $('#dateTo').val();

    $.get('{{ route('admin.reports.promos.data') }}', { from, to }, function (res) {
        // Summary cards
        $('#cardOrders').text(res.summary?.total_promo_orders ?? 0);
        $('#cardDiscount').text(fmt(res.summary?.total_discount_given));
        $('#cardCodes').text(res.summary?.unique_codes_used ?? 0);

        // Chart
        const dates    = res.daily.map(r => r.date);
        const discounts = res.daily.map(r => parseFloat(r.discount || 0));
        if (chart) { chart.destroy(); }
        chart = new ApexCharts(document.querySelector('#promoChart'), {
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            series: [{ name: 'Discount Given', data: discounts }],
            xaxis: { categories: dates },
            yaxis: { labels: { formatter: v => '₹' + v.toLocaleString('en-IN') } },
            colors: ['#d63939'],
            dataLabels: { enabled: false },
            plotOptions: { bar: { borderRadius: 4 } },
        });
        chart.render();

        // Per-code table
        let html = '';
        if (res.by_code.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-muted py-4">No promo usage in this period</td></tr>';
        } else {
            res.by_code.forEach(row => {
                const badge = row.is_cashback
                    ? '<span class="badge bg-yellow-lt">Cashback</span>'
                    : '<span class="badge bg-green-lt">Instant</span>';
                html += `<tr>
                    <td><span class="fw-bold font-monospace">${row.promo_code}</span></td>
                    <td class="text-end">${row.uses}</td>
                    <td class="text-end text-danger fw-semibold">${fmt(row.total_discount)}</td>
                    <td class="text-end">${fmt(row.avg_discount)}</td>
                    <td class="text-end">${fmt(row.gross_revenue)}</td>
                    <td>${badge}</td>
                </tr>`;
            });
        }
        $('#promoTableBody').html(html);
    });
}

$(function () {
    const today = new Date().toISOString().split('T')[0];
    const from  = new Date(Date.now() - 29 * 86400000).toISOString().split('T')[0];
    $('#dateFrom').val(from);
    $('#dateTo').val(today);
    loadData();
    $('#applyFilter').on('click', loadData);
});
</script>
@endpush
