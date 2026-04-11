@php use App\Enums\Payment\PaymentTypeEnum; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['payment']['sub_active'] ?? "" ])

@section('title', __('labels.payment_settings'))

@section('header_data')
    @php
        $page_title = __('labels.payment_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.payment_settings'), 'url' => null],
    ];
@endphp

@php
    $isUnlocked = (bool) ($paymentSettingsUnlocked ?? false);
    $maskValue = static function (?string $value): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $length = strlen($value);
        if ($length <= 6) {
            return str_repeat('•', $length);
        }
        return substr($value, 0, 3) . str_repeat('•', max(0, $length - 6)) . substr($value, -3);
    };
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.payment_settings') }}</h2>
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
                            <a class="nav-link" href="#pills-razorpay">{{ __('labels.razorpay_payment') }}</a>
                            <a class="nav-link" href="#pills-easepay">Easebuzz</a>
                            <a class="nav-link" href="#pills-cod">{{ __('labels.cash_on_delivery') }}</a>
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="payment">

                                <div class="card mb-4 border-{{ $isUnlocked ? 'green' : 'orange' }}">
                                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div>
                                            <h4 class="card-title mb-1">Sensitive Payment Credentials</h4>
                                            @if($isUnlocked)
                                                <p class="text-muted mb-0">Editing is temporarily unlocked for {{ $paymentUnlockTtlMinutes ?? 10 }} minutes.</p>
                                            @else
                                                <p class="text-muted mb-0">Credentials are masked and locked by default. Verify password + authenticator code to edit.</p>
                                            @endif
                                        </div>
                                        <div class="d-flex gap-2">
                                            @if($isUnlocked)
                                                <button type="button" class="btn btn-outline-danger" id="payment-lock-btn">Lock Now</button>
                                            @else
                                                <button type="button" class="btn btn-primary" id="payment-unlock-btn">Unlock to Edit</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- ============================================================ --}}
                                {{-- Razorpay --}}
                                {{-- ============================================================ --}}
                                <div class="card mb-4" id="pills-razorpay">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.razorpay_payment') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_razorpay_payment') }}</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="razorpayPayment" value="1" {{ isset($settings['razorpayPayment']) && $settings['razorpayPayment'] ? 'checked' : '' }} />
                                                    </label>
                                                </span>
                                            </label>
                                        </div>
                                        <div id="razorpayFields"
                                             style="{{ isset($settings['razorpayPayment']) && $settings['razorpayPayment'] ? 'display: block;' : 'display: none;' }}">
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.razorpay_payment_mode') }}</label>
                                                <select class="form-select sensitive-field" name="razorpayPaymentMode">
                                                    <option value="" disabled {{ !isset($settings['razorpayPaymentMode']) ? 'selected' : '' }}>{{ __('labels.razorpay_payment_mode_placeholder') }}</option>
                                                    <option value="test" {{ isset($settings['razorpayPaymentMode']) && $settings['razorpayPaymentMode'] === 'test' ? 'selected' : '' }}>Test</option>
                                                    <option value="live" {{ isset($settings['razorpayPaymentMode']) && $settings['razorpayPaymentMode'] === 'live' ? 'selected' : '' }}>Live</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.razorpay_key_id') }}</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control sensitive-field revealable-field" id="razorpayKeyId" name="razorpayKeyId"
                                                           placeholder="{{ __('labels.razorpay_key_id_placeholder') }}"
                                                           value="{{ $isUnlocked ? ($settings['razorpayKeyId'] ?? '') : $maskValue($settings['razorpayKeyId'] ?? '') }}"/>
                                                    <button class="btn btn-outline-secondary toggle-visibility-btn" type="button" data-target="#razorpayKeyId" title="Show/Hide">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="18" height="18" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                                                            <path d="M22 12c-2.5 4 -5.5 6 -10 6s-7.5 -2 -10 -6c2.5 -4 5.5 -6 10 -6s7.5 2 10 6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.razorpay_secret_key') }}</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control sensitive-field revealable-field" id="razorpaySecretKey" name="razorpaySecretKey"
                                                           placeholder="{{ __('labels.razorpay_secret_key_placeholder') }}"
                                                           value="{{ $isUnlocked ? ($settings['razorpaySecretKey'] ?? '') : $maskValue($settings['razorpaySecretKey'] ?? '') }}"/>
                                                    <button class="btn btn-outline-secondary toggle-visibility-btn" type="button" data-target="#razorpaySecretKey" title="Show/Hide">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="18" height="18" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                                                            <path d="M22 12c-2.5 4 -5.5 6 -10 6s-7.5 -2 -10 -6c2.5 -4 5.5 -6 10 -6s7.5 2 10 6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.razorpay_webhook_secret') }}</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control sensitive-field revealable-field" id="razorpayWebhookSecret" name="razorpayWebhookSecret"
                                                           placeholder="{{ __('labels.razorpay_webhook_secret_placeholder') }}"
                                                           value="{{ $isUnlocked ? ($settings['razorpayWebhookSecret'] ?? '') : $maskValue($settings['razorpayWebhookSecret'] ?? '') }}"/>
                                                    <button class="btn btn-outline-secondary toggle-visibility-btn" type="button" data-target="#razorpayWebhookSecret" title="Show/Hide">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="18" height="18" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                                                            <path d="M22 12c-2.5 4 -5.5 6 -10 6s-7.5 -2 -10 -6c2.5 -4 5.5 -6 10 -6s7.5 2 10 6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ============================================================ --}}
                                {{-- Easebuzz (Easepay) --}}
                                {{-- ============================================================ --}}
                                <div class="card mb-4" id="pills-easepay">
                                    <div class="card-header">
                                        <h4 class="card-title">Easebuzz <span class="badge bg-blue-lt ms-1">India</span></h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">Enable Easebuzz Payment</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="easepayPayment" value="1"
                                                               {{ isset($settings['easepayPayment']) && $settings['easepayPayment'] ? 'checked' : '' }} />
                                                    </label>
                                                </span>
                                            </label>
                                        </div>
                                        <div id="easepayFields"
                                             style="{{ isset($settings['easepayPayment']) && $settings['easepayPayment'] ? 'display:block;' : 'display:none;' }}">
                                            <div class="mb-3">
                                                <label class="form-label required">Payment Mode</label>
                                                <select class="form-select sensitive-field" name="easepayPaymentMode">
                                                    <option value="" disabled {{ !isset($settings['easepayPaymentMode']) ? 'selected' : '' }}>Select mode</option>
                                                    <option value="test" {{ ($settings['easepayPaymentMode'] ?? '') === 'test' ? 'selected' : '' }}>Test</option>
                                                    <option value="live" {{ ($settings['easepayPaymentMode'] ?? '') === 'live' ? 'selected' : '' }}>Live</option>
                                                </select>
                                                <small class="form-hint">Test uses <code>testpay.easebuzz.in</code>; Live uses <code>pay.easebuzz.in</code></small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">Merchant Key</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control sensitive-field revealable-field" id="easepayMerchantKey" name="easepayMerchantKey"
                                                           placeholder="XXXXXXXXXX"
                                                           value="{{ $isUnlocked ? ($settings['easepayMerchantKey'] ?? '') : $maskValue($settings['easepayMerchantKey'] ?? '') }}">
                                                    <button class="btn btn-outline-secondary toggle-visibility-btn" type="button" data-target="#easepayMerchantKey" title="Show/Hide">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="18" height="18" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                                                            <path d="M22 12c-2.5 4 -5.5 6 -10 6s-7.5 -2 -10 -6c2.5 -4 5.5 -6 10 -6s7.5 2 10 6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <small class="form-hint">Available in your Easebuzz merchant dashboard.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">Merchant Salt</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control sensitive-field revealable-field" id="easepayMerchantSalt" name="easepayMerchantSalt"
                                                           placeholder="••••••••••••••••"
                                                           value="{{ $isUnlocked ? ($settings['easepayMerchantSalt'] ?? '') : $maskValue($settings['easepayMerchantSalt'] ?? '') }}">
                                                    <button class="btn btn-outline-secondary toggle-visibility-btn" type="button" data-target="#easepayMerchantSalt" title="Show/Hide">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="18" height="18" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                                                            <path d="M22 12c-2.5 4 -5.5 6 -10 6s-7.5 -2 -10 -6c2.5 -4 5.5 -6 10 -6s7.5 2 10 6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <small class="form-hint">Used to generate hash signatures for all API calls.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Webhook Secret</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control sensitive-field revealable-field" id="easepayWebhookSecret" name="easepayWebhookSecret"
                                                           placeholder="optional"
                                                           value="{{ $isUnlocked ? ($settings['easepayWebhookSecret'] ?? '') : $maskValue($settings['easepayWebhookSecret'] ?? '') }}">
                                                    <button class="btn btn-outline-secondary toggle-visibility-btn" type="button" data-target="#easepayWebhookSecret" title="Show/Hide">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="18" height="18" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                                                            <path d="M22 12c-2.5 4 -5.5 6 -10 6s-7.5 -2 -10 -6c2.5 -4 5.5 -6 10 -6s7.5 2 10 6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <small class="form-hint">
                                                    Webhook URL: <code>{{ url('/api/easepay/webhook') }}</code>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ============================================================ --}}
                                {{-- Cash on Delivery --}}
                                {{-- ============================================================ --}}
                                <div class="card mb-4" id="pills-cod">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.cash_on_delivery') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_cash_on_delivery') }}</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="{{PaymentTypeEnum::COD()}}"
                                                               value="1" {{ isset($settings['cod']) && $settings['cod'] ? 'checked' : '' }} />
                                                    </label>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'payment'])
                                            <button type="submit"
                                                    class="btn btn-primary ms-auto" id="payment-submit-btn">{{ __('labels.submit') }}</button>
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

    <!-- END PAGE BODY -->
