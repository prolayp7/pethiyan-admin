@extends('layouts.admin.app', ['page' => 'reports'])

@section('title', 'Products Report')

@section('header_data')
    @php
        $page_title   = 'Products Report';
        $page_pretitle = 'Reports';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Reports', 'url' => route('admin.reports.sales')],
        ['title' => 'Products', 'url' => ''],
    ];
@endphp

@section('admin-content')
<div class="page-body">
    <div class="container-xl">
        @include('admin.reports._filter')

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Top Selling Products</h4>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" class="btn btn-primary btn-sm" id="downloadProductsCsv">Download CSV</a>
                    <select class="form-select" id="limitSelect" style="width:100px">
                        <option value="10">Top 10</option>
                        <option value="15" selected>Top 15</option>
                        <option value="25">Top 25</option>
                        <option value="50">Top 50</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Qty Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <tr><td colspan="4" class="text-center text-muted py-4">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function fmt(n) {
    return '₹' + parseFloat(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
}

function loadData() {
    const from  = $('#dateFrom').val();
    const to    = $('#dateTo').val();
    const limit = $('#limitSelect').val();
    $.get('{{ route('admin.reports.products.data') }}', { from, to, limit }, function (res) {
        let html = '';
        res.products.forEach((p, i) => {
            const title = p.product?.title ?? '—';
            html += `<tr>
                <td>${i + 1}</td>
                <td>${title}</td>
                <td>${p.qty_sold}</td>
                <td>${fmt(p.revenue)}</td>
            </tr>`;
        });
        $('#productsTableBody').html(html || '<tr><td colspan="4" class="text-center text-muted">No data</td></tr>');
    });
}

$(function () {
    const today = new Date().toISOString().split('T')[0];
    const from  = new Date(Date.now() - 29 * 86400000).toISOString().split('T')[0];
    $('#dateFrom').val(from);
    $('#dateTo').val(today);
    loadData();
    $('#applyFilter').on('click', loadData);
    $('#limitSelect').on('change', loadData);

    $('#downloadProductsCsv').on('click', function (event) {
        event.preventDefault();

        const from = $('#dateFrom').val();
        const to = $('#dateTo').val();
        const limit = $('#limitSelect').val();
        const url = new URL('{{ route('admin.reports.products.export') }}', window.location.origin);

        if (from) url.searchParams.set('from', from);
        if (to) url.searchParams.set('to', to);
        if (limit) url.searchParams.set('limit', limit);

        window.location.href = url.toString();
    });
});
</script>
@endpush
