@php use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['reports']['active'] ?? 'reports', 'sub_page' => $menuAdmin['reports']['route']['payment_monitor']['sub_active'] ?? 'payment_monitor'])

@section('title', __('labels.payment_monitor'))

@section('header_data')
    @php
        $page_title = __('labels.payment_monitor');
        $page_pretitle = 'Reports';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Reports', 'url' => route('admin.reports.payments')],
        ['title' => __('labels.payment_monitor'), 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-body">
    <div class="container-xl">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label">{{ __('labels.payment_method') }}</label>
                        <select class="form-select text-capitalize" id="paymentMethodFilter">
                            <option value="">{{ __('labels.payment_method') }}</option>
                            @foreach($paymentMethodOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label">{{ __('labels.status') }}</label>
                        <select class="form-select text-capitalize" id="paymentStatusFilter">
                            <option value="">{{ __('labels.status') }}</option>
                            @foreach($paymentStatusOptions as $value)
                                <option value="{{ $value }}">{{ Str::headline($value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label">{{ __('labels.date_range') }}</label>
                        <select class="form-select" id="paymentDateRangeFilter">
                            <option value="">{{ __('labels.date_range') }}</option>
                            @foreach($dateRangeOptions as $value)
                                <option value="{{ $value }}">{{ Str::headline($value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" id="paymentDateFromFilter">
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" id="paymentDateToFilter">
                    </div>
                    <div class="col-md-3 col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary w-100" id="applyPaymentFilters">Apply</button>
                        <button class="btn btn-outline-secondary" id="resetPaymentFilters">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">{{ __('labels.payment_transactions') }}</div>
                        <div class="h1 mt-1" data-summary-value="transactions">{{ number_format($summary['transactions'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">{{ __('labels.refunds') }}</div>
                        <div class="h1 mt-1" data-summary-value="refunds">{{ number_format($summary['refunds'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">{{ __('labels.disputes') }}</div>
                        <div class="h1 mt-1" data-summary-value="disputes">{{ number_format($summary['disputes'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">{{ __('labels.settlements') }}</div>
                        <div class="h1 mt-1" data-summary-value="settlements">{{ number_format($summary['settlements'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">{{ __('labels.webhook_logs') }}</div>
                        <div class="h1 mt-1" data-summary-value="webhook_logs">{{ number_format($summary['webhook_logs'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">{{ __('labels.payment_monitor') }}</h3>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                    <li class="nav-item" role="presentation"><a href="#tab-transactions" class="nav-link active" data-bs-toggle="tab" aria-selected="true" role="tab">{{ __('labels.payment_transactions') }}</a></li>
                    <li class="nav-item" role="presentation"><a href="#tab-refunds" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab">{{ __('labels.refunds') }}</a></li>
                    <li class="nav-item" role="presentation"><a href="#tab-disputes" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab">{{ __('labels.disputes') }}</a></li>
                    <li class="nav-item" role="presentation"><a href="#tab-settlements" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab">{{ __('labels.settlements') }}</a></li>
                    <li class="nav-item" role="presentation"><a href="#tab-webhook-logs" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab">{{ __('labels.webhook_logs') }}</a></li>
                </ul>
                <div class="tab-content mt-3">
                    <div class="tab-pane active show" id="tab-transactions" role="tabpanel">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-outline-primary export-payment-table" data-export-route="{{ route('admin.reports.payments.transactions.export') }}">{{ __('labels.export_csv') }}</button>
                        </div>
                        <x-datatable id="payment-transactions-table" :columns="$transactionColumns" route="{{ route('admin.reports.payments.transactions.datatable') }}" :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                    </div>
                    <div class="tab-pane" id="tab-refunds" role="tabpanel">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-outline-primary export-payment-table" data-export-route="{{ route('admin.reports.payments.refunds.export') }}">{{ __('labels.export_csv') }}</button>
                        </div>
                        <x-datatable id="payment-refunds-table" :columns="$refundColumns" route="{{ route('admin.reports.payments.refunds.datatable') }}" :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                    </div>
                    <div class="tab-pane" id="tab-disputes" role="tabpanel">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-outline-primary export-payment-table" data-export-route="{{ route('admin.reports.payments.disputes.export') }}">{{ __('labels.export_csv') }}</button>
                        </div>
                        <x-datatable id="payment-disputes-table" :columns="$disputeColumns" route="{{ route('admin.reports.payments.disputes.datatable') }}" :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                    </div>
                    <div class="tab-pane" id="tab-settlements" role="tabpanel">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-outline-primary export-payment-table" data-export-route="{{ route('admin.reports.payments.settlements.export') }}">{{ __('labels.export_csv') }}</button>
                        </div>
                        <x-datatable id="payment-settlements-table" :columns="$settlementColumns" route="{{ route('admin.reports.payments.settlements.datatable') }}" :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                    </div>
                    <div class="tab-pane" id="tab-webhook-logs" role="tabpanel">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-outline-primary export-payment-table" data-export-route="{{ route('admin.reports.payments.webhook-logs.export') }}">{{ __('labels.export_csv') }}</button>
                        </div>
                        <x-datatable id="payment-webhook-logs-table" :columns="$webhookLogColumns" route="{{ route('admin.reports.payments.webhook-logs.datatable') }}" :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const tableSelectors = [
            '#payment-transactions-table',
            '#payment-refunds-table',
            '#payment-disputes-table',
            '#payment-settlements-table',
            '#payment-webhook-logs-table',
        ];
        const summaryEndpoint = '{{ route('admin.reports.payments.summary') }}';

        function currentFilterParams() {
            return {
                payment_method: $('#paymentMethodFilter').val(),
                payment_status: $('#paymentStatusFilter').val(),
                date_range: $('#paymentDateRangeFilter').val(),
                from_date: $('#paymentDateFromFilter').val(),
                to_date: $('#paymentDateToFilter').val(),
            };
        }

        function reloadAllPaymentTables(resetPaging = false) {
            tableSelectors.forEach((selector) => {
                const table = $(selector);
                if (table.length && $.fn.DataTable.isDataTable(selector)) {
                    table.DataTable().ajax.reload(null, resetPaging);
                }
            });
        }

        function refreshSummaryCards() {
            $.get(summaryEndpoint, currentFilterParams(), function (response) {
                Object.entries(response || {}).forEach(([key, value]) => {
                    const target = $('[data-summary-value="' + key + '"]');
                    if (target.length) {
                        target.text(Number(value || 0).toLocaleString());
                    }
                });
            });
        }

        function exportWithFilters(route) {
            const url = new URL(route, window.location.origin);
            Object.entries(currentFilterParams()).forEach(([key, value]) => {
                if (value) {
                    url.searchParams.set(key, value);
                }
            });
            window.location.href = url.toString();
        }

        function attachFilterParams(selector) {
            $(selector).on('preXhr.dt', function (e, settings, data) {
                Object.assign(data, currentFilterParams());
            });
        }

        tableSelectors.forEach(attachFilterParams);

        $('#applyPaymentFilters').on('click', function () {
            reloadAllPaymentTables(true);
            refreshSummaryCards();
        });

        $('#resetPaymentFilters').on('click', function () {
            $('#paymentMethodFilter').val('');
            $('#paymentStatusFilter').val('');
            $('#paymentDateRangeFilter').val('');
            $('#paymentDateFromFilter').val('');
            $('#paymentDateToFilter').val('');
            reloadAllPaymentTables(true);
            refreshSummaryCards();
        });

        $('.export-payment-table').on('click', function () {
            exportWithFilters($(this).data('export-route'));
        });
    })();
</script>
@endpush