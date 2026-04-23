@extends('layouts.admin.app',  ['page' => $menuAdmin['settings']['active'] ?? ""])

@section('title', __('labels.settings'))

@section('header_data')
    @php
        $page_title = __('labels.settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-body">
        <div class="row row-cards">
            @can('viewSetting', [\App\Models\Setting::class, 'system'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{route('admin.settings.show', ['setting' => 'system'])}}" class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-adjustments-horizontal">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M14 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                                    <path d="M4 6l8 0"/>
                                    <path d="M16 6l4 0"/>
                                    <path d="M8 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                                    <path d="M4 12l2 0"/>
                                    <path d="M10 12l10 0"/>
                                    <path d="M17 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                                    <path d="M4 18l11 0"/>
                                    <path d="M19 18l1 0"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">{{ __('labels.system.title') }}</h3>
                            <p class="text-secondary">
                                {{ __('labels.system.description') }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @can('viewSetting', [\App\Models\Setting::class, 'app'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{route('admin.settings.show', ['setting' => 'app'])}}" class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-device-mobile">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path
                                        d="M6 5a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-14z"/>
                                    <path d="M11 4h2"/>
                                    <path d="M12 17v.01"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">{{ __('labels.app.title') }}</h3>
                            <p class="text-secondary">
                                {{ __('labels.app.description') }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @can('viewSetting', [\App\Models\Setting::class, 'storage'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{route('admin.settings.show', ['setting' => 'storage'])}}" class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-folder">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path
                                        d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">{{ __('labels.storage.title') }}</h3>
                            <p class="text-secondary">
                                {{ __('labels.storage.description') }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @can('viewSetting', [\App\Models\Setting::class, 'authentication'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{route('admin.settings.show', ['setting' => 'authentication'])}}" class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-brand-auth0">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 14.5l-5.5 3.5l2 -6l-4.5 -4h6l2 -5l2 5h6l-4.5 4l2 6z"/>
                                    <path
                                        d="M20.507 8.872l-2.01 -5.872h-12.994l-2.009 5.872c-1.242 3.593 -.135 7.094 3.249 9.407l5.257 3.721l5.257 -3.721c3.385 -2.313 4.49 -5.814 3.25 -9.407z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">{{ __('labels.authentication.title') }}</h3>
                            <p class="text-secondary">
                                {{ __('labels.authentication.description') }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @can('viewSetting', [\App\Models\Setting::class, 'email'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{route('admin.settings.show', ['setting' => 'email'])}}" class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-mailbox">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M10 21v-6.5a3.5 3.5 0 0 0 -7 0v6.5h18v-6a4 4 0 0 0 -4 -4h-10.5"/>
                                    <path d="M12 11v-8h4l2 2l-2 2h-4"/>
                                    <path d="M6 15h1"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">{{ __('labels.email_title') }}</h3>
                            <p class="text-secondary">
                                {{ __('labels.email_description') }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @can('viewSetting', [\App\Models\Setting::class, 'payment'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{route('admin.settings.show', ['setting' => 'payment'])}}" class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-wallet">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path
                                        d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12"/>
                                    <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">{{ __('labels.payment.title') }}</h3>
                            <p class="text-secondary">
                                {{ __('labels.payment.description') }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @can('viewSetting', [\App\Models\Setting::class, 'notification'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{route('admin.settings.show', ['setting' => 'notification'])}}"
                       class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-bell-ringing">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path
                                        d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/>
                                    <path d="M9 17v1a3 3 0 0 0 6 0v-1"/>
                                    <path d="M21 6.727a11.05 11.05 0 0 0 -2.794 -3.727"/>
                                    <path d="M3 6.727a11.05 11.05 0 0 1 2.792 -3.727"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">{{ __('labels.notification.title') }}</h3>
                            <p class="text-secondary">
                                {{ __('labels.notification.description') }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @can('viewSetting', [\App\Models\Setting::class, 'delivery_boy'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{route('admin.settings.show', ['setting' => 'delivery_boy'])}}" class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-truck-delivery">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                                    <path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                                    <path d="M5 17h-2v-4m-1 -8h11v12m0 -7h6l2 2v3h-2"/>
                                    <path d="M3 9l4 0"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">{{ __('labels.delivery_boy_section.title') }}</h3>
                            <p class="text-secondary">
                                {{ __('labels.delivery_boy_section.description') }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @can('viewSetting', [\App\Models\Setting::class, 'home_general_settings'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{route('admin.settings.show', ['setting' => 'home_general_settings'])}}"
                       class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-home-2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M5 12l-2 0l9 -9l9 9l-2 0"/>
                                    <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/>
                                    <path d="M10 12h4v4h-4z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">{{ __('labels.home_general_settings') }}</h3>
                            <p class="text-secondary">
                                {{ __('labels.home_general_settings_description') }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @can('viewSetting', [\App\Models\Setting::class, 'seo_advanced'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{ route('admin.settings.show', ['setting' => 'seo_advanced']) }}" class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-sitemap">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M3 15m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"/>
                                    <path d="M15 15m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"/>
                                    <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"/>
                                    <path d="M6 15v-1a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v1"/>
                                    <path d="M12 9l0 3"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">SEO Advanced</h3>
                            <p class="text-secondary">
                                Manage sitemap custom URLs, exclusions, and extra robots.txt disallow rules.
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @can('viewSetting', [\App\Models\Setting::class, 'system'])
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <a href="{{ route('admin.system-logs.index') }}" class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-file-alert">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    <path d="M12 17l.01 0"/>
                                    <path d="M12 11l0 3"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title">System Logs</h3>
                            <p class="text-secondary">
                                Inspect Laravel log files, review recent errors, and clear a log after confirmation.
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
            @if(auth('admin')->user()?->hasRole(\App\Enums\DefaultSystemRolesEnum::SUPER_ADMIN()))
            <div class="col-md-6 col-lg-4">
                <div class="card border-danger">
                    <a href="{{ route('admin.data-management.index') }}" class="card-link">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-danger">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-database-x">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M4 6c0 1.657 3.582 3 8 3s8 -1.343 8 -3s-3.582 -3 -8 -3s-8 1.343 -8 3"/>
                                    <path d="M4 6v6c0 1.657 3.582 3 8 3c1.14 0 2.218 -.1 3.2 -.278"/>
                                    <path d="M20 12v-6"/>
                                    <path d="M4 12v6c0 1.657 3.582 3 8 3c.357 0 .711 -.01 1.057 -.03"/>
                                    <path d="M22 22l-5 -5"/>
                                    <path d="M17 22l5 -5"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-body py-5">
                            <h3 class="card-title text-danger">Data Management</h3>
                            <p class="text-secondary">
                                Truncate orders, carts, transactions, and payment records from the database.
                            </p>
                        </div>
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
