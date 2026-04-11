@extends('layouts.admin.app',['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['gst']['sub_active'] ?? "" ])

@section('title', __('labels.gst_settings'))

@section('header_data')
    @php
        $page_title = __('labels.gst_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.gst_settings'), 'url' => null],
    ];

    use App\Services\GstService;
    $stateCodes = GstService::STATE_CODES; // ['CODE' => 'State Name']
    $gstSlabs   = [0, 5, 12, 18, 28];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.gst_settings') }}</h2>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-5">
                {{-- Side navigation --}}
                <div class="col-sm-2 d-none d-lg-block">
                    <div class="sticky-top">
                        <h3>{{ __('labels.menu') }}</h3>
                        <nav class="nav nav-vertical nav-pills" id="pills">
                            <a class="nav-link" href="#pills-general">General</a>
                            <a class="nav-link" href="#pills-seller">Seller Details</a>
                            <a class="nav-link" href="#pills-invoice">Invoice</a>
                            <a class="nav-link" href="#pills-composition">Composition</a>
                        </nav>
                    </div>
                </div>

                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="gst">

                                {{-- ────────────────── General ────────────────── --}}
                                <div class="card mb-4" id="pills-general">
                                    <div class="card-header">
                                        <h4 class="card-title">General</h4>
                                    </div>
                                    <div class="card-body">

                                        {{-- Enable GST --}}
                                        <div class="mb-3">
                                            <label class="form-label">Enable GST</label>
                                            <div>
                                                <label class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="gst_enabled"
                                                           id="gstEnabledToggle"
                                                           value="1" {{ !empty($settings['gst_enabled']) ? 'checked' : '' }}>
                                                    <span class="form-check-label">Apply GST on all applicable orders</span>
                                                </label>
                                            </div>
                                        </div>

                                        {{-- Default GST slab --}}
                                        <div class="mb-3">
                                            <label class="form-label" for="default_gst_rate">Default GST Rate for New Products</label>
                                            <select class="form-select" name="default_gst_rate" id="default_gst_rate">
                                                @foreach ($gstSlabs as $slab)
                                                    <option value="{{ $slab }}"
                                                        {{ (int)($settings['default_gst_rate'] ?? 18) === $slab ? 'selected' : '' }}>
                                                        {{ $slab }}%
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-hint">This rate is pre-filled when adding a new product. Overridable per product.</small>
                                        </div>

                                        {{-- Collect customer GSTIN --}}
                                        <div class="mb-3">
                                            <label class="form-label">Collect Customer GSTIN (B2B)</label>
                                            <div>
                                                <label class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="collect_customer_gstin"
                                                           value="1" {{ !empty($settings['collect_customer_gstin']) ? 'checked' : '' }}>
                                                    <span class="form-check-label">Show GSTIN field at checkout for business customers</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ────────────────── Seller Details ────────────────── --}}
                                <div class="card mb-4" id="pills-seller">
                                    <div class="card-header">
                                        <h4 class="card-title">Seller / Platform Details</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            {{-- GSTIN --}}
                                            <div class="col-md-6">
                                                <label class="form-label" for="seller_gstin">GSTIN</label>
                                                <input type="text" class="form-control" name="seller_gstin" id="seller_gstin"
                                                       maxlength="15" placeholder="22AAAAA0000A1Z5"
                                                       value="{{ $settings['seller_gstin'] ?? '' }}">
                                                <small class="form-hint">15-character GST Identification Number</small>
                                            </div>

                                            {{-- Registration State --}}
                                            <div class="col-md-6">
                                                <label class="form-label" for="seller_state_code">Registration State</label>
                                                <select class="form-select" name="seller_state_code" id="seller_state_code">
                                                    @foreach ($stateCodes as $code => $name)
                                                        <option value="{{ $code }}"
                                                            {{ ($settings['seller_state_code'] ?? 'MH') === $code ? 'selected' : '' }}>
                                                            {{ $code }} — {{ $name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="form-hint">Used to determine CGST/SGST vs IGST for each order</small>
                                            </div>

                                            {{-- Legal / Trade Name --}}
                                            <div class="col-md-6">
                                                <label class="form-label" for="seller_legal_name">Legal / Trade Name</label>
                                                <input type="text" class="form-control" name="seller_legal_name" id="seller_legal_name"
                                                       maxlength="200"
                                                       value="{{ $settings['seller_legal_name'] ?? '' }}"
                                                       placeholder="ABC Enterprises Pvt. Ltd.">
                                                <small class="form-hint">Printed on tax invoices</small>
                                            </div>

                                            {{-- Address --}}
                                            <div class="col-12">
                                                <label class="form-label" for="seller_address">Registered Address</label>
                                                <textarea class="form-control" name="seller_address" id="seller_address"
                                                          rows="3" maxlength="500"
                                                          placeholder="123, Business Park, City, State — 400001">{{ $settings['seller_address'] ?? '' }}</textarea>
                                                <small class="form-hint">Full address printed on invoices</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ────────────────── Invoice ────────────────── --}}
                                <div class="card mb-4" id="pills-invoice">
                                    <div class="card-header">
                                        <h4 class="card-title">Invoice Settings</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">

                                            {{-- Show GST breakdown --}}
                                            <div class="col-12">
                                                <label class="form-label">Show GST Breakdown on Invoice</label>
                                                <div>
                                                    <label class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="show_gst_breakdown"
                                                               value="1" {{ !empty($settings['show_gst_breakdown']) ? 'checked' : '' }}>
                                                        <span class="form-check-label">Display CGST / SGST / IGST line items separately</span>
                                                    </label>
                                                </div>
                                            </div>

                                            {{-- Show HSN --}}
                                            <div class="col-12">
                                                <label class="form-label">Show HSN Code on Invoice</label>
                                                <div>
                                                    <label class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="show_hsn_on_invoice"
                                                               value="1" {{ !empty($settings['show_hsn_on_invoice']) ? 'checked' : '' }}>
                                                        <span class="form-check-label">Print HSN / SAC code for each line item</span>
                                                    </label>
                                                </div>
                                            </div>

                                            {{-- Invoice prefix --}}
                                            <div class="col-md-4">
                                                <label class="form-label" for="invoice_prefix">Invoice Prefix</label>
                                                <input type="text" class="form-control" name="invoice_prefix" id="invoice_prefix"
                                                       maxlength="10" placeholder="INV"
                                                       value="{{ $settings['invoice_prefix'] ?? 'INV' }}">
                                                <small class="form-hint">E.g. "INV" → INV-1001</small>
                                            </div>

                                            {{-- Invoice starting number --}}
                                            <div class="col-md-4">
                                                <label class="form-label" for="invoice_starting_number">Starting Number</label>
                                                <input type="number" class="form-control" name="invoice_starting_number"
                                                       id="invoice_starting_number" min="1"
                                                       value="{{ $settings['invoice_starting_number'] ?? 1001 }}">
                                                <small class="form-hint">First invoice serial number</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ────────────────── Composition Scheme ────────────────── --}}
                                <div class="card mb-4" id="pills-composition">
                                    <div class="card-header">
                                        <h4 class="card-title">Composition Scheme</h4>
                                    </div>
                                    <div class="card-body">

                                        <div class="mb-3">
                                            <label class="form-label">Under Composition Scheme</label>
                                            <div>
                                                <label class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="composition_scheme"
                                                           id="compositionToggle"
                                                           value="1" {{ !empty($settings['composition_scheme']) ? 'checked' : '' }}>
                                                    <span class="form-check-label">Seller is registered under GST Composition Scheme</span>
                                                </label>
                                            </div>
                                            <small class="form-hint text-warning">
                                                Composition dealers cannot collect GST from customers — they pay tax at a flat rate on turnover.
                                                Enabling this will override the standard CGST/SGST/IGST calculation.
                                            </small>
                                        </div>

                                        <div id="compositionFields" class="{{ empty($settings['composition_scheme']) ? 'd-none' : '' }}">
                                            <div class="col-md-4">
                                                <label class="form-label" for="composition_rate">Composition Rate (%)</label>
                                                <input type="number" class="form-control" name="composition_rate"
                                                       id="composition_rate" step="0.5" min="0" max="5"
                                                       value="{{ $settings['composition_rate'] ?? 1.0 }}">
                                                <small class="form-hint">Typical rates: 1% (traders/manufacturers), 5% (restaurants)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Submit --}}
                                <div class="card">
                                    <div class="card-footer text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="24" height="24"
                                                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"/>
                                                <circle cx="12" cy="14" r="2"/>
                                                <polyline points="14 4 14 8 8 8 8 4"/>
                                            </svg>
                                            {{ __('labels.save_settings') }}
                                        </button>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const compositionToggle = document.getElementById('compositionToggle');
    const compositionFields = document.getElementById('compositionFields');

    function toggleCompositionFields() {
        if (compositionToggle.checked) {
            compositionFields.classList.remove('d-none');
        } else {
            compositionFields.classList.add('d-none');
        }
    }

    compositionToggle.addEventListener('change', toggleCompositionFields);
</script>
@endpush