@endsection

@push('script')
    <script>
        (function () {
            const razorpayToggle = document.querySelector('input[name="razorpayPayment"]');
            const razorpayFields = document.getElementById('razorpayFields');
            const easepayToggle  = document.querySelector('input[name="easepayPayment"]');
            const easepayFields  = document.getElementById('easepayFields');
            const sensitiveFields = document.querySelectorAll('.sensitive-field');
            const visibilityButtons = document.querySelectorAll('.toggle-visibility-btn');
            const paymentSubmitBtn = document.getElementById('payment-submit-btn');
            const paymentUnlockBtn = document.getElementById('payment-unlock-btn');
            const paymentLockBtn = document.getElementById('payment-lock-btn');
            const unlocked = @json($isUnlocked);

            const toggleRazorpayFields = () => {
                razorpayFields.style.display = razorpayToggle.checked ? 'block' : 'none';
            };
            const toggleEasepayFields = () => {
                easepayFields.style.display = easepayToggle.checked ? 'block' : 'none';
            };
            const applyLockState = (isUnlockedState) => {
                sensitiveFields.forEach((field) => {
                    field.readOnly = !isUnlockedState && field.tagName !== 'SELECT';
                    if (field.tagName === 'SELECT') {
                        field.disabled = !isUnlockedState;
                    }
                });
                if (razorpayToggle) {
                    razorpayToggle.disabled = !isUnlockedState;
                }
                if (easepayToggle) {
                    easepayToggle.disabled = !isUnlockedState;
                }
                if (paymentSubmitBtn) {
                    paymentSubmitBtn.disabled = !isUnlockedState;
                }
            };

            const unlockPaymentSettings = async () => {
                const password = window.prompt('Enter your admin password');
                if (!password) return;
                const totp = window.prompt('Enter 6-digit authenticator code');
                if (!totp) return;

                const response = await fetch(@json(route('admin.settings.payment.unlock')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        password: password,
                        totp_code: totp
                    })
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok || payload.success === false) {
                    alert(payload.message || 'Failed to unlock payment settings.');
                    return;
                }
                window.location.reload();
            };

            const lockPaymentSettings = async () => {
                await fetch(@json(route('admin.settings.payment.lock')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });
                window.location.reload();
            };

            visibilityButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const selector = button.getAttribute('data-target');
                    const input = selector ? document.querySelector(selector) : null;
                    if (!input) return;
                    input.type = input.type === 'password' ? 'text' : 'password';
                });
            });

            razorpayToggle.addEventListener('change', toggleRazorpayFields);
            easepayToggle.addEventListener('change', toggleEasepayFields);
            if (paymentUnlockBtn) {
                paymentUnlockBtn.addEventListener('click', unlockPaymentSettings);
            }
            if (paymentLockBtn) {
                paymentLockBtn.addEventListener('click', lockPaymentSettings);
            }
            applyLockState(unlocked);
            toggleRazorpayFields();
            toggleEasepayFields();
        })();
    </script>
@endpush
