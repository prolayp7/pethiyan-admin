@extends('layouts.admin.app', ['page' => 'reports'])

@section('title', 'Customers Report')

@section('header_data')
    @php
        $page_title   = 'Customers Report';
        $page_pretitle = 'Reports';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Reports', 'url' => route('admin.reports.sales')],
        ['title' => 'Customers', 'url' => ''],
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
                        <div class="subheader">Total Customers</div>
                        <div class="h1 mt-1" id="cardTotal">—</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">New in Period</div>
                        <div class="h1 mt-1" id="cardNew">—</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Placed an Order</div>
                        <div class="h1 mt-1" id="cardOrdered">—</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">New Registrations per Day</h4>
                <a href="#" class="btn btn-primary btn-sm" id="downloadCustomersCsv">Download CSV</a>
            </div>
            <div class="card-body">
                <div id="customersChart" style="height:280px"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
let chart = null;

function loadData() {
    const from = $('#dateFrom').val();
    const to   = $('#dateTo').val();
    $.get('{{ route('admin.reports.customers.data') }}', { from, to }, function (res) {
        $('#cardTotal').text(res.summary?.total_customers ?? 0);
        $('#cardNew').text(res.summary?.new_in_period ?? 0);
        $('#cardOrdered').text(res.summary?.customers_with_orders ?? 0);

        if (chart) chart.destroy();
        chart = new ApexCharts(document.querySelector('#customersChart'), {
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            series: [{ name: 'New Customers', data: res.registrations.map(r => r.count) }],
            xaxis: { categories: res.registrations.map(r => r.date) },
            colors: ['#2563eb'],
            dataLabels: { enabled: false },
        });
        chart.render();
    });
}

$(function () {
    const today = new Date().toISOString().split('T')[0];
    const from  = new Date(Date.now() - 29 * 86400000).toISOString().split('T')[0];
    $('#dateFrom').val(from);
    $('#dateTo').val(today);
    loadData();
    $('#applyFilter').on('click', loadData);

    $('#downloadCustomersCsv').on('click', function (event) {
        event.preventDefault();

        const from = $('#dateFrom').val();
        const to = $('#dateTo').val();
        const url = new URL('{{ route('admin.reports.customers.export') }}', window.location.origin);

        if (from) url.searchParams.set('from', from);
        if (to) url.searchParams.set('to', to);

        window.location.href = url.toString();
    });
});
</script>
@endpush
