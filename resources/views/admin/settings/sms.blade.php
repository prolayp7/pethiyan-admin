@extends('layouts.admin.app',['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['sms']['sub_active'] ?? "" ])

@section('title', __('labels.sms_settings'))

@section('header_data')
    @php
        $page_title = __('labels.sms_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.sms_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.sms_settings') }}</h2>
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
                            <a class="nav-link" href="#pills-demo-mode">Demo Mode</a>
                            <a class="nav-link" href="#pills-general">{{ __('labels.general') }}</a>
                            <a class="nav-link" href="#pills-msg91">MSG91</a>
                            <a class="nav-link" href="#pills-twilio">Twilio</a>
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="sms">

                                {{-- OTP Demo Mode --}}
                                <div class="card mb-4 border-warning" id="pills-demo-mode">
                                    <div class="card-header bg-warning-lt">
                                        <h4 class="card-title text-warning">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon me-1">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M12 9v4"/>
                                                <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/>
                                                <path d="M12 16h.01"/>
                                            </svg>
                                            OTP Demo Mode
                                        </h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning mb-3">
                                            <strong>Development / Testing Only</strong> — When enabled, all OTP requests bypass the SMS gateway and use the fixed code <strong>123456</strong>. Disable before going live.
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                       name="otp_demo_mode" value="1"
                                                       {{ !empty($settings['otp_demo_mode']) ? 'checked' : '' }}>
                                                <span class="form-check-label fw-medium">Enable OTP Demo Mode</span>
                                            </label>
                                            <small class="form-hint d-block mt-1">
                                                When ON: SMS is skipped, OTP is always <code>123456</code>, and the SMS enabled check is bypassed.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                {{-- General --}}
                                <div class="card mb-4" id="pills-general">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.general') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.enable_sms_otp') }}</label>
                                            <div>
                                                <label class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="enabled"
                                                           value="1" {{ !empty($settings['enabled']) ? 'checked' : '' }}>
                                                    <span class="form-check-label">{{ __('labels.enabled') }}</span>
                                                </label>
                                            </div>
                                            <small class="form-hint">{{ __('messages.sms_otp_enable_hint') }}</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.sms_gateway') }}</label>
                                            <select class="form-select" name="gateway" id="smsGatewaySelect">
                                                <option value="msg91"  {{ ($settings['gateway'] ?? 'msg91') === 'msg91'  ? 'selected' : '' }}>MSG91 (India)</option>
                                                <option value="twilio" {{ ($settings['gateway'] ?? '') === 'twilio' ? 'selected' : '' }}>Twilio</option>
                                                <option value="log"    {{ ($settings['gateway'] ?? '') === 'log'    ? 'selected' : '' }}>Log Only (Dev/Staging)</option>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ __('labels.otp_length') }}</label>
                                                <input type="number" class="form-control" name="otp_length"
                                                       min="4" max="8"
                                                       value="{{ $settings['otp_length'] ?? 6 }}">
                                                <small class="form-hint">{{ __('messages.otp_length_hint') }}</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ __('labels.otp_expiry_minutes') }}</label>
                                                <input type="number" class="form-control" name="otp_expiry_minutes"
                                                       min="1" max="60"
                                                       value="{{ $settings['otp_expiry_minutes'] ?? 10 }}">
                                                <small class="form-hint">{{ __('messages.otp_expiry_hint') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- MSG91 --}}
                                <div class="card mb-4" id="pills-msg91">
                                    <div class="card-header">
                                        <h4 class="card-title">MSG91 <span class="badge bg-blue-lt ms-1">India</span></h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info mb-3">
                                            <div class="d-flex">
                                                <div>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                                </div>
                                                <div>
                                                    @php
                                                        $msg91Hint = __('messages.msg91_hint') ?: 'Get your Auth Key and create an OTP template at msg91.com. Set the OTP variable name to {{otp}} in your template.';
                                                    @endphp
                                                    {{ $msg91Hint }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.msg91_auth_key') }}</label>
                                            <input type="text" class="form-control" name="msg91_auth_key"
                                                   placeholder="XXXXXX..."
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? \Illuminate\Support\Str::mask(($settings['msg91_auth_key'] ?? ''), '*', 4) : ($settings['msg91_auth_key'] ?? '') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.msg91_template_id') }}</label>
                                            <input type="text" class="form-control" name="msg91_template_id"
                                                   placeholder="6XXXXXXXXXXXXXXXXXXXXXXXXX"
                                                   value="{{ $settings['msg91_template_id'] ?? '' }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.msg91_sender_id') }}</label>
                                            <input type="text" class="form-control" name="msg91_sender_id"
                                                   placeholder="LCOMRC" maxlength="6"
                                                   value="{{ $settings['msg91_sender_id'] ?? '' }}">
                                            <small class="form-hint">6-character DLT registered sender ID</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Twilio --}}
                                <div class="card mb-4" id="pills-twilio">
                                    <div class="card-header">
                                        <h4 class="card-title">Twilio</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.twilio_account_sid') }}</label>
                                            <input type="text" class="form-control" name="twilio_account_sid"
                                                   placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? \Illuminate\Support\Str::mask(($settings['twilio_account_sid'] ?? ''), '*', 4) : ($settings['twilio_account_sid'] ?? '') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.twilio_auth_token') }}</label>
                                            <input type="password" class="form-control" name="twilio_auth_token"
                                                   placeholder="••••••••"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? '' : ($settings['twilio_auth_token'] ?? '') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.twilio_from_number') }}</label>
                                            <input type="text" class="form-control" name="twilio_from_number"
                                                   placeholder="+1XXXXXXXXXX"
                                                   value="{{ $settings['twilio_from_number'] ?? '' }}">
                                            <small class="form-hint">E.164 format, e.g. +14155552671</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'sms'])
                                            <button type="submit" class="btn btn-primary ms-auto">{{ __('labels.submit') }}</button>
                                        @endcan
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
