@php
    use App\Enums\Order\OrderItemStatusEnum;
    use App\Enums\Payment\PaymentTypeEnum;
@endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['orders']['active'] ?? ""])
@section('title', __('labels.order_details'))

@section('header_data')
    @php
        $page_title = __('labels.order_details');
        $page_pretitle = __('labels.admin') . " " . __('labels.order_details');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.orders'), 'url' => route('admin.orders.index')],
        ['title' => __('labels.order_details'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <!-- BEGIN PAGE HEADER -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <h2 class="page-title">{{ __('labels.order_details') }}</h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            @if(!empty($order['id']))
                            <a href="{{ route('admin.orders.invoice.download', $order['id']) }}"
                               class="btn btn-success d-none d-sm-inline-block" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="icon icon-tabler icon-tabler-file-download">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    <path d="M12 17v-6"/>
                                    <path d="M9.5 14.5l2.5 2.5l2.5 -2.5"/>
                                </svg>
                                Download GST Invoice
                            </a>
                            @else
                            <button class="btn btn-success d-none d-sm-inline-block" type="button" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="icon icon-tabler icon-tabler-file-download">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    <path d="M12 17v-6"/>
                                    <path d="M9.5 14.5l2.5 2.5l2.5 -2.5"/>
                                </svg>
                                Download GST Invoice
                            </button>
                            @endif
                            <a href="{{ route('admin.orders.index') }}"
                               class="btn btn-secondary d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-left"
                                     width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                     fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M5 12l14 0"></path>
                                    <path d="M5 12l6 6"></path>
                                    <path d="M5 12l6 -6"></path>
                                </svg>
                                {{ __('labels.back_to_orders') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END PAGE HEADER -->

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-cards">
                    <!-- Order Summary Card -->
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.order_summary') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="datagrid">
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.order_number') }}</div>
                                        <div class="datagrid-content">{{ $order['order_number'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.order_date') }}</div>
                                        <div
                                            class="datagrid-content">{{ $order['created_at'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.status') }}</div>
                                        <div class="datagrid-content text-capitalize">
                                            <span class="badge {{ $order['status'] }}">
                                                {{ $currentOrderStatusLabel }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.final_total') }}</div>
                                        <div
                                            class="datagrid-content">{{ $systemSettings['currencySymbol'] . number_format($order['final_total'], 2) }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.payment_method') }}</div>
                                        <div
                                            class="datagrid-content text-uppercase">{{ $order['payment_method'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.payment_status') }}</div>
                                        <div class="datagrid-content text-capitalize">
                                            <span
                                                class="badge {{ $order['payment_status'] === 'paid' ? 'bg-green-lt' : 'bg-yellow-lt' }}">
                                            {{ $order['payment_status'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($canManageOrder)
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('labels.order_management') }}</h3>
                                </div>
                                <div class="card-body">
                                    @if(!$isCodOrder)
                                        <div class="alert alert-info">
                                            {{ __('messages.online_payment_status_managed_by_gateway') }}
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('admin.orders.manage', $order['id']) }}">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.status') }}</label>
                                            <select name="status" class="form-select text-capitalize">
                                                @foreach($orderStatusOptions as $statusValue => $statusLabel)
                                                    <option value="{{ $statusValue }}" {{ old('status', $order['status']) === $statusValue ? 'selected' : '' }}>
                                                        {{ $statusLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.payment_status') }}</label>
                                            <select name="payment_status" class="form-select text-capitalize" {{ $isCodOrder ? '' : 'disabled' }}>
                                                @foreach($paymentStatusOptions as $paymentStatusOption)
                                                    <option value="{{ $paymentStatusOption }}" {{ old('payment_status', $order['payment_status']) === $paymentStatusOption ? 'selected' : '' }}>
                                                        {{ Str::headline($paymentStatusOption) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(!$isCodOrder)
                                                <input type="hidden" name="payment_status" value="{{ $order['payment_status'] }}">
                                            @endif
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tracking Code</label>
                                            <textarea name="tracking_code" rows="3" class="form-control" placeholder="Add tracking code if available">{{ old('tracking_code', $order['tracking_code'] ?? '') }}</textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.admin_note') }}</label>
                                            <textarea name="admin_note" rows="4" class="form-control" placeholder="{{ __('labels.admin_note_placeholder') }}">{{ old('admin_note', $order['admin_note'] ?? '') }}</textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">{{ __('labels.save_changes') }}</button>
                                    </form>
                                </div>
                            </div>
                        @endif

                        @if($managementHistories->isNotEmpty())
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h3 class="card-title">Order Management History</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="timeline px-3 py-2">
                                        @foreach($managementHistories as $history)
                                            <div class="timeline-event">
                                                <div class="timeline-event-icon bg-primary-lt text-primary">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                </div>
                                                <div class="timeline-event-card card">
                                                    <div class="card-body py-2 px-3">
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <small class="text-muted">
                                                                {{ $history->created_at->format('d M Y, h:i A') }}
                                                                @if($history->adminUser)
                                                                    &mdash; by <strong>{{ $history->adminUser->name }}</strong>
                                                                @endif
                                                            </small>
                                                        </div>
                                                        <div class="d-flex flex-wrap gap-2 mt-1">
                                                            @if(in_array('status', $history->changed_fields ?? []))
                                                                <span class="badge bg-blue-lt">
                                                                    Status: <span class="text-capitalize">{{ Str::replace('_', ' ', $history->previous_status) }}</span>
                                                                    &rarr; <span class="text-capitalize">{{ Str::replace('_', ' ', $history->new_status) }}</span>
                                                                </span>
                                                            @endif
                                                            @if(in_array('payment_status', $history->changed_fields ?? []))
                                                                <span class="badge bg-green-lt">
                                                                    Payment: <span class="text-capitalize">{{ Str::replace('_', ' ', $history->previous_payment_status) }}</span>
                                                                    &rarr; <span class="text-capitalize">{{ Str::replace('_', ' ', $history->new_payment_status) }}</span>
                                                                </span>
                                                            @endif
                                                            @if(in_array('tracking_code', $history->changed_fields ?? []))
                                                                <span class="badge bg-cyan-lt">
                                                                    Tracking updated
                                                                    @if($history->tracking_code)
                                                                        : {{ $history->tracking_code }}
                                                                    @endif
                                                                </span>
                                                            @endif
                                                            @if(in_array('admin_note', $history->changed_fields ?? []))
                                                                <span class="badge bg-yellow-lt">
                                                                    Note updated
                                                                    @if($history->admin_note)
                                                                        : {{ Str::limit($history->admin_note, 60) }}
                                                                    @endif
                                                                </span>
                                                            @endif
                                                            @if(empty($history->changed_fields))
                                                                <span class="badge bg-muted-lt">No field changes</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(!empty($order['order_note']))
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('labels.customer_information') }}</h3>
                                </div>
                                <div class="card-body">
                                    <div class="datagrid">
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.order_note') }}</div>
                                            <div
                                                class="datagrid-content text-capitalize">
                                                <textarea class="form-control" rows="3" readonly
                                                          disabled>{{ $order['order_note'] }}</textarea></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Customer Information Card -->
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.customer_information') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="datagrid">
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.customer_name') }}</div>
                                        <div
                                            class="datagrid-content text-capitalize">{{ $order['billing_name'] }}</div>
                                    </div>
                                    @if(!empty($order['customer_company']))
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.company') }}</div>
                                            <div class="datagrid-content text-capitalize">{{ $order['customer_company'] }}</div>
                                        </div>
                                    @endif
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.email') }}</div>
                                        <div class="datagrid-content">{{ $order['email'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.phone') }}</div>
                                        <div class="datagrid-content">{{ $order['billing_phone'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">GSTIN</div>
                                        <div class="datagrid-content">{{ $order['customer_gstin'] ?: 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address Card -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.shipping_address') }}</h3>
                                <div class="card-actions">
                                    <a href="{{ route('admin.orders.shipping-address.download', $order['id']) }}" class="btn btn-outline-primary btn-sm">
                                        Download Printable Address
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <address>
                                    {{ $order['shipping_name'] }}<br>
                                    {{ $order['shipping_address_1'] }}<br>
                                    @if($order['shipping_address_2'])
                                        {{ $order['shipping_address_2'] }}<br>
                                    @endif
                                    @if($order['shipping_landmark'])
                                        {{ $order['shipping_landmark'] }}<br>
                                    @endif
                                    {{ $order['shipping_city'] }}
                                    , {{ $order['shipping_state'] }} {{ $order['shipping_zip'] }}<br>
                                    {{ $order['shipping_country'] }}<br>
                                    {{ $order['shipping_phone'] }}
                                </address>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.payment_activity') }}</h3>
                            </div>
                            <div class="card-body">
                                @if(!empty($order['payment_transactions']))
                                    @php $latestTransaction = $order['payment_transactions'][0]; @endphp
                                    <div class="datagrid mb-3">
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.transaction_id') }}</div>
                                            <div class="datagrid-content">{{ $latestTransaction['display_transaction_id'] ?? $latestTransaction['transaction_id'] }}</div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.payment_method') }}</div>
                                            <div class="datagrid-content text-capitalize">{{ Str::headline($latestTransaction['payment_method']) }}</div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.payment_status') }}</div>
                                            <div class="datagrid-content text-capitalize">{{ Str::replace('_', ' ', $latestTransaction['payment_status']) }}</div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.updated_at') }}</div>
                                            <div class="datagrid-content">{{ $latestTransaction['updated_at'] }}</div>
                                        </div>
                                        @if(!empty($latestTransaction['gateway_event']))
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">{{ __('labels.gateway_event') }}</div>
                                                <div class="datagrid-content">{{ $latestTransaction['gateway_event'] }}</div>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="datagrid-title">{{ __('labels.latest_gateway_message') }}</div>
                                        <div class="datagrid-content mt-1">
                                            <textarea class="form-control" rows="3" readonly disabled>{{ $latestTransaction['message'] ?? __('labels.no_payment_message_available') }}</textarea>
                                        </div>
                                    </div>
                                    @if(!empty($latestTransaction['failure_description']) || !empty($latestTransaction['failure_reason']) || !empty($latestTransaction['failure_code']) || !empty($latestTransaction['failure_source']) || !empty($latestTransaction['failure_step']))
                                        <div class="mt-3">
                                            <div class="datagrid-title">{{ __('labels.gateway_failure_details') }}</div>
                                            <div class="datagrid mt-2">
                                                @if(!empty($latestTransaction['failure_description']))
                                                    <div class="datagrid-item">
                                                        <div class="datagrid-title">{{ __('labels.failure_description') }}</div>
                                                        <div class="datagrid-content">{{ $latestTransaction['failure_description'] }}</div>
                                                    </div>
                                                @endif
                                                @if(!empty($latestTransaction['failure_reason']))
                                                    <div class="datagrid-item">
                                                        <div class="datagrid-title">{{ __('labels.failure_reason') }}</div>
                                                        <div class="datagrid-content">{{ $latestTransaction['failure_reason'] }}</div>
                                                    </div>
                                                @endif
                                                @if(!empty($latestTransaction['failure_code']))
                                                    <div class="datagrid-item">
                                                        <div class="datagrid-title">{{ __('labels.failure_code') }}</div>
                                                        <div class="datagrid-content">{{ $latestTransaction['failure_code'] }}</div>
                                                    </div>
                                                @endif
                                                @if(!empty($latestTransaction['failure_source']))
                                                    <div class="datagrid-item">
                                                        <div class="datagrid-title">{{ __('labels.failure_source') }}</div>
                                                        <div class="datagrid-content">{{ $latestTransaction['failure_source'] }}</div>
                                                    </div>
                                                @endif
                                                @if(!empty($latestTransaction['failure_step']))
                                                    <div class="datagrid-item">
                                                        <div class="datagrid-title">{{ __('labels.failure_step') }}</div>
                                                        <div class="datagrid-content">{{ $latestTransaction['failure_step'] }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    @if(!empty($latestTransaction['latest_settlement']))
                                        <div class="mt-3">
                                            <div class="datagrid-title">{{ __('labels.latest_settlement') }}</div>
                                            <div class="datagrid mt-2">
                                                <div class="datagrid-item">
                                                    <div class="datagrid-title">{{ __('labels.settlement_status') }}</div>
                                                    <div class="datagrid-content text-capitalize">{{ Str::headline($latestTransaction['latest_settlement']['status']) }}</div>
                                                </div>
                                                @if(!empty($latestTransaction['latest_settlement']['event_name']))
                                                    <div class="datagrid-item">
                                                        <div class="datagrid-title">{{ __('labels.gateway_event') }}</div>
                                                        <div class="datagrid-content">{{ $latestTransaction['latest_settlement']['event_name'] }}</div>
                                                    </div>
                                                @endif
                                                @if(!empty($latestTransaction['latest_settlement']['settlement_reference']))
                                                    <div class="datagrid-item">
                                                        <div class="datagrid-title">{{ __('labels.settlement_reference') }}</div>
                                                        <div class="datagrid-content">{{ $latestTransaction['latest_settlement']['settlement_reference'] }}</div>
                                                    </div>
                                                @endif
                                                @if(!empty($latestTransaction['latest_settlement']['utr']))
                                                    <div class="datagrid-item">
                                                        <div class="datagrid-title">{{ __('labels.settlement_utr') }}</div>
                                                        <div class="datagrid-content">{{ $latestTransaction['latest_settlement']['utr'] }}</div>
                                                    </div>
                                                @endif
                                                @if(!empty($latestTransaction['latest_settlement']['settled_at']))
                                                    <div class="datagrid-item">
                                                        <div class="datagrid-title">{{ __('labels.settled_at') }}</div>
                                                        <div class="datagrid-content">{{ $latestTransaction['latest_settlement']['settled_at'] }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-info mb-0">
                                        @if(strtolower((string) $order['payment_method']) === PaymentTypeEnum::COD())
                                            {{ __('labels.cod_payment_waiting_for_admin_update') }}
                                        @else
                                            {{ __('labels.online_payment_waiting_for_gateway_update') }}
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                    <!-- Order Items Card -->
                    <div class="col-12 mt-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.order_items') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                        <tr>
                                            {{--                                            <th width="30">--}}
                                            {{--                                                <input type="checkbox" class="form-check-input" id="select-all-items">--}}
                                            {{--                                            </th>--}}
                                            <th>{{ __('labels.store_name') }}</th>
                                            <th>{{ __('labels.product') }}</th>
                                            <th>{{ __('labels.variant') }}</th>
                                            <th>{{ __('labels.price') }}</th>
                                            <th>{{ __('labels.status') }}</th>
                                            <th>{{ __('labels.quantity') }}</th>
                                            <th>{{ __('labels.subtotal') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($order['items'] as $item)
                                            <tr>
                                                {{--                                                <td>--}}
                                                {{--                                                    <input type="checkbox" class="form-check-input item-checkbox"--}}
                                                {{--                                                           name="item_ids[]" value="{{ $item['orderItem']['id'] }}">--}}
                                                {{--                                                </td>--}}
                                                <td>{{ $item['store']['name'] ?? 'N/A' }}</td>
                                                <td>{{ $item['product']['title'] ?? 'N/A' }}
                                                    @if(!empty($item['attachments']))
                                                        <br class="mt-7">
                                                        <span class="fw-medium">Attachments</span>
                                                        <ul class="list-unstyled mb-0">
                                                            @foreach($item['attachments'] as $attachment)
                                                                <li><a href="{{ $attachment }}"
                                                                       target="_blank"
                                                                       data-fslightbox="gallery">view</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                                <td>{{ $item['variant']['title'] ?? 'N/A' }}</td>
                                                <td>{{$systemSettings['currencySymbol'] . number_format($item['price'], 2) }}</td>
                                                <td><span class="badge {{ $item['orderItem']['status'] }}">
                                                {{ $item['orderItem']['status_formatted'] }}
                                                </span></td>
                                                <td>{{ $item['quantity'] }}</td>
                                                <td>{{ $systemSettings['currencySymbol'] . number_format($item['subtotal'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Total Quantity:</strong>
                                            </td>
                                            <td><strong>{{ collect($order['items'])->sum('quantity')  }}</strong></td>
                                            <td></td>
                                        </tr>

                                        <tr>
                                            <td colspan="6" class="text-end"><b>{{ __('labels.subtotal') }}:</b></td>
                                            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['subtotal'], 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><b>Shipping Cost:</b></td>
                                            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['delivery_charge'], 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><b>GST:</b></td>
                                            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['total_gst'] ?? 0, 2) }}</td>
                                        </tr>
                                        @if(($order['handling_charges'] ?? 0) > 0)
                                            <tr>
                                                <td colspan="6" class="text-end"><b>{{ __('labels.handling_charges') }}:</b>
                                                </td>
                                                <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['handling_charges'], 2) }}</td>
                                            </tr>
                                        @endif
                                        @if(($order['per_store_drop_off_fee'] ?? 0) > 0)
                                            <tr>
                                                <td colspan="6" class="text-end">
                                                    <b>{{ __('labels.per_store_drop_off_fee') }}:</b></td>
                                                <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['per_store_drop_off_fee'], 2) }}</td>
                                            </tr>
                                        @endif
                                        @if($order['wallet_balance'] > 0)
                                            <tr>
                                                <td colspan="6" class="text-end"><b>{{ __('labels.wallet_used') }}:</b>
                                                </td>
                                                <td>
                                                    - {{ $systemSettings['currencySymbol'] }}{{ $order['wallet_balance'] }}</td>
                                            </tr>
                                        @endif
                                        @if($order['promo_discount'] > 0)
                                            <tr>
                                                <td colspan="6" class="text-end">
                                                    <b>
                                                        {{ __('labels.promo_discount') }}
                                                        @if(!empty($order['promo_line']) && $order['promo_line']['cashback_flag'])
                                                            ({{ __('labels.cashback') }})

                                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                                  title="{{ __('messages.cashback_info_message') }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="18"
                                                                     height="18"
                                                                     viewBox="0 0 24 24" fill="none"
                                                                     stroke="currentColor"
                                                                     stroke-width="2" stroke-linecap="round"
                                                                     stroke-linejoin="round"
                                                                     class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                    <path
                                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/>
                                                                    <path d="M12 16v.01"/>
                                                                    <path
                                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/>
                                                                </svg>
                                                            </span>
                                                        @endif
                                                        <span
                                                            class="text-uppercase">({{ $order['promo_code'] }}):</span>
                                                    </b>
                                                </td>
                                                <td>
                                                    - {{ $systemSettings['currencySymbol'] }}{{ $order['promo_discount'] }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td colspan="6" class="text-end"><b>{{ __('labels.total') }}:</b>
                                            </td>
                                            <td>
                                                <b>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['final_total'] ?? $order['total_payable'], 2) }}</b>
                                            </td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Status Card -->
                    {{--                    <div class="col-12 mt-3">--}}
                    {{--                        <div class="card">--}}
                    {{--                            <div class="card-header">--}}
                    {{--                                <h3 class="card-title">{{ __('labels.update_status') }}</h3>--}}
                    {{--                            </div>--}}
                    {{--                            <div class="card-body">--}}
                    {{--                                <div class="alert alert-info mb-3">--}}
                    {{--                                    <p class="mb-0">{{ __('labels.select_items_to_update_status') ?? 'Select one or more items from the table above to update their status.' }}</p>--}}
                    {{--                                </div>--}}
                    {{--                                <div id="status-update-results" class="mb-3"></div>--}}
                    {{--                                <form id="update-status-form" method="POST">--}}
                    {{--                                    @csrf--}}
                    {{--                                    <div class="mb-3">--}}
                    {{--                                        <label class="form-label">{{ __('labels.status') }}</label>--}}
                    {{--                                        <select name="status" class="form-select text-capitalize" id="item-status">--}}
                    {{--                                            <option--}}
                    {{--                                                    value="accept">Accept--}}
                    {{--                                            </option>--}}
                    {{--                                            <option--}}
                    {{--                                                    value="reject">Reject--}}
                    {{--                                            </option>--}}
                    {{--                                            <option--}}
                    {{--                                                    value="preparing">Preparing--}}
                    {{--                                            </option>--}}
                    {{--                                        </select>--}}
                    {{--                                    </div>--}}
                    {{--                                    <div class="mb-3">--}}
                    {{--                                        <button type="submit" class="btn btn-primary" id="update-items-status">--}}
                    {{--                                            {{ __('labels.update_status') }}--}}
                    {{--                                        </button>--}}
                    {{--                                    </div>--}}
                    {{--                                </form>--}}
                    {{--                            </div>--}}
                    {{--                        </div>--}}
                    {{--                    </div>--}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/order.js') }}"></script>
@endpush
