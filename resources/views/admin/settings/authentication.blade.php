@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['authentication']['sub_active'] ?? "" ])

@section('title', __('labels.authentication_settings'))

@section('header_data')
    @php
        $page_title = __('labels.authentication_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.authentication_settings'), 'url' => null],
    ];
    $adminUser = auth('admin')->user();
    $adminTotpEnabled = $adminUser && method_exists($adminUser, 'isTotpEnabled') ? $adminUser->isTotpEnabled() : false;
    $adminTotpEnabledAt = $adminUser?->totp_enabled_at;
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.authentication_settings') }}</h2>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-5">
                <div class="col-sm-2 d-none d-lg-block">
                    <div class="sticky-top">
                        <h3>{{ __('labels.menu') }}</h3>
                        <nav class="nav nav-vertical nav-pills" id="pills">
                            <a class="nav-link" href="#pills-custom-sms">{{ __('labels.custom_sms') }}</a>
                            <a class="nav-link" href="#pills-google-keys">{{ __('labels.google_keys') }}</a>
                            <a class="nav-link" href="#pills-firebase">{{ __('labels.firebase') }}</a>
                            <a class="nav-link" href="#pills-social-login">{{ __('labels.social_login') }}</a>
                            <a class="nav-link" href="#pills-admin-totp">Admin TOTP</a>
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="authentication">
                                <div class="card mb-4" id="pills-custom-sms">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.custom_sms') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_custom_sms') }}</span>
                                                <span class="col-auto">
                                                        <label class="form-check form-check-single form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="customSms" value="1" {{ isset($settings['customSms']) && $settings['customSms'] ? 'checked' : '' }} />
                                                        </label>
                                                    </span>
                                            </label>
                                        </div>
                                        <div id="customSmsFields"
                                             style="{{ isset($settings['customSms']) && $settings['customSms'] ? 'display: block;' : 'display: none;' }}">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.custom_sms_url') }}</label>
                                                <input type="url" class="form-control" name="customSmsUrl"
                                                       placeholder="{{ __('labels.custom_sms_url_placeholder') }}"
                                                       value="{{ $settings['customSmsUrl'] ?? '' }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.custom_sms_method') }}</label>
                                                <select class="form-select" name="customSmsMethod">
                                                    <option
                                                        value="" {{ !isset($settings['customSmsMethod']) ? 'selected' : '' }}>{{ __('labels.custom_sms_method_placeholder') }}</option>
                                                    <option
                                                        value="GET" {{ isset($settings['customSmsMethod']) && $settings['customSmsMethod'] === 'GET' ? 'selected' : '' }}>
                                                        GET
                                                    </option>
                                                    <option
                                                        value="POST" {{ isset($settings['customSmsMethod']) && $settings['customSmsMethod'] === 'POST' ? 'selected' : '' }}>
                                                        POST
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.custom_sms_token_account_sid') }}</label>
                                                <input type="text" class="form-control"
                                                       name="customSmsTokenAccountSid"
                                                       placeholder="{{ __('labels.custom_sms_token_account_sid_placeholder') }}"
                                                       value="{{ $settings['customSmsTokenAccountSid'] ?? '' }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.custom_sms_auth_token') }}</label>
                                                <input type="text" class="form-control" name="customSmsAuthToken"
                                                       placeholder="{{ __('labels.custom_sms_auth_token_placeholder') }}"
                                                       value="{{ $settings['customSmsAuthToken'] ?? '' }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.custom_sms_text_format_data') }}</label>
                                                <input type="text" class="form-control"
                                                       name="customSmsTextFormatData"
                                                       placeholder="{{ __('labels.custom_sms_text_format_data_placeholder') }}"
                                                       value="{{ $settings['customSmsTextFormatData'] ?? '' }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.custom_sms_header') }}</label>
                                                <div id="headerFields">
                                                    @if(isset($settings['customSmsHeaderKey']) && is_array($settings['customSmsHeaderKey']))
                                                        @foreach($settings['customSmsHeaderKey'] as $index => $key)
                                                            <div class="row mb-2 header-field">
                                                                <div class="col-md-5">
                                                                    <input type="text" class="form-control"
                                                                           name="customSmsHeaderKey[]"
                                                                           placeholder="{{ __('labels.custom_sms_header_key_placeholder') }}"
                                                                           value="{{ $key }}"/>
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <input type="text" class="form-control"
                                                                           name="customSmsHeaderValue[]"
                                                                           placeholder="{{ __('labels.custom_sms_header_value_placeholder') }}"
                                                                           value="{{ $settings['customSmsHeaderValue'][$index] ?? '' }}"/>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <button type="button"
                                                                            class="btn btn-danger btn-sm remove-field">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                             class="icon icon-tabler icon-tabler-trash"
                                                                             width="24" height="24"
                                                                             viewBox="0 0 24 24" stroke-width="2"
                                                                             stroke="currentColor" fill="none"
                                                                             stroke-linecap="round"
                                                                             stroke-linejoin="round">
                                                                            <path stroke="none" d="M0 0h24v24H0z"
                                                                                  fill="none"/>
                                                                            <path d="M4 7l16 0"/>
                                                                            <path d="M10 11l0 6"/>
                                                                            <path d="M14 11l0 6"/>
                                                                            <path
                                                                                d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                                                            <path
                                                                                d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                <button type="button" class="btn btn-primary mt-2"
                                                        id="addHeaderField">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                         class="icon icon-tabler icon-tabler-plus" width="24"
                                                         height="24" viewBox="0 0 24 24" stroke-width="2"
                                                         stroke="currentColor" fill="none" stroke-linecap="round"
                                                         stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M12 5l0 14"/>
                                                        <path d="M5 12l14 0"/>
                                                    </svg>
                                                    {{ __('labels.add_header') }}
                                                </button>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.custom_sms_params') }}</label>
                                                <div id="paramsFields">
                                                    @if(isset($settings['customSmsParamsKey']) && is_array($settings['customSmsParamsKey']))
                                                        @foreach($settings['customSmsParamsKey'] as $index => $key)
                                                            <div class="row mb-2 params-field">
                                                                <div class="col-md-5">
                                                                    <input type="text" class="form-control"
                                                                           name="customSmsParamsKey[]"
                                                                           placeholder="{{ __('labels.custom_sms_params_key_placeholder') }}"
                                                                           value="{{ $key }}"/>
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <input type="text" class="form-control"
                                                                           name="customSmsParamsValue[]"
                                                                           placeholder="{{ __('labels.custom_sms_params_value_placeholder') }}"
                                                                           value="{{ $settings['customSmsParamsValue'][$index] ?? '' }}"/>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <button type="button"
                                                                            class="btn btn-danger btn-sm remove-field">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                             class="icon icon-tabler icon-tabler-trash"
                                                                             width="24" height="24"
                                                                             viewBox="0 0 24 24" stroke-width="2"
                                                                             stroke="currentColor" fill="none"
                                                                             stroke-linecap="round"
                                                                             stroke-linejoin="round">
                                                                            <path stroke="none" d="M0 0h24v24H0z"
                                                                                  fill="none"/>
                                                                            <path d="M4 7l16 0"/>
                                                                            <path d="M10 11l0 6"/>
                                                                            <path d="M14 11l0 6"/>
                                                                            <path
                                                                                d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                                                            <path
                                                                                d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                <button type="button" class="btn btn-primary mt-2"
                                                        id="addParamsField">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                         class="icon icon-tabler icon-tabler-plus" width="24"
                                                         height="24" viewBox="0 0 24 24" stroke-width="2"
                                                         stroke="currentColor" fill="none" stroke-linecap="round"
                                                         stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M12 5l0 14"/>
                                                        <path d="M5 12l14 0"/>
                                                    </svg>
                                                    {{ __('labels.add_param') }}
                                                </button>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.custom_sms_body') }}</label>
                                                <div id="bodyFields">
                                                    @if(isset($settings['customSmsBodyKey']) && is_array($settings['customSmsBodyKey']))
                                                        @foreach($settings['customSmsBodyKey'] as $index => $key)
                                                            <div class="row mb-2 body-field">
                                                                <div class="col-md-5">
                                                                    <input type="text" class="form-control"
                                                                           name="customSmsBodyKey[]"
                                                                           placeholder="{{ __('labels.custom_sms_body_key_placeholder') }}"
                                                                           value="{{ $key }}"/>
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <input type="text" class="form-control"
                                                                           name="customSmsBodyValue[]"
                                                                           placeholder="{{ __('labels.custom_sms_body_value_placeholder') }}"
                                                                           value="{{ $settings['customSmsBodyValue'][$index] ?? '' }}"/>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <button type="button"
                                                                            class="btn btn-danger btn-sm remove-field">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                             class="icon icon-tabler icon-tabler-trash"
                                                                             width="24" height="24"
                                                                             viewBox="0 0 24 24" stroke-width="2"
                                                                             stroke="currentColor" fill="none"
                                                                             stroke-linecap="round"
                                                                             stroke-linejoin="round">
                                                                            <path stroke="none" d="M0 0h24v24H0z"
                                                                                  fill="none"/>
                                                                            <path d="M4 7l16 0"/>
                                                                            <path d="M10 11l0 6"/>
                                                                            <path d="M14 11l0 6"/>
                                                                            <path
                                                                                d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                                                            <path
                                                                                d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                <button type="button" class="btn btn-primary mt-2"
                                                        id="addBodyField">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                         class="icon icon-tabler icon-tabler-plus" width="24"
                                                         height="24" viewBox="0 0 24 24" stroke-width="2"
                                                         stroke="currentColor" fill="none" stroke-linecap="round"
                                                         stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M12 5l0 14"/>
                                                        <path d="M5 12l14 0"/>
                                                    </svg>
                                                    {{ __('labels.add_body') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-google-keys">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.google_recaptcha') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.google_recaptcha_site_key') }}</label>
                                            <input type="text" class="form-control" name="googleRecaptchaSiteKey"
                                                   placeholder="{{ __('labels.google_recaptcha_site_key_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['googleRecaptchaSiteKey'] ?? '****'), '****', 3, 8) : ($settings['googleRecaptchaSiteKey'] ?? '') }}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.google_api_key') }}</label>
                                            <input type="text" class="form-control" name="googleApiKey"
                                                   placeholder="{{ __('labels.enter_google_api_key') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['googleApiKey'] ?? '****'), '****', 3, 8) : ($settings['googleApiKey'] ?? '') }}"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-firebase">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.firebase') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_firebase') }}</span>
                                                <span class="col-auto">
                                                        <label class="form-check form-check-single form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="firebase" value="1" {{ isset($settings['firebase']) && $settings['firebase'] ? 'checked' : '' }} />
                                                        </label>
                                                    </span>
                                            </label>
                                        </div>
                                        <div id="firebaseFields"
                                             style="{{ isset($settings['firebase']) && $settings['firebase'] ? 'display: block;' : 'display: none;' }}">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.firebase_api_key') }}</label>
                                                <input type="text" class="form-control" name="fireBaseApiKey"
                                                       placeholder="{{ __('labels.firebase_api_key_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['fireBaseApiKey'] ?? '****'), '****', 3, 8) : ($settings['fireBaseApiKey'] ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.firebase_auth_domain') }}</label>
                                                <input type="text" class="form-control" name="fireBaseAuthDomain"
                                                       placeholder="{{ __('labels.firebase_auth_domain_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['fireBaseAuthDomain'] ?? '****'), '****', 3, 8) : ($settings['fireBaseAuthDomain'] ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.firebase_database_url') }}</label>
                                                <input type="url" class="form-control" name="fireBaseDatabaseURL"
                                                       placeholder="{{ __('labels.firebase_database_url_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['fireBaseDatabaseURL'] ?? '****'), '****', 3, 8) : ($settings['fireBaseDatabaseURL'] ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.firebase_project_id') }}</label>
                                                <input type="text" class="form-control" name="fireBaseProjectId"
                                                       placeholder="{{ __('labels.firebase_project_id_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['fireBaseProjectId'] ?? '****'), '****', 3, 8) : ($settings['fireBaseProjectId'] ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.firebase_storage_bucket') }}</label>
                                                <input type="text" class="form-control" name="fireBaseStorageBucket"
                                                       placeholder="{{ __('labels.firebase_storage_bucket_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['fireBaseStorageBucket'] ?? '****'), '****', 3, 8) : ($settings['fireBaseStorageBucket'] ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.firebase_messaging_sender_id') }}</label>
                                                <input type="text" class="form-control"
                                                       name="fireBaseMessagingSenderId"
                                                       placeholder="{{ __('labels.firebase_messaging_sender_id_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['fireBaseMessagingSenderId'] ?? '****'), '****', 3, 8) : ($settings['fireBaseMessagingSenderId'] ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.firebase_app_id') }}</label>
                                                <input type="text" class="form-control" name="fireBaseAppId"
                                                       placeholder="{{ __('labels.firebase_app_id_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['fireBaseAppId'] ?? '****'), '****', 3, 8) : ($settings['fireBaseAppId'] ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.firebase_measurement_id') }}</label>
                                                <input type="text" class="form-control" name="fireBaseMeasurementId"
                                                       placeholder="{{ __('labels.firebase_measurement_id_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['fireBaseMeasurementId'] ?? '****'), '****', 3, 8) : ($settings['fireBaseMeasurementId'] ?? '') }}"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-social-login">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.social_login') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.apple_login') }}</span>
                                                <span class="col-auto">
                                                        <label class="form-check form-check-single form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="appleLogin" value="1" {{ isset($settings['appleLogin']) && $settings['appleLogin'] ? 'checked' : '' }} />
                                                        </label>
                                                    </span>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.google_login') }}</span>
                                                <span class="col-auto">
                                                        <label class="form-check form-check-single form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="googleLogin" value="1" {{ isset($settings['googleLogin']) && $settings['googleLogin'] ? 'checked' : '' }} />
                                                        </label>
                                                    </span>
                                            </label>
                                        </div>
{{--                                        <div class="mb-3">--}}
{{--                                            <label class="row">--}}
{{--                                                <span class="col">{{ __('labels.facebook_login') }}</span>--}}
{{--                                                <span class="col-auto">--}}
{{--                                                        <label class="form-check form-check-single form-switch">--}}
{{--                                                            <input class="form-check-input" type="checkbox"--}}
{{--                                                                   name="facebookLogin" value="1" {{ isset($settings['facebookLogin']) && $settings['facebookLogin'] ? 'checked' : '' }} />--}}
{{--                                                        </label>--}}
{{--                                                    </span>--}}
{{--                                            </label>--}}
{{--                                        </div>--}}
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'authentication'])
                                            <button type="submit"
                                                    class="btn btn-primary ms-auto">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-12">
                            <div class="card mb-4" id="pills-admin-totp">
                                <div class="card-header">
                                    <h4 class="card-title">Google Authenticator (TOTP)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <span class="badge {{ $adminTotpEnabled ? 'bg-green' : 'bg-secondary' }}" id="totp-status-badge">{{ $adminTotpEnabled ? 'Enabled' : 'Disabled' }}</span>
                                        <small class="text-muted d-block mt-1" id="totp-enabled-at">{{ $adminTotpEnabledAt ? 'Enabled at: ' . $adminTotpEnabledAt : '' }}</small>
                                    </div>

                                    <div id="totp-setup-block" class="border rounded p-3 mb-3 {{ $adminTotpEnabled ? 'd-none' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h5 class="mb-0">Setup</h5>
                                            <button type="button" class="btn btn-primary btn-sm" id="totp-start-setup-btn">Start Setup</button>
                                        </div>
                                        <p class="text-muted mb-3">Scan QR with Google Authenticator, then enable with one code.</p>
                                        <div id="totp-setup-data" class="d-none">
                                            <div class="row g-3 align-items-center">
                                                <div class="col-md-4">
                                                    <img id="totp-qr-image" src="" alt="TOTP QR" class="img-fluid border rounded">
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label">Manual Secret</label>
                                                    <input type="text" class="form-control" id="totp-manual-secret" readonly>
                                                    <small class="text-muted">Use this if QR scan does not work.</small>
                                                </div>
                                            </div>
                                        </div>
                                        <form id="totp-enable-form" class="mt-3 d-none">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label required">Current Password</label>
                                                    <input type="password" class="form-control" name="password" required autocomplete="current-password">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label required">Authenticator Code</label>
                                                    <input type="text" class="form-control" name="totp_code" maxlength="6" required placeholder="123456">
                                                </div>
                                            </div>
                                            <div class="mt-3 text-end">
                                                <button type="submit" class="btn btn-success">Enable TOTP</button>
                                            </div>
                                        </form>
                                    </div>

                                    <div id="totp-manage-block" class="border rounded p-3 {{ $adminTotpEnabled ? '' : 'd-none' }}">
                                        <h5 class="mb-3">Manage</h5>
                                        <form id="totp-disable-form" class="mb-3">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label required">Current Password</label>
                                                    <input type="password" class="form-control" name="password" required autocomplete="current-password">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Authenticator Code</label>
                                                    <input type="text" class="form-control" name="totp_code" maxlength="6" placeholder="123456">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Or Recovery Code</label>
                                                    <input type="text" class="form-control" name="recovery_code" placeholder="AAAA-BBBB">
                                                </div>
                                            </div>
                                            <div class="mt-3 text-end">
                                                <button type="submit" class="btn btn-danger">Disable TOTP</button>
                                            </div>
                                        </form>

                                        <form id="totp-regenerate-form">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label required">Current Password</label>
                                                    <input type="password" class="form-control" name="password" required autocomplete="current-password">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label required">Authenticator Code</label>
                                                    <input type="text" class="form-control" name="totp_code" maxlength="6" required placeholder="123456">
                                                </div>
                                            </div>
                                            <div class="mt-3 text-end">
                                                <button type="submit" class="btn btn-warning">Regenerate Recovery Codes</button>
                                            </div>
                                        </form>
                                    </div>

                                    <div id="totp-recovery-codes-wrapper" class="mt-3 d-none">
                                        <label class="form-label">Recovery Codes</label>
                                        <ul class="list-group" id="totp-recovery-codes-list"></ul>
                                        <small class="text-danger d-block mt-2">Save these codes now. They may not be shown again.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function addField(containerId, keyName, valueName, keyPlaceholder, valuePlaceholder) {
            const container = document.getElementById(containerId);
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'row mb-2 ' + containerId.replace('Fields', '') + '-field';
            fieldDiv.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" name="${keyName}[]" placeholder="${keyPlaceholder}" />
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="${valueName}[]" placeholder="${valuePlaceholder}" />
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-field">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M4 7l16 0" />
                            <path d="M10 11l0 6" />
                            <path d="M14 11l0 6" />
                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                        </svg>
                    </button>
                </div>
            `;
            container.appendChild(fieldDiv);
        }

        document.getElementById('addHeaderField')?.addEventListener('click', () => {
            addField('headerFields', 'customSmsHeaderKey', 'customSmsHeaderValue', '{{ __('labels.custom_sms_header_key_placeholder') }}', '{{ __('labels.custom_sms_header_value_placeholder') }}');
        });

        document.getElementById('addParamsField')?.addEventListener('click', () => {
            addField('paramsFields', 'customSmsParamsKey', 'customSmsParamsValue', '{{ __('labels.custom_sms_params_key_placeholder') }}', '{{ __('labels.custom_sms_params_value_placeholder') }}');
        });

        document.getElementById('addBodyField')?.addEventListener('click', () => {
            addField('bodyFields', 'customSmsBodyKey', 'customSmsBodyValue', '{{ __('labels.custom_sms_body_key_placeholder') }}', '{{ __('labels.custom_sms_body_value_placeholder') }}');
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-field') || e.target.closest('.remove-field')) {
                e.target.closest('.row').remove();
            }
        });

        const customSmsToggle = document.querySelector('input[name="customSms"]');
        const customSmsFields = document.getElementById('customSmsFields');
        const firebaseToggle = document.querySelector('input[name="firebase"]');
        const firebaseFields = document.getElementById('firebaseFields');

        const toggleCustomSmsFields = () => {
            customSmsFields.style.display = customSmsToggle.checked ? 'block' : 'none';
        };
        const toggleFirebaseFields = () => {
            firebaseFields.style.display = firebaseToggle.checked ? 'block' : 'none';
        };

        customSmsToggle?.addEventListener('change', toggleCustomSmsFields);
        firebaseToggle?.addEventListener('change', toggleFirebaseFields);
        if (customSmsToggle && customSmsFields) {
            toggleCustomSmsFields();
        }
        if (firebaseToggle && firebaseFields) {
            toggleFirebaseFields();
        }

        const totpStatusBadge = document.getElementById('totp-status-badge');
        const totpEnabledAt = document.getElementById('totp-enabled-at');
        const totpSetupBlock = document.getElementById('totp-setup-block');
        const totpSetupData = document.getElementById('totp-setup-data');
        const totpEnableForm = document.getElementById('totp-enable-form');
        const totpManageBlock = document.getElementById('totp-manage-block');
        const totpStartSetupBtn = document.getElementById('totp-start-setup-btn');
        const totpQrImage = document.getElementById('totp-qr-image');
        const totpManualSecret = document.getElementById('totp-manual-secret');
        const totpRecoveryCodesWrapper = document.getElementById('totp-recovery-codes-wrapper');
        const totpRecoveryCodesList = document.getElementById('totp-recovery-codes-list');
        const totpDisableForm = document.getElementById('totp-disable-form');
        const totpRegenerateForm = document.getElementById('totp-regenerate-form');

        const showRecoveryCodes = (codes) => {
            totpRecoveryCodesList.innerHTML = '';
            if (!Array.isArray(codes) || !codes.length) {
                totpRecoveryCodesWrapper.classList.add('d-none');
                return;
            }

            codes.forEach((code) => {
                const li = document.createElement('li');
                li.className = 'list-group-item font-monospace';
                li.textContent = String(code);
                totpRecoveryCodesList.appendChild(li);
            });
            totpRecoveryCodesWrapper.classList.remove('d-none');
        };

        const setTotpUi = (enabled, enabledAt = null) => {
            if (enabled) {
                totpStatusBadge.className = 'badge bg-green';
                totpStatusBadge.textContent = 'Enabled';
                totpSetupBlock.classList.add('d-none');
                totpManageBlock.classList.remove('d-none');
            } else {
                totpStatusBadge.className = 'badge bg-secondary';
                totpStatusBadge.textContent = 'Disabled';
                totpSetupBlock.classList.remove('d-none');
                totpManageBlock.classList.add('d-none');
                totpSetupData.classList.add('d-none');
                totpEnableForm.classList.add('d-none');
            }
            totpEnabledAt.textContent = enabledAt ? `Enabled at: ${enabledAt}` : '';
        };

        const handleApiError = (error) => {
            const message = error?.response?.data?.message || 'Request failed. Please try again.';
            Toast.fire({icon: 'error', title: message});
        };

        const fetchTotpStatus = () => {
            return axios.get("{{ route('admin.security.totp.status') }}", {
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).then((response) => {
                const data = response?.data?.data || {};
                setTotpUi(Boolean(data.enabled), data.enabled_at ?? null);
            }).catch((error) => {
                totpStatusBadge.className = 'badge bg-red';
                totpStatusBadge.textContent = 'Status unavailable';
                handleApiError(error);
            });
        };

        totpStartSetupBtn?.addEventListener('click', () => {
            totpStartSetupBtn.disabled = true;
            axios.post("{{ route('admin.security.totp.setup') }}", {}, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then((response) => {
                const data = response?.data?.data || {};
                totpQrImage.src = data.qr_url || '';
                totpManualSecret.value = data.secret || '';
                totpSetupData.classList.remove('d-none');
                totpEnableForm.classList.remove('d-none');
                Toast.fire({icon: 'success', title: response?.data?.message || 'TOTP setup started.'});
            }).catch(handleApiError)
                .finally(() => {
                    totpStartSetupBtn.disabled = false;
                });
        });

        totpEnableForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            const submitBtn = totpEnableForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            axios.post("{{ route('admin.security.totp.enable') }}", new FormData(totpEnableForm), {
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).then((response) => {
                showRecoveryCodes(response?.data?.data?.recovery_codes ?? []);
                Toast.fire({icon: 'success', title: response?.data?.message || 'TOTP enabled.'});
                totpEnableForm.reset();
                fetchTotpStatus();
            }).catch(handleApiError)
                .finally(() => {
                    submitBtn.disabled = false;
                });
        });

        totpDisableForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            const submitBtn = totpDisableForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            axios.post("{{ route('admin.security.totp.disable') }}", new FormData(totpDisableForm), {
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).then((response) => {
                Toast.fire({icon: 'success', title: response?.data?.message || 'TOTP disabled.'});
                totpDisableForm.reset();
                showRecoveryCodes([]);
                fetchTotpStatus();
            }).catch(handleApiError)
                .finally(() => {
                    submitBtn.disabled = false;
                });
        });

        totpRegenerateForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            const submitBtn = totpRegenerateForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            axios.post("{{ route('admin.security.totp.recovery-codes') }}", new FormData(totpRegenerateForm), {
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).then((response) => {
                showRecoveryCodes(response?.data?.data?.recovery_codes ?? []);
                Toast.fire({icon: 'success', title: response?.data?.message || 'Recovery codes regenerated.'});
                totpRegenerateForm.reset();
            }).catch(handleApiError)
                .finally(() => {
                    submitBtn.disabled = false;
                });
        });

        fetchTotpStatus();
    </script>
@endsection
