@extends('layouts.admin.app', ['page' => $menuAdmin['stores']['active'] ?? ""])

@section('title', empty($store) ? __('labels.add_store') : __('labels.edit_store'))

@section('header_data')
    @php
        $page_title = empty($store) ? __('labels.add_store') : __('labels.edit_store');
        $page_pretitle = __('labels.stores');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.stores'), 'url' => route('admin.sellers.store.index')],
        ['title' => empty($store) ? __('labels.add_store') : __('labels.edit_store'), 'url' => ''],
    ];
@endphp

@section('admin-content')
    <div class="row g-5">
        <div class="col-sm-2 d-none d-lg-block">
            <div class="sticky-top pt-3">
                <h3>{{ __('labels.menu') }}</h3>
                <nav class="nav nav-vertical nav-pills" id="pills">
                    <a class="nav-link" href="#pills-basic">{{ __('labels.basic_details') }}</a>
                    <a class="nav-link" href="#pills-location">{{ __('labels.location_details') }}</a>
                    <a class="nav-link" href="#pills-documents">{{ __('labels.business_documents') }}</a>
                </nav>
            </div>
        </div>
        <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
            <div class="card mb-3">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">
                            {{ empty($store) ? __('labels.add_store') : 'Update ' . $store->name }}
                        </h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                </div>
            </div>

            <div class="row row-cards">
                <div class="col-12">
                    <form
                        action="{{ empty($store) ? route('admin.sellers.store.store') : route('admin.sellers.store.update', $store->id) }}"
                        class="form-submit"
                        method="post"
                        enctype="multipart/form-data">
                        @csrf

                        {{-- Pethiyan seller is locked --}}
                        <input type="hidden" name="seller_id" value="1">

                        <!-- Basic Details -->
                        <div class="card mb-4" id="pills-basic">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('labels.basic_details') }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.store_name') }}</label>
                                    <input type="text" class="form-control" name="name"
                                           placeholder="{{ __('labels.enter_store_name') }}"
                                           value="{{ old('name', $store->name ?? '') }}"/>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.contact_email') }}</label>
                                            <input type="email" class="form-control" name="contact_email"
                                                   placeholder="{{ __('labels.enter_email_address') }}"
                                                   value="{{ old('contact_email', $store->contact_email ?? '') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.contact_number') }}</label>
                                            <input type="number" min="0" class="form-control" name="contact_number"
                                                   placeholder="{{ __('labels.enter_mobile_number') }}"
                                                   value="{{ old('contact_number', $store->contact_number ?? '') }}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location Details -->
                        <div class="card mb-4" id="pills-location">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('labels.location_details') }}</h4>
                            </div>
                            <div class="card-body">
                                <div id="autocomplete-container" class="form-row" style="display: none;"></div>
                                <div id="map"></div>
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.country') }}</label>
                                    <select class="form-select" name="country" id="select-countries"></select>
                                    <input type="hidden" id="selected-country"
                                           value="{{ !empty($store) && $store->country ? $store->country : '' }}"/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.address') }}</label>
                                    <input type="text" class="form-control" name="address" id="address"
                                           placeholder="{{ __('labels.enter_address') }}"
                                           value="{{ old('address', $store->address ?? '') }}"/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.landmark') }}</label>
                                    <input type="text" class="form-control" name="landmark" id="landmark"
                                           placeholder="{{ __('labels.enter_landmark') }}"
                                           value="{{ old('landmark', $store->landmark ?? '') }}"/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.city') }}</label>
                                    <input type="text" class="form-control" name="city" id="city"
                                           placeholder="{{ __('labels.enter_city') }}"
                                           value="{{ old('city', $store->city ?? '') }}"/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.state') }}</label>
                                    <select class="form-select" name="state_id" id="state_id_select"
                                            onchange="onStateChange(this)">
                                        <option value="">— Select State —</option>
                                        @foreach($states as $s)
                                            <option value="{{ $s->id }}"
                                                    data-name="{{ $s->name }}"
                                                    data-code="{{ $s->state_code }}"
                                                    data-gst="{{ $s->gst_code }}"
                                                    {{ old('state_id', $store->state_id ?? null) == $s->id ? 'selected' : '' }}>
                                                {{ $s->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    {{-- hidden field keeps the text value required by validation --}}
                                    <input type="hidden" name="state" id="state"
                                           value="{{ old('state', $store->state ?? '') }}"/>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.zipcode') }}</label>
                                    <input type="text" class="form-control" name="zipcode" id="zipcode"
                                           placeholder="{{ __('labels.enter_zipcode') }}"
                                           value="{{ old('zipcode', $store->zipcode ?? '') }}"/>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.latitude') }}</label>
                                            <input type="text" class="form-control" name="latitude" id="latitude"
                                                   placeholder="{{ __('labels.enter_latitude') }}"
                                                   value="{{ old('latitude', $store->latitude ?? '') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.longitude') }}</label>
                                            <input type="text" class="form-control" name="longitude" id="longitude"
                                                   placeholder="{{ __('labels.enter_longitude') }}"
                                                   value="{{ old('longitude', $store->longitude ?? '') }}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Business Documents -->
                        <div class="card mb-4" id="pills-documents">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('labels.business_documents') }}</h4>
                            </div>
                            <div class="card-body">
                                <h5 class="mb-3">GST Registration (India)</h5>
                                <div class="mb-3">
                                    <label class="form-label">GSTIN</label>
                                    <input type="text" class="form-control" name="gstin"
                                           maxlength="15"
                                           placeholder="e.g. 27AABCU9603R1ZV"
                                           value="{{ old('gstin', $store->gstin ?? '') }}"/>
                                    <small class="form-hint">15-character GST Identification Number. Appears on tax invoices.</small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">GST State Name</label>
                                        <input type="text" class="form-control" name="state_name" id="gst_state_name"
                                               placeholder="Auto-filled from state"
                                               value="{{ old('state_name', $store->state_name ?? '') }}"/>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">State Code</label>
                                        <input type="text" class="form-control" name="state_code" id="gst_state_code"
                                               maxlength="5"
                                               placeholder="Auto-filled from state"
                                               value="{{ old('state_code', $store->state_code ?? '') }}"/>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <label class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox"
                                                   name="gst_registered" value="1"
                                                   {{ old('gst_registered', $store->gst_registered ?? false) ? 'checked' : '' }}/>
                                            <span class="form-check-label">GST Registered</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end mb-4">
                            <div class="d-flex">
                                <a href="{{ route('admin.sellers.store.index') }}" class="btn btn-secondary me-2">
                                    {{ __('labels.cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary ms-auto">
                                    {{ empty($store) ? __('labels.submit') : __('labels.update') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        #map {
            height: 400px;
            width: 100%;
            margin-bottom: 20px;
        }
        .form-row {
            margin-bottom: 8px;
        }
    </style>

    <script>
        function onStateChange(select) {
            const opt = select.options[select.selectedIndex];
            const name = opt.dataset.name || '';
            const code = opt.dataset.code || '';

            // keep hidden "state" text field in sync (required by validation)
            document.getElementById('state').value = name;

            // auto-fill GST fields if they are still empty or were previously auto-filled
            const gstName = document.getElementById('gst_state_name');
            const gstCode = document.getElementById('gst_state_code');
            if (gstName) gstName.value = name;
            if (gstCode) gstCode.value = code;
        }

        // On page load, ensure hidden state field is populated when editing
        document.addEventListener('DOMContentLoaded', function () {
            const sel = document.getElementById('state_id_select');
            if (sel && sel.value && !document.getElementById('state').value) {
                onStateChange(sel);
            }
        });
    </script>
@endsection

@push('scripts')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ $googleApiKey }}&libraries=maps,places,marker&callback=initMap"
        async defer>
    </script>
    <script src="{{ hyperAsset('assets/js/stores.js') }}"></script>
@endpush
