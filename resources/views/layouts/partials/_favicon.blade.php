@php
    $faviconUrl = !empty($systemSettings['favicon'])
        ? $systemSettings['favicon']
        : asset('logos/hyper-local-favicon.png');

    $faviconPath = strtolower((string) (parse_url($faviconUrl, PHP_URL_PATH) ?? ''));

    $faviconType = match (true) {
        str_ends_with($faviconPath, '.png') => 'image/png',
        str_ends_with($faviconPath, '.svg') => 'image/svg+xml',
        str_ends_with($faviconPath, '.webp') => 'image/webp',
        str_ends_with($faviconPath, '.jpg'), str_ends_with($faviconPath, '.jpeg') => 'image/jpeg',
        default => 'image/x-icon',
    };

    $faviconHref = $faviconUrl . (str_contains($faviconUrl, '?') ? '&' : '?') . 'v=' . md5($faviconUrl);
@endphp

<link rel="icon" type="{{ $faviconType }}" href="{{ $faviconHref }}">
<link rel="shortcut icon" type="{{ $faviconType }}" href="{{ $faviconHref }}">
<link rel="apple-touch-icon" href="{{ $faviconHref }}">