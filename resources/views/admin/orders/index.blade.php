@php use App\Enums\DateRangeFilterEnum;use App\Enums\Order\OrderStatusEnum;use App\Enums\Payment\PaymentTypeEnum;use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['orders']['active'] ?? ""])

@section('title', $pageTitle ?? __('labels.orders'))

@section('header_data')
    @php
        $page_title = $pageTitle ?? __('labels.orders');
        $page_pretitle = __('labels.admin') . " " . __('labels.orders');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.orders'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ $pageTitle ?? __('labels.orders') }} <span class="order-count"></span></h3>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <select class="form-select text-capitalize" id="paymentFilter">
                                            <option value="">{{ __('labels.payment_type') }}</option>
                                            @foreach(PaymentTypeEnum::values() as $value)
                                                <option
                                                    value="{{$value}}">{{Str::replace("_", " ", $value)}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select text-capitalize" id="statusFilter" @isset($defaultStatus) disabled @endisset>
                                            <option value="">{{ __('labels.status') }}</option>
                                            @foreach(OrderStatusEnum::values() as $value)
                                                <option value="{{$value}}" @selected(isset($defaultStatus) && $defaultStatus === $value)>
                                                    {{Str::replace("_", " ", $value)}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="rangeFilter">
                                            <option value="">{{ __('labels.date_range') }}</option>
                                            @foreach(DateRangeFilterEnum::values() as $value)
                                                <option
                                                    value="{{$value}}">{{Str::replace("_", " ", $value)}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <div class="input-group" style="width:230px;">
                                            <input type="text" class="form-control" id="customDateRange"
                                                   placeholder="Custom range…" readonly style="cursor:pointer;">
                                            <button class="btn btn-outline-secondary" type="button" id="clearDateRange" title="Clear custom range" style="display:none;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <input type="text" class="form-control" id="promoFilter" placeholder="Promo code…" style="width:140px">
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-outline-primary" id="refresh">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                                <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                            </svg>
                                            {{ __('labels.refresh') }}
                                        </button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-success" id="exportOrders" data-export-url="{{ route('admin.orders.export') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-download">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                                                <path d="M7 11l5 5l5 -5"/>
                                                <path d="M12 4l0 12"/>
                                            </svg>
                                            Export CSV
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row w-full">
                                <x-datatable id="orders-table" :columns="$columns"
                                             route="{{ route('admin.orders.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,'noExport' => true]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- REJECT MODAL -->
    <div
        class="modal modal-blur fade"
        id="rejectModel"
        tabindex="-1"
        role="dialog"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <!-- Download SVG icon from http://tabler.io/icons/icon/alert-triangle -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="icon mb-2 text-danger icon-lg"
                    >
                        <path d="M12 9v4"/>
                        <path
                            d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"
                        />
                        <path d="M12 16h.01"/>
                    </svg>
                    <h3>Reject Order</h3>
                    <div class="text-secondary">
                        Are you sure you want to reject this order? This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="#" class="btn w-100" data-bs-dismiss="modal">Cancel</a>
                            </div>
                            <div class="col">
                                <a href="#" class="btn btn-danger w-100" id="confirmReject" data-bs-dismiss="modal">Reject
                                    Order</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END MODAL -->
    <!-- ACCEPT MODAL -->
    <div
        class="modal modal-blur fade"
        id="acceptModel"
        tabindex="-1"
        role="dialog"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-success"></div>
                <div class="modal-body text-center py-4">
                    <!-- Download SVG icon from http://tabler.io/icons/icon/circle-check -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="icon mb-2 text-green icon-lg"
                    >
                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                        <path d="M9 12l2 2l4 -4"/>
                    </svg>
                    <h3>Accept Order</h3>
                    <div class="text-secondary">
                        Are you sure you want to accept this order? You will be responsible for fulfilling it.
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="#" class="btn w-100" data-bs-dismiss="modal">Cancel</a>
                            </div>
                            <div class="col">
                                <a href="#" class="btn btn-success w-100" id="confirmAccept" data-bs-dismiss="modal">Accept
                                    Order</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END MODAL -->
    <!-- PREPARING MODAL -->
    <div
        class="modal modal-blur fade"
        id="preparingModel"
        tabindex="-1"
        role="dialog"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-primary"></div>
                <div class="modal-body text-center py-4">
                    <!-- Download SVG icon from http://tabler.io/icons/icon/package -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="icon mb-2 text-primary icon-lg"
                    >
                        <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/>
                        <path d="M12 12l8 -4.5"/>
                        <path d="M12 12l0 9"/>
                        <path d="M12 12l-8 -4.5"/>
                    </svg>
                    <h3>Mark as Preparing</h3>
                    <div class="text-secondary">
                        Are you sure you want to mark this order as preparing? This indicates you've started working on
                        the order.
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="#" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancel</a>
                            </div>
                            <div class="col">
                                <a href="#" class="btn btn-primary w-100" id="confirmPreparing" data-bs-dismiss="modal">Mark
                                    as Preparing</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END MODAL -->
@endsection

@push('styles')
    <link rel="stylesheet" href="{{hyperAsset('assets/vendor/litepicker/dist/css/litepicker.css')}}">
    <style>
        /* Force nav arrows visible — Tabler's button reset can hide them */
        .litepicker .month-item-header .button-previous-month,
        .litepicker .month-item-header .button-next-month {
            visibility: visible !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center;
            padding: 3px 6px;
            color: #9e9e9e;
        }
        .litepicker .month-item-header .button-previous-month:hover,
        .litepicker .month-item-header .button-next-month:hover {
            color: #2196f3 !important;
        }
        .litepicker .month-item-header .button-previous-month svg,
        .litepicker .month-item-header .button-next-month svg {
            fill: currentColor;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{hyperAsset('assets/vendor/litepicker/dist/litepicker.js')}}" defer></script>
    <script src="{{hyperAsset('assets/vendor/litepicker/dist/plugins/ranges.js')}}" defer></script>
    @isset($defaultStatus)
    <script>window.orderDefaultStatus = @json($defaultStatus);</script>
    @endisset
    <script src="{{hyperAsset('assets/js/order.js')}}" defer></script>
@endpush
