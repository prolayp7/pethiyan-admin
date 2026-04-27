@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['system']['sub_active'] ?? "" ])

@section('title', __('labels.system_settings'))

@section('header_data')
    @php
        $page_title = __('labels.system_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.system_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.system_settings') }}</h2>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
        </div>
    </div>
    <!-- BEGIN PAGE BODY -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-5">
                <div class="col-sm-2 d-none d-lg-block">
                    <div class="sticky-top">
                        <h3>{{ __('labels.menu') }}</h3>
                        <nav class="nav nav-vertical nav-pills" id="pills">
                            <a class="nav-link" href="#pills-general">{{ __('labels.general') }}</a>
                            <a class="nav-link"
                               href="#pills-support">{{ __('labels.support_information') }}</a>
                            <a class="nav-link"
                               href="#pills-cart">{{ __('labels.cart_inventory_settings') }}</a>
                            <a class="nav-link"
                               href="#pills-order-settings">Order Settings</a>
                            <a class="nav-link" href="#pills-product-grid">Product Grid Display</a>
                            
                            {{-- <a class="nav-link" href="#pills-wallet">{{ __('labels.wallet_settings') }}</a> --}}
                            {{-- <a class="nav-link"
                               href="#pills-maintenance">{{ __('labels.maintenance_mode') }}</a> --}}
                            {{-- <a class="nav-link"
                               href="#pills-demomode">{{ __('labels.demo_mode') }}</a> --}}
                            <a class="nav-link" href="#pills-social">Social Media</a>
                            
                            @can('viewSetting', [\App\Models\Setting::class, 'web'])
                                <a class="nav-link" href="#pills-web-settings-anchor">{{ __('labels.web_settings') }}</a>
                                <a class="nav-link" href="#pills-web-default-location">{{ __('labels.default_location') }}</a>
                                {{--<a class="nav-link" href="#pills-web-country-validation">{{ __('labels.country_validation') }}</a>--}}
                                <a class="nav-link" href="#pills-web-support">{{ __('labels.support_information') }}</a>
                                <a class="nav-link" href="#pills-web-seo">{{ __('labels.seo_settings') }}</a>
                                <a class="nav-link" href="#pills-web-footer-seo">Footer SEO Content</a>
                                {{--<a class="nav-link" href="#pills-web-app">{{ __('labels.app_download_section') }}</a>--}}
                                {{--<a class="nav-link" href="#pills-web-features">{{ __('labels.feature_sections') }}</a>--}}
                                {{-- Policy settings removed per request --}}
                                {{--<a class="nav-link" href="#pills-web-pwa-manifest">{{ __('labels.pwa_manifest_settings') }}</a>
                                <a class="nav-link" href="#pills-web-scripts">{{ __('labels.scripts') }}</a>--}}
                            @endcan
                            {{--                            <a class="nav-link"--}}
                            {{--                               href="#pills-referral">{{ __('labels.referral_earn_program') }}</a>--}}
                        </nav>
                    </div>
                </div>
                <div class="col-sm">
                    <div class="row row-cards">
                        <div class="col-12">
                            {{-- ── GENERAL ─────────────────────────────────────────── --}}
                            <form action="{{route('admin.settings.store')}}" class="form-submit" method="post"
                                  enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="type" value="system">
                                <input type="hidden" name="_section" value="general">
                                <div class="card mb-4" id="pills-general">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.general') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.app_name') }}</label>
                                            <input type="text" class="form-control" name="appName"
                                                   placeholder="{{ __('labels.app_name_placeholder') }}"
                                                   value="{{$settings['appName'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.system_timezone') }}</label>
                                            @php
                                                $timezones = \DateTimeZone::listIdentifiers();
                                                $selectedTimezone = $settings['systemTimezone'] ?? config('app.timezone', 'UTC');
                                            @endphp
                                            <select class="form-select" id="select-timezone" name="systemTimezone">
                                                @foreach($timezones as $timezone)
                                                    <option value="{{ $timezone }}" {{ $selectedTimezone === $timezone ? 'selected' : '' }}>
                                                        {{ $timezone }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.copyright_details') }}</label>
                                            <input type="text" class="form-control" name="copyrightDetails"
                                                   placeholder="{{ __('labels.copyright_details_placeholder') }}"
                                                   value="{{$settings['copyrightDetails'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.currency') }}</label>
                                            <input type="hidden" name="currencySymbol" id="currency-symbol"
                                                   value="{{$settings['currencySymbol'] ?? ''}}">
                                            <input type="hidden" id="selected-currency"
                                                   value="{{$settings['currency'] ?? 'INR'}}">
                                            <select class="form-select" id="select-currency" name="currency"
                                                    placeholder="USD, EUR, INR, etc."></select>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div
                                                        class="form-label required">{{ __('labels.logo') }}</div>
                                                    <input type="file" class="form-control" name="logo"
                                                           data-image-url="{{ $settings['logo'] }}"/>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div
                                                        class="form-label required">{{ __('labels.favicon') }}</div>
                                                    <input type="file" name="favicon"
                                                           data-image-url="{{ $settings['favicon'] }}"/>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Company Address</label>
                                            <textarea class="form-control" name="companyAddress" rows="3" placeholder="Enter company address shown on invoice">{{ $settings['companyAddress'] ?? '' }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Company GSTIN</label>
                                            <input type="text" class="form-control" name="companyGstin"
                                                   placeholder="e.g. 29ABCDE1234F1Z5"
                                                   maxlength="15"
                                                   style="text-transform:uppercase"
                                                   value="{{ $settings['companyGstin'] ?? '' }}"/>
                                            <small class="form-hint">15-character GST Identification Number printed on invoices. Leave blank if not applicable.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Admin Signature (Authorized Signatory)</label>
                                            <input type="file" name="adminSignature" data-image-url="{{ $settings['adminSignature'] ?? '' }}"/>
                                            <small class="form-hint">Upload a signature image to display on invoices.</small>
                                        </div>

                                        @can('viewSetting', [\App\Models\Setting::class, 'web'])
                                            <hr class="my-4">
                                            <h5 class="mb-3">Website Branding & Basics</h5>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.address') }}</label>
                                                <input type="text" class="form-control" name="address"
                                                       placeholder="{{ __('labels.address_placeholder') }}"
                                                       value="{{ $webSettings['address'] ?? '' }}" maxlength="255"/>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.short_description') }}</label>
                                                <textarea class="form-control" name="shortDescription"
                                                          placeholder="{{ __('labels.short_description_placeholder') }}"
                                                          maxlength="500">{{ $webSettings['shortDescription'] ?? '' }}</textarea>
                                            </div>
                                        @endcan
                                    </div>
                                    <div class="card-footer text-end">
                                        @can('updateSetting', [\App\Models\Setting::class, 'system'])
                                            <button type="submit" class="btn btn-primary">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>

                            {{-- ── SUPPORT INFORMATION ─────────────────────────────── --}}
                            <form action="{{route('admin.settings.store')}}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="system">
                                <input type="hidden" name="_section" value="support">
                                <div class="card mb-4" id="pills-support">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.support_information') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.seller_support_email') }}</label>
                                            <div>
                                                <input type="email" class="form-control" name="sellerSupportEmail"
                                                       aria-describedby="emailHelp"
                                                       placeholder="{{ __('labels.seller_support_email_placeholder') }}"
                                                       value="{{$settings['sellerSupportEmail'] ?? ''}}"/>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.seller_support_number') }}</label>
                                            <div>
                                                <input type="tel" class="form-control" name="sellerSupportNumber"
                                                       aria-describedby="numberHelp"
                                                       placeholder="{{ __('labels.seller_support_number_placeholder') }}"
                                                       value="{{$settings['sellerSupportNumber'] ?? ''}}"/>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        @can('updateSetting', [\App\Models\Setting::class, 'system'])
                                            <button type="submit" class="btn btn-primary">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>

                            {{-- ── CART & INVENTORY ────────────────────────────────── --}}
                            <form action="{{route('admin.settings.store')}}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="system">
                                <input type="hidden" name="_section" value="cart">
                                <div class="card mb-4" id="pills-cart">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.cart_inventory_settings') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.select_checkout_type') }}</label>
                                            <div>
                                                <label class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="checkoutType"
                                                           value="single_store" {{!empty($settings['checkoutType']) && $settings['checkoutType'] === 'single_store' ? 'checked' : ''}}>
                                                    <span
                                                        class="form-check-label">{{ __('labels.single_store') }}</span>
                                                </label>
                                                <label class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="checkoutType"
                                                           value="multi_store" {{!empty($settings['checkoutType']) && $settings['checkoutType'] === 'multi_store' ? 'checked' : ''}}>
                                                    <span class="form-check-label">{{ __('labels.multi_store') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.minimum_cart_amount') }}</label>
                                            <input type="number" step="0.01" min="0" class="form-control"
                                                   name="minimumCartAmount"
                                                   placeholder="{{ __('labels.minimum_cart_amount_placeholder') }}"
                                                   value="{{$settings['minimumCartAmount'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.maximum_items_allowed_in_cart') }}</label>
                                            <input type="number" min="1" class="form-control"
                                                   name="maximumItemsAllowedInCart"
                                                   placeholder="{{ __('labels.maximum_items_allowed_in_cart placeholder') }}"
                                                   value="{{$settings['maximumItemsAllowedInCart'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.low_stock_limit') }}</label>
                                            <input type="number" min="0" class="form-control" name="lowStockLimit"
                                                   placeholder="{{ __('labels.low_stock_limit_placeholder') }}"
                                                   value="{{$settings['lowStockLimit'] ?? ''}}"/>
                                        </div>
{{--                                        <div class="mb-3">--}}
{{--                                            <label--}}
{{--                                                class="form-label required">{{ __('labels.maximum_distance_to_nearest_store') }}</label>--}}
{{--                                            <input type="number" step="0.01" min="0" class="form-control"--}}
{{--                                                   name="maximumDistanceToNearestStore"--}}
{{--                                                   placeholder="{{ __('labels.maximum_distance_to_nearest_store_placeholder') }}"--}}
{{--                                                   value="{{$settings['maximumDistanceToNearestStore'] ?? ''}}"/>--}}
{{--                                        </div>--}}
                                    </div>
                                    <div class="card-footer text-end">
                                        @can('updateSetting', [\App\Models\Setting::class, 'system'])
                                            <button type="submit" class="btn btn-primary">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>

                            {{-- ── ORDER SETTINGS ──────────────────────────────────── --}}
                            <form action="{{route('admin.settings.store')}}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="system">
                                <input type="hidden" name="_section" value="order">
                                <div class="card mb-4" id="pills-order-settings">
                                    <div class="card-header">
                                        <h4 class="card-title">Order Settings</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">Enable Customer Invoice Download</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="customerInvoiceDownloadEnabled" value="1"
                                                               {{ !empty($settings['customerInvoiceDownloadEnabled']) ? 'checked' : '' }}/>
                                                    </label>
                                                </span>
                                            </label>
                                            <small class="form-hint">Controls whether customers can download invoices from frontend order details.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">Invoice Download Allowed From Status</label>
                                            <select class="form-select" name="customerInvoiceDownloadMinStatus">
                                                <option value="awaiting_store_response" {{ ($settings['customerInvoiceDownloadMinStatus'] ?? 'awaiting_store_response') === 'awaiting_store_response' ? 'selected' : '' }}>Awaiting Store Response</option>
                                                <option value="accepted_by_seller" {{ ($settings['customerInvoiceDownloadMinStatus'] ?? '') === 'accepted_by_seller' ? 'selected' : '' }}>Order Accepted</option>
                                                <option value="preparing" {{ ($settings['customerInvoiceDownloadMinStatus'] ?? '') === 'preparing' ? 'selected' : '' }}>Order Start Packing</option>
                                                <option value="ready_for_pickup" {{ ($settings['customerInvoiceDownloadMinStatus'] ?? '') === 'ready_for_pickup' ? 'selected' : '' }}>Order Packing Done</option>
                                                <option value="assigned" {{ ($settings['customerInvoiceDownloadMinStatus'] ?? '') === 'assigned' ? 'selected' : '' }}>Order Ready for Pickup</option>
                                                <option value="collected" {{ ($settings['customerInvoiceDownloadMinStatus'] ?? '') === 'collected' ? 'selected' : '' }}>Order Collected</option>
                                                <option value="cancelled" {{ ($settings['customerInvoiceDownloadMinStatus'] ?? '') === 'cancelled' ? 'selected' : '' }}>Order Cancelled</option>
                                                <option value="failed" {{ ($settings['customerInvoiceDownloadMinStatus'] ?? '') === 'failed' ? 'selected' : '' }}>Order Failed</option>
                                                <option value="delivered" {{ ($settings['customerInvoiceDownloadMinStatus'] ?? '') === 'delivered' ? 'selected' : '' }}>Order Dispatched</option>
                                            </select>
                                            <small class="form-hint">Choose the minimum order status after which customers can download invoices.</small>
                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        @can('updateSetting', [\App\Models\Setting::class, 'system'])
                                            <button type="submit" class="btn btn-primary">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>

                                {{-- Wallet Settings (temporarily disabled)
                                <div class="card mb-4" id="pills-wallet">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.wallet_settings') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.welcome_wallet_balance_amount') }}</label>
                                            <input type="number" step="0.01" min="0" class="form-control"
                                                   name="welcomeWalletBalanceAmount"
                                                   placeholder="{{ __('labels.welcome_wallet_balance_amount_placeholder') }}"
                                                   value="{{$settings['welcomeWalletBalanceAmount'] ?? '0'}}"/>
                                        </div>
                                    </div>
                                </div>
                                --}}

                                {{-- Maintenance Mode (temporarily disabled)
                                <div class="card mb-4" id="pills-maintenance">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.maintenance_mode') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                    <span
                                                        class="col">{{ __('labels.seller_app_maintenance_mode') }}</span>
                                                <span class="col-auto">
                                                        <label class="form-check form-check-single form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="sellerAppMaintenanceMode" value="1" {{ isset($settings['sellerAppMaintenanceMode']) && $settings['sellerAppMaintenanceMode'] ? 'checked' : '' }}/>
                                                        </label>
                                                    </span>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.seller_app_maintenance_message') }}</label>
                                            <input type="text" class="form-control"
                                                   name="sellerAppMaintenanceMessage"
                                                   placeholder="{{ __('labels.seller_app_maintenance_message_placeholder') }}"
                                                   value="{{$settings['sellerAppMaintenanceMessage'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="row">
                                                    <span
                                                        class="col">{{ __('labels.web_maintenance_mode') }}</span>
                                                <span class="col-auto">
                                                        <label class="form-check form-check-single form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="webMaintenanceMode" value="1" {{ isset($settings['webMaintenanceMode']) && $settings['webMaintenanceMode'] ? 'checked' : '' }}/>
                                                        </label>
                                                    </span>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.web_maintenance_message') }}</label>
                                            <input type="text" class="form-control" name="webMaintenanceMessage"
                                                   placeholder="{{ __('labels.web_maintenance_message_placeholder') }}"
                                                   value="{{$settings['webMaintenanceMessage'] ?? ''}}"/>
                                        </div>
                                    </div>
                                </div>
                                --}}

                            {{-- ── DEMO MODE (temporarily hidden) ──────────────────── --}}
                            {{-- <form action="{{route('admin.settings.store')}}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="system">
                                <input type="hidden" name="_section" value="demomode">
                                <div class="card mb-4" id="pills-demomode">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.demo_mode') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_demo_mode') }}</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="demoMode" value="1" {{ isset($settings['demoMode']) && $settings['demoMode'] ? 'checked' : '' }}/>
                                                    </label>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.admin_demo_mode_message') }}</label>
                                            <input type="text" class="form-control" name="adminDemoModeMessage"
                                                   placeholder="{{ __('labels.admin_demo_mode_message_placeholder') }}"
                                                   value="{{$settings['adminDemoModeMessage'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.seller_demo_mode_message') }}</label>
                                            <input type="text" class="form-control" name="sellerDemoModeMessage"
                                                   placeholder="{{ __('labels.seller_demo_mode_message_placeholder') }}"
                                                   value="{{$settings['sellerDemoModeMessage'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.customer_demo_mode_message') }}</label>
                                            <input type="text" class="form-control" name="customerDemoModeMessage"
                                                   placeholder="{{ __('labels.customer_demo_mode_message_placeholder') }}"
                                                   value="{{$settings['customerDemoModeMessage'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.customer_location_demo_mode_message') }}</label>
                                            <input type="text" class="form-control" name="customerLocationDemoModeMessage"
                                                   placeholder="{{ __('labels.customer_location_demo_mode_message_placeholder') }}"
                                                   value="{{$settings['customerLocationDemoModeMessage'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.delivery_boy_demo_mode_message') }}</label>
                                            <input type="text" class="form-control" name="deliveryBoyDemoModeMessage"
                                                   placeholder="{{ __('labels.delivery_boy_demo_mode_message_placeholder') }}"
                                                   value="{{$settings['deliveryBoyDemoModeMessage'] ?? ''}}"/>
                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        @can('updateSetting', [\App\Models\Setting::class, 'system'])
                                            <button type="submit" class="btn btn-primary">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form> --}}

                                {{--                                <div class="card mb-4" id="pills-referral">--}}
                                {{--                                    <div class="card-header">--}}
                                {{--                                        <h4 class="card-title">{{ __('labels.referral_earn_program') }}</h4>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="card-body">--}}
                                {{--                                        <div class="mb-3">--}}
                                {{--                                            <label class="row">--}}
                                {{--                                                    <span--}}
                                {{--                                                        class="col">{{ __('labels.enable_referral_program') }}</span>--}}
                                {{--                                                <span class="col-auto">--}}
                                {{--                                                        <label class="form-check form-check-single form-switch">--}}
                                {{--                                                            <input class="form-check-input" type="checkbox"--}}
                                {{--                                                                   name="referEarnStatus" role="switch"--}}
                                {{--                                                                   id="referEarnToggle" {{ isset($settings['referEarnStatus']) && $settings['referEarnStatus'] ? 'checked' : '' }}/>--}}
                                {{--                                                        </label>--}}
                                {{--                                                    </span>--}}
                                {{--                                            </label>--}}
                                {{--                                        </div>--}}
                                {{--                                        <div id="referEarnFields">--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.user_bonus_method') }}</label>--}}
                                {{--                                                <select class="form-select" name="referEarnMethodUser">--}}
                                {{--                                                    <option--}}
                                {{--                                                        value="fixed" {{ isset($settings['referEarnMethodUser']) && $settings['referEarnMethodUser'] === 'fixed' ? 'selected' : '' }}>--}}
                                {{--                                                        {{ __('labels.fixed') }}--}}
                                {{--                                                    </option>--}}
                                {{--                                                    <option--}}
                                {{--                                                        value="percentage" {{ isset($settings['referEarnMethodUser']) && $settings['referEarnMethodUser'] === 'percentage' ? 'selected' : '' }}>--}}
                                {{--                                                        {{ __('labels.percentage') }}--}}
                                {{--                                                    </option>--}}
                                {{--                                                </select>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.user_bonus') }}</label>--}}
                                {{--                                                <input type="number" class="form-control" name="referEarnBonusUser"--}}
                                {{--                                                       placeholder="{{ __('labels.user_bonus_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnBonusUser'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.max_bonus_amount_user') }}</label>--}}
                                {{--                                                <input type="number" min="0" step="0.01" class="form-control"--}}
                                {{--                                                       name="referEarnMaximumBonusAmountUser"--}}
                                {{--                                                       placeholder="{{ __('labels.max_bonus_amount_user_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnMaximumBonusAmountUser'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.referral_bonus_method') }}</label>--}}
                                {{--                                                <select class="form-select" name="referEarnMethodReferral">--}}
                                {{--                                                    <option--}}
                                {{--                                                        value="fixed" {{ isset($settings['referEarnMethodReferral']) && $settings['referEarnMethodReferral'] === 'fixed' ? 'selected' : '' }}>--}}
                                {{--                                                        {{ __('labels.fixed') }}--}}
                                {{--                                                    </option>--}}
                                {{--                                                    <option--}}
                                {{--                                                        value="percentage" {{ isset($settings['referEarnMethodReferral']) && $settings['referEarnMethodReferral'] === 'percentage' ? 'selected' : '' }}>--}}
                                {{--                                                        {{ __('labels.percentage') }}--}}
                                {{--                                                    </option>--}}
                                {{--                                                </select>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.referral_bonus') }}</label>--}}
                                {{--                                                <input type="number" class="form-control"--}}
                                {{--                                                       name="referEarnBonusReferral"--}}
                                {{--                                                       placeholder="{{ __('labels.referral_bonus_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnBonusReferral'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.max_bonus_amount_referral') }}</label>--}}
                                {{--                                                <input type="number" min="0" step="0.01" class="form-control"--}}
                                {{--                                                       name="referEarnMaximumBonusAmountReferral"--}}
                                {{--                                                       placeholder="{{ __('labels.max_bonus_amount_referral_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnMaximumBonusAmountReferral'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.minimum_order_amount_for_bonus') }}</label>--}}
                                {{--                                                <input type="number" min="0" step="0.01" class="form-control"--}}
                                {{--                                                       name="referEarnMinimumOrderAmount"--}}
                                {{--                                                       placeholder="{{ __('labels.minimum_order_amount_for_bonus_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnMinimumOrderAmount'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.number_of_times_bonus_applicable') }}</label>--}}
                                {{--                                                <input type="number" min="0" class="form-control"--}}
                                {{--                                                       name="referEarnNumberOfTimesBonus"--}}
                                {{--                                                       placeholder="{{ __('labels.number_of_times_bonus_applicable_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnNumberOfTimesBonus'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}

                            {{-- ── SOCIAL MEDIA ─────────────────────────────────────── --}}
                            @php
                                $socialPlatforms = [
                                    'facebook'  => 'Facebook',
                                    'instagram' => 'Instagram',
                                    'twitter'   => 'Twitter / X',
                                    'youtube'   => 'YouTube',
                                    'linkedin'  => 'LinkedIn',
                                    'pinterest' => 'Pinterest',
                                    'whatsapp'  => 'WhatsApp',
                                    'telegram'  => 'Telegram',
                                ];
                            @endphp
                            <form action="{{route('admin.settings.store')}}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="system">
                                <input type="hidden" name="_section" value="social">
                                <div class="card mb-4" id="pills-social">
                                    <div class="card-header">
                                        <h4 class="card-title">Social Media Links</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-3">Configure social media links shown in the frontend footer. Toggle each platform on/off without removing the URL.</p>
                                        @foreach ($socialPlatforms as $key => $label)
                                            @php $link = $settings['socialLinks'][$key] ?? []; @endphp
                                            <div class="mb-3">
                                                <div class="row align-items-center g-2">
                                                    <div class="col-sm-2">
                                                        <span class="badge bg-secondary-lt text-secondary fw-medium w-100 py-2">
                                                            {{ $label }}
                                                        </span>
                                                    </div>
                                                    <div class="col">
                                                        <input
                                                            type="url"
                                                            class="form-control"
                                                            name="socialLinks[{{ $key }}][url]"
                                                            placeholder="https://..."
                                                            value="{{ $link['url'] ?? '' }}"
                                                        />
                                                    </div>
                                                    <div class="col-auto">
                                                        <label class="form-check form-switch mb-0">
                                                            <input
                                                                class="form-check-input"
                                                                type="checkbox"
                                                                name="socialLinks[{{ $key }}][active]"
                                                                value="1"
                                                                {{ ($link['active'] ?? false) ? 'checked' : '' }}
                                                            />
                                                            <span class="form-check-label">Active</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="card-footer text-end">
                                        @can('updateSetting', [\App\Models\Setting::class, 'system'])
                                            <button type="submit" class="btn btn-primary">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>

                            {{-- ── PRODUCT GRID DISPLAY ─────────────────────────── --}}
                            <form action="{{route('admin.settings.store')}}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="system">
                                <input type="hidden" name="_section" value="product_grid">
                                <div class="card mb-4" id="pills-product-grid">
                                    <div class="card-header">
                                        <h4 class="card-title">Product Grid Display</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-3">Control which elements are shown on product cards across all grids (Shop, Category, Related Products, etc.).</p>

                                        <label class="row mb-3">
                                            <span class="col">
                                                <span class="fw-medium">Show variant colour swatches</span>
                                                <small class="d-block text-muted">Displays colour dots and variant count below the product title.</small>
                                            </span>
                                            <span class="col-auto">
                                                <label class="form-check form-check-single form-switch">
                                                    <input class="form-check-input" type="checkbox" name="showVariantColorsInGrid" role="switch"
                                                           value="1" {{ !empty($settings['showVariantColorsInGrid']) ? 'checked' : '' }}/>
                                                </label>
                                            </span>
                                        </label>

                                        <label class="row mb-3">
                                            <span class="col">
                                                <span class="fw-medium">Show GST rate</span>
                                                <small class="d-block text-muted">Shows "+X% GST" below the price row.</small>
                                            </span>
                                            <span class="col-auto">
                                                <label class="form-check form-check-single form-switch">
                                                    <input class="form-check-input" type="checkbox" name="showGstInGrid" role="switch"
                                                           value="1" {{ !empty($settings['showGstInGrid']) ? 'checked' : '' }}/>
                                                </label>
                                            </span>
                                        </label>

                                        <label class="row mb-3">
                                            <span class="col">
                                                <span class="fw-medium">Show category name</span>
                                                <small class="d-block text-muted">Displays the product's category above the title.</small>
                                            </span>
                                            <span class="col-auto">
                                                <label class="form-check form-check-single form-switch">
                                                    <input class="form-check-input" type="checkbox" name="showCategoryNameInGrid" role="switch"
                                                           value="1" {{ !empty($settings['showCategoryNameInGrid']) ? 'checked' : '' }}/>
                                                </label>
                                            </span>
                                        </label>

                                        <label class="row mb-3">
                                            <span class="col">
                                                <span class="fw-medium">Show minimum order quantity</span>
                                                <small class="d-block text-muted">Shows "Min: X pcs" below the price row.</small>
                                            </span>
                                            <span class="col-auto">
                                                <label class="form-check form-check-single form-switch">
                                                    <input class="form-check-input" type="checkbox" name="showMinQtyInGrid" role="switch"
                                                           value="1" {{ !empty($settings['showMinQtyInGrid']) ? 'checked' : '' }}/>
                                                </label>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="card-footer text-end">
                                        @can('updateSetting', [\App\Models\Setting::class, 'system'])
                                            <button type="submit" class="btn btn-primary">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>

                            @can('viewSetting', [\App\Models\Setting::class, 'web'])
                                <div class="card mb-4 mt-4" id="pills-web-settings-anchor">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.web_settings') }}</h4>
                                    </div>
                                    <div class="card-body text-muted">
                                        Web settings are now managed from this System Settings page.
                                    </div>
                                </div>
                                @include('admin.settings.partials.web-settings-form', ['settings' => $webSettings])
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END PAGE BODY -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menu = document.getElementById('pills');
            const menuLinks = menu ? Array.from(menu.querySelectorAll('a.nav-link[href^="#"]')) : [];
            const setActiveMenuByHash = (hash) => {
                if (!hash) return;
                menuLinks.forEach((link) => {
                    link.classList.toggle('active', link.getAttribute('href') === hash);
                });
            };

            if (menuLinks.length) {
                menuLinks.forEach((link) => {
                    link.addEventListener('click', function () {
                        setActiveMenuByHash(this.getAttribute('href'));
                    });
                });

                setActiveMenuByHash(window.location.hash || '#pills-general');
                window.addEventListener('hashchange', () => setActiveMenuByHash(window.location.hash));
            }

            const timezoneSelect = document.getElementById('select-timezone');
            if (timezoneSelect && window.TomSelect) {
                new TomSelect(timezoneSelect, {
                    create: false,
                    maxOptions: 500,
                    sortField: { field: 'text', direction: 'asc' },
                    placeholder: 'Search timezone...'
                });
            }

            const toggle = document.getElementById('referEarnToggle');
            const fields = document.getElementById('referEarnFields');
            if (toggle && fields) {
                const toggleFields = () => {
                    fields.style.display = toggle.checked ? 'block' : 'none';
                };
                toggle.addEventListener('change', toggleFields);
                toggleFields();
            }
        });
    </script>
@endsection

@can('viewSetting', [\App\Models\Setting::class, 'web'])
    @push('script')
        <script async defer>(g => {
                var h, a, k, p = "The Google Maps JavaScript API", c = "google", l = "importLibrary", q = "__ib__",
                    m = document, b = window;
                b = b[c] || (b[c] = {});
                var d = b.maps || (b.maps = {}), r = new Set, e = new URLSearchParams,
                    u = () => h || (h = new Promise(async (f, n) => {
                        await (a = m.createElement("script"));
                        e.set("libraries", [...r] + "");
                        for (k in g) e.set(k.replace(/[A-Z]/g, t => "_" + t[0].toLowerCase()), g[k]);
                        e.set("callback", c + ".maps." + q);
                        a.src = `https://maps.${c}apis.com/maps/api/js?` + e;
                        d[q] = f;
                        a.onerror = () => h = n(Error(p + " could not load."));
                        a.nonce = m.querySelector("script[nonce]")?.nonce || "";
                        m.head.append(a)
                    }));
                d[l] ? console.warn(p + " only loads once. Ignoring:", g) : d[l] = (f, ...n) => r.add(f) && u().then(() => d[l](f, ...n))
            })
            ({key: "{{$webSettings['googleMapKey'] ?? ''}}", v: "weekly"});</script>
        <script src="{{hyperAsset('assets/js/settings.js')}}"></script>
    @endpush
@endcan
