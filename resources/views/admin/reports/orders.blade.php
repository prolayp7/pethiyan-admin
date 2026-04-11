@extends('layouts.admin.app', ['page' => 'reports'])

@section('title', 'Orders Report')

@section('header_data')
    @php
        $page_title   = 'Orders Report';
        $page_pretitle = 'Reports';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Reports', 'url' => route('admin.reports.sales')],
        ['title' => 'Orders', 'url' => ''],
    ];
@endphp

@section('admin-content')
<div class="page-body">
    <div class="container-xl">
        @include('admin.reports._filter')

        <div class="row g-4">
            {{-- Orders by day --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Orders per Day</h4></div>
                    <div class="card-body">
                        <div id="ordersChart" style="height:280px"></div>
                    </div>
                </div>
            </div>

            {{-- Orders by status --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">By Status</h4></div>
                    <div class="card-body">
                        <div id="statusChart" style="height:280px"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status table --}}
        <div class="card mt-4">
            <div class="card-header"><h4 class="card-title">Status Breakdown</h4></div>
            <div class="card-body p-0">
                <table class="table table-vcenter">
                    <thead><tr><th>Status</th><th>Count</th></tr></thead>
                    <tbody id="statusTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
let dayChart = null, pieChart = null;

function loadData() {
    const from = $('#dateFrom').val();
    const to   = $('#dateTo').val();
    $.get('{{ route('admin.reports.orders.data') }}', { from, to }, function (res) {
        // Day chart
        if (dayChart) dayChart.destroy();
        dayChart = new ApexCharts(document.querySelector('#ordersChart'), {
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            series: [{ name: 'Orders', data: res.by_day.map(r => r.count) }],
            xaxis: { categories: res.by_day.map(r => r.date) },
            colors: ['#2563eb'],
            dataLabels: { enabled: false },
        });
        dayChart.render();

        // Pie chart
        if (pieChart) pieChart.destroy();
        pieChart = new ApexCharts(document.querySelector('#statusChart'), {
            chart: { type: 'donut', height: 280 },
            series: res.by_status.map(r => parseInt(r.count)),
            labels: res.by_status.map(r => r.status),
            legend: { position: 'bottom' },
        });
        pieChart.render();

        // Table
        let html = '';
        res.by_status.forEach(r => {
            html += `<tr><td class="text-capitalize">${r.status.replace(/_/g,' ')}</td><td>${r.count}</td></tr>`;
        });
        $('#statusTableBody').html(html || '<tr><td colspan="2" class="text-center text-muted">No data</td></tr>');
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
