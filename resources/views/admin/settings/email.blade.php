@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['email']['sub_active'] ?? "" ])

@section('title', __('labels.email_settings'))

@section('header_data')
    @php
        $page_title = __('labels.email_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $emailDemoModeEnabled = !empty($settings['email_demo_mode']);
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.email_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.email_settings') }}</h2>
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
                            <a class="nav-link" href="#pills-smtp">{{ __('labels.smtp') }}</a>
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="email">
                                {{-- Email Demo Mode --}}
                                <div class="card mb-4 border-warning" id="pills-demo-mode">
                                    <div class="card-header bg-warning-lt">
                                        <h4 class="card-title text-warning">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M12 9v4"/>
                                                <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/>
                                                <path d="M12 16h.01"/>
                                            </svg>
                                            Email Demo Mode
                                        </h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning mb-3">
                                            <strong>Development / Testing Only</strong> — When enabled, all outgoing emails are suppressed. No mail is sent via SMTP. Disable before going live.
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-check form-switch">
                                                <input class="form-check-input" id="email-demo-mode-toggle" type="checkbox"
                                                       name="email_demo_mode" value="1"
                                                       {{ !empty($settings['email_demo_mode']) ? 'checked' : '' }}>
                                                <span class="form-check-label fw-medium">Enable Email Demo Mode</span>
                                            </label>
                                            <small class="form-hint d-block mt-1">
                                                When ON: emails are skipped entirely — no SMTP connection is made.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4" id="pills-smtp">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.smtp') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.smtp_host') }}</label>
                                            <input type="text" class="form-control smtp-required-field" name="smtpHost"
                                                   placeholder="{{ __('labels.smtp_host_placeholder') }}"
                                                   value="{{ $settings['smtpHost'] ?? '' }}" {{ $emailDemoModeEnabled ? '' : 'required' }}/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.smtp_port') }}</label>
                                            <input type="number" class="form-control smtp-required-field" name="smtpPort"
                                                   placeholder="{{ __('labels.smtp_port_placeholder') }}"
                                                   value="{{ $settings['smtpPort'] ?? '' }}" {{ $emailDemoModeEnabled ? '' : 'required' }}/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.smtp_username') }}</label>
                                            <input type="text" class="form-control smtp-required-field" name="smtpUsername"
                                                   placeholder="{{ __('labels.smtp_username_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['smtpUsername'] ?? ($settings['smtpEmail'] ?? '****')), '****', 3, 8) : ($settings['smtpUsername'] ?? ($settings['smtpEmail'] ?? '')) }}" {{ $emailDemoModeEnabled ? '' : 'required' }}/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.smtp_from_email') }}</label>
                                            <input type="email" class="form-control smtp-required-field" name="smtpFromEmail"
                                                   placeholder="{{ __('labels.smtp_from_email_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['smtpFromEmail'] ?? ($settings['smtpEmail'] ?? '****')), '****', 3, 8) : ($settings['smtpFromEmail'] ?? ($settings['smtpEmail'] ?? '')) }}" {{ $emailDemoModeEnabled ? '' : 'required' }}/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.smtp_password') }}</label>
                                            <input type="password" class="form-control smtp-required-field" name="smtpPassword"
                                                   placeholder="{{ __('labels.smtp_password_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['smtpPassword'] ?? '****'), '****', 3, 8) : ($settings['smtpPassword'] ?? '') }}" {{ $emailDemoModeEnabled ? '' : 'required' }}/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.smtp_encryption') }}</label>
                                            <select class="form-select smtp-required-field" name="smtpEncryption" {{ $emailDemoModeEnabled ? '' : 'required' }}>
                                                <option value=""
                                                        disabled {{ !isset($settings['smtpEncryption']) ? 'selected' : '' }}>{{ __('labels.smtp_encryption_placeholder') }}</option>
                                                <option
                                                    value="tls" {{ isset($settings['smtpEncryption']) && $settings['smtpEncryption'] === 'tls' ? 'selected' : '' }}>
                                                    TLS
                                                </option>
                                                <option
                                                    value="ssl" {{ isset($settings['smtpEncryption']) && $settings['smtpEncryption'] === 'ssl' ? 'selected' : '' }}>
                                                    SSL
                                                </option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.smtp_content_type') }}</label>
                                            <select class="form-select smtp-required-field" name="smtpContentType" {{ $emailDemoModeEnabled ? '' : 'required' }}>
                                                <option value=""
                                                        disabled {{ !isset($settings['smtpContentType']) ? 'selected' : '' }}>{{ __('labels.smtp_content_type_placeholder') }}</option>
                                                <option
                                                    value="text" {{ isset($settings['smtpContentType']) && $settings['smtpContentType'] === 'text' ? 'selected' : '' }}>
                                                    Text
                                                </option>
                                                <option
                                                    value="html" {{ isset($settings['smtpContentType']) && $settings['smtpContentType'] === 'html' ? 'selected' : '' }}>
                                                    HTML
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'email'])
                                            <button type="submit"
                                                    class="btn btn-primary ms-auto">{{ __('labels.submit') }}</button>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const demoToggle = document.getElementById('email-demo-mode-toggle');
            const smtpFields = document.querySelectorAll('.smtp-required-field');

            if (!demoToggle || smtpFields.length === 0) {
                return;
            }

            const syncSmtpRequiredState = () => {
                const shouldRequireSmtp = !demoToggle.checked;
                smtpFields.forEach((field) => {
                    field.required = shouldRequireSmtp;
                });
            };

            syncSmtpRequiredState();
            demoToggle.addEventListener('change', syncSmtpRequiredState);
        });
    </script>
@endpush
