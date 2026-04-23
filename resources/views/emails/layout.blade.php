<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body { margin: 0; padding: 0; background: #f0f4f8; }
        table { border-collapse: collapse; }
        .outer { width: 100%; background: #f0f4f8; }
        .container { width: 680px; max-width: 680px; background: #ffffff; border: 1px solid #cdddf0; }
        .header { background: linear-gradient(135deg, #0f2f5f 0%, #1f4f8a 45%, #27a567 100%); }
        .title { font-family: Arial, sans-serif; font-size: 24px; font-weight: 700; color: #ffffff; }
        .sub { font-family: Arial, sans-serif; font-size: 14px; color: rgba(255,255,255,0.80); }
        .body-wrap { padding: 24px 28px; }
        .body-wrap h2 { font-family: Arial, sans-serif; font-size: 22px; color: #0f2f5f; font-weight: 700; margin: 0 0 8px; }
        .body-wrap p { font-family: Arial, sans-serif; font-size: 14px; color: #3e5677; line-height: 22px; margin: 0 0 16px; }
        .meta { background: #f4f8ff; border: 1px solid #cdddf0; border-radius: 8px; padding: 12px 14px; margin: 16px 0; font-family: Arial, sans-serif; font-size: 14px; color: #3e5677; line-height: 22px; }
        .otp-card { margin: 20px 0; padding: 18px 20px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; text-align: center; }
        .otp-card.warm { background: #fef3c7; border-color: #fcd34d; }
        .otp-label { font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #1d4ed8; margin-bottom: 8px; font-family: Arial, sans-serif; }
        .otp-card.warm .otp-label { color: #92400e; }
        .otp-value { font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #111827; font-family: Arial, sans-serif; }
        .note-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 18px; margin: 20px 0; font-family: Arial, sans-serif; font-size: 14px; color: #3e5677; line-height: 22px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; letter-spacing: .4px; font-family: Arial, sans-serif; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-green { background: #dff5eb; color: #1a6b3a; }
        .badge-orange { background: #ffedd5; color: #9a3412; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-gray { background: #f3f4f6; color: #374151; }
        .table-wrap { border: 1px solid #cdddf0; border-radius: 8px; overflow: hidden; margin: 16px 0; }
        .table-wrap table { width: 100%; }
        .table-wrap thead { background: #eef4fb; }
        .table-wrap th, .table-wrap td { padding: 10px 12px; text-align: left; font-family: Arial, sans-serif; border-bottom: 1px solid #cdddf0; }
        .table-wrap th { color: #445e80; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
        .table-wrap td { color: #1f3d60; font-size: 13px; }
        .table-wrap tr:last-child td { border-bottom: none; }
        .totals { background: #f6fbf8; border: 1px solid #c3e6d0; border-radius: 8px; padding: 10px 14px 12px; margin: 16px 0; }
        .totals .row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; font-family: Arial, sans-serif; font-size: 14px; color: #3e5677; }
        .totals .row.grand { border-top: 1px solid #c3e6d0; margin-top: 8px; padding-top: 10px; font-size: 18px; font-weight: 700; color: #0f2f5f; }
        .address-box { background: #f4fbf7; border: 1px solid #c3e6d0; border-radius: 8px; padding: 12px 14px; margin: 16px 0; font-family: Arial, sans-serif; font-size: 14px; color: #3e5677; line-height: 22px; }
        .footer { background: #f4f8ff; border-top: 1px solid #cdddf0; font-family: Arial, sans-serif; font-size: 12px; color: #7a8fa8; }
        .footer p { margin: 0; line-height: 20px; }
        .divider { border: none; border-top: 1px solid #cdddf0; margin: 24px 0; }
        .muted { color: #6b7280; font-size: 13px; }
        @media only screen and (max-width: 700px) {
            .container { width: 100% !important; }
            .mobile-pad { padding-left: 16px !important; padding-right: 16px !important; }
            .body-wrap { padding-left: 16px !important; padding-right: 16px !important; }
        }
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
<table role="presentation" class="outer" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding:24px 10px;">
            <table role="presentation" class="container" width="680" cellpadding="0" cellspacing="0" style="border-radius:12px; overflow:hidden;">
                <tr>
                    <td class="header mobile-pad" align="center" style="padding:24px 28px;">
                        <img src="{{ (isset($message) && is_file($logoPath)) ? $message->embed($logoPath) : $logoUrl }}" alt="{{ $appName }}" width="84" height="84" style="display:block; border:0; background:#ffffff; border-radius:12px; padding:6px; object-fit:contain;">
                        <div class="title" style="padding-top:10px;">{{ $appName }}</div>
                        <div class="sub" style="padding-top:6px;">@yield('header-sub', 'Transactional Email')</div>
                    </td>
                </tr>
                <tr>
                    <td class="body-wrap mobile-pad">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td class="footer" align="center" style="padding:16px;">
                        <p>
                            You are receiving this email because you have an account with {{ $appName }}.<br>
                            &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
