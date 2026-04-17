<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f9; color: #374151; font-size: 15px; line-height: 1.6; }
        .wrapper { max-width: 620px; margin: 32px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .header { background-color: #2563eb; padding: 28px 32px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -.3px; }
        .header p { color: rgba(255,255,255,.8); font-size: 13px; margin-top: 4px; }
        .body { padding: 32px; }
        .body h2 { font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 8px; }
        .body p { color: #4b5563; margin-bottom: 16px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .badge-blue    { background: #dbeafe; color: #1e40af; }
        .badge-green   { background: #d1fae5; color: #065f46; }
        .badge-orange  { background: #ffedd5; color: #9a3412; }
        .badge-red     { background: #fee2e2; color: #991b1b; }
        .badge-gray    { background: #f3f4f6; color: #374151; }
        .table-wrap { border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        table thead { background-color: #f9fafb; }
        table th, table td { padding: 10px 14px; text-align: left; font-size: 13px; border-bottom: 1px solid #e5e7eb; }
        table th { font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 11px; letter-spacing: .5px; }
        table tr:last-child td { border-bottom: none; }
        .totals { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .totals .row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px; }
        .totals .row.grand { font-weight: 700; font-size: 16px; border-top: 1px solid #d1d5db; margin-top: 8px; padding-top: 10px; }
        .btn { display: inline-block; background: #2563eb; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-size: 14px; font-weight: 600; margin: 8px 0; }
        .address-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px 18px; margin: 16px 0; font-size: 13px; line-height: 1.8; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .footer { background: #f9fafb; padding: 20px 32px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { color: #9ca3af; font-size: 12px; line-height: 1.7; }
    </style>
</head>
<body>
@php
    $systemSettings = \App\Models\Setting::find(\App\Enums\SettingTypeEnum::SYSTEM())?->value ?? [];
    $appName = $systemSettings['appName'] ?? config('app.name');
    $rawLogo = (string)($systemSettings['logo'] ?? '');
    $logoUrl = asset('logos/hyper-local-logo.png');
    $logoPath = public_path('logos/hyper-local-logo.png');

    if ($rawLogo !== '') {
        if (str_starts_with($rawLogo, 'http://') || str_starts_with($rawLogo, 'https://') || str_starts_with($rawLogo, 'data:')) {
            $logoUrl = $rawLogo;
        } else {
            $normalizedLogo = ltrim($rawLogo, '/');
            $storageRelativeLogo = str_starts_with($normalizedLogo, 'storage/')
                ? ltrim(substr($normalizedLogo, strlen('storage/')), '/')
                : $normalizedLogo;

            $logoUrl = url('storage/' . $storageRelativeLogo);

            $candidateStoragePath = storage_path('app/public/' . $storageRelativeLogo);
            $candidatePublicPath = public_path($normalizedLogo);

            if (is_file($candidateStoragePath)) {
                $logoPath = $candidateStoragePath;
            } elseif (is_file($candidatePublicPath)) {
                $logoPath = $candidatePublicPath;
            }
        }
    }
@endphp
<div class="wrapper">
    <div class="header">
        <img src="{{ (isset($message) && is_file($logoPath)) ? $message->embed($logoPath) : $logoUrl }}" alt="{{ $appName }}" width="72" height="72" style="display:block; margin:0 auto 12px; border:0; background:#ffffff; border-radius:12px; padding:6px; object-fit:contain;">
        <h1>{{ $appName }}</h1>
        <p>@yield('header-sub', 'Transactional Email')</p>
    </div>
    <div class="body">
        @yield('content')
    </div>
    <div class="footer">
        <p>
            You are receiving this email because you have an account with {{ $appName }}.<br>
            &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
        </p>
    </div>
</div>
</body>
</html>
