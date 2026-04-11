@extends('layouts.admin.app', ['page' => '', 'sub_page' => ''])

@section('title', '404 - Page Not Found')

@section('header_data')
    @php
        $page_title = 'Page Not Found';
        $page_pretitle = 'Error 404';
    @endphp
@endsection

@section('admin-content')
    <style>
        .error-404-wrap {
            position: relative;
            overflow: hidden;
            border: 1px solid #d8e1ee;
            border-radius: 14px;
            background:
                radial-gradient(1200px 260px at 20% -80px, rgba(16, 95, 173, 0.14), transparent 45%),
                radial-gradient(1200px 260px at 90% -80px, rgba(70, 163, 59, 0.13), transparent 45%),
                linear-gradient(135deg, #f6f9ff 0%, #f8fbf6 100%);
            box-shadow: 0 10px 32px rgba(21, 62, 104, 0.08);
        }

        .error-404-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border-radius: 999px;
            padding: 8px 14px;
            background: rgba(16, 95, 173, 0.12);
            color: #0f4a89;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .error-404-code {
            font-size: clamp(64px, 11vw, 122px);
            line-height: .9;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.03em;
            background: linear-gradient(120deg, #0f4a89 0%, #176dc8 52%, #3d9b3c 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .error-404-title {
            margin: 6px 0 8px;
            color: #1d3046;
            font-weight: 700;
            font-size: 30px;
        }

        .error-404-subtitle {
            margin: 0;
            color: #4f647d;
            font-size: 16px;
            max-width: 560px;
        }

        .error-404-path {
            margin-top: 12px;
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 13px;
            color: #18446e;
            background: rgba(17, 97, 175, 0.08);
            border: 1px dashed rgba(17, 97, 175, 0.3);
        }

        .error-404-logo {
            max-height: 68px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(12, 66, 115, 0.2));
        }

        .error-404-right-mark {
            width: 120px;
            height: 120px;
            border-radius: 24px;
            display: grid;
            place-items: center;
            color: #1f5b97;
            background: linear-gradient(160deg, rgba(16, 95, 173, 0.16), rgba(70, 163, 59, 0.16));
            border: 1px solid rgba(31, 101, 174, 0.24);
        }

        .error-404-right-mark i {
            font-size: 56px;
        }
    </style>

    <div class="card error-404-wrap">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-column flex-lg-row align-items-start justify-content-between gap-4">
                <div>
                    <div class="error-404-badge mb-3">
                        <i class="ti ti-alert-triangle-filled"></i>
                        Admin Route Missing
                    </div>
                    <h1 class="error-404-code">404</h1>
                    <h2 class="error-404-title">This page does not exist</h2>
                    <p class="error-404-subtitle">
                        The URL you requested is not available in the admin panel. Please use the side menu or return to dashboard.
                    </p>
                    @if(!empty($requestedPath))
                        <div class="error-404-path">/{{ $requestedPath }}</div>
                    @endif
                    <div class="d-flex gap-2 flex-wrap mt-4">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                            <i class="ti ti-layout-dashboard me-1"></i> Go To Dashboard
                        </a>
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Go Back
                        </a>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-3">
                    <img class="error-404-logo"
                         src="{{ !empty($systemSettings['logo']) ? $systemSettings['logo'] : asset('logos/hyper-local-logo-white.png') }}"
                         alt="{{ $systemSettings['appName'] ?? config('app.name') }}">
                    <div class="error-404-right-mark">
                        <i class="ti ti-route-off"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
