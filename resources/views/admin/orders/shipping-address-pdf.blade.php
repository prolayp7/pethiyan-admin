<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shipping Address {{ $order->order_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            margin: 0;
            padding: 18px;
            font-size: 13px;
            background: #ffffff;
        }

        .slip {
            border: 1.5px solid #111827;
            padding: 16px 18px;
        }

        .row {
            width: 100%;
        }

        .row:after {
            content: "";
            display: table;
            clear: both;
        }

        .section {
            padding: 12px 0;
            border-bottom: 1px solid #d1d5db;
        }

        .section:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #4b5563;
            margin-bottom: 6px;
        }

        .order-id {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .to-name {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .mobile {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .address-line {
            font-size: 16px;
            line-height: 1.45;
        }

        .two-col-left {
            float: left;
            width: 64%;
        }

        .two-col-right {
            float: right;
            width: 32%;
            text-align: right;
        }

        .logo {
            max-width: 120px;
            max-height: 52px;
            margin-bottom: 10px;
        }

        .from-name {
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .from-address {
            font-size: 13px;
            line-height: 1.45;
            color: #374151;
            white-space: pre-line;
        }
    </style>
</head>
<body>
@php
    $resolvePdfImagePath = function ($candidate, $fallback = null) {
        $candidate = $candidate ?: $fallback;
        if (!$candidate) {
            return null;
        }

        if (filter_var($candidate, FILTER_VALIDATE_URL)) {
            $parsedPath = parse_url($candidate, PHP_URL_PATH);
            if ($parsedPath) {
                if (str_starts_with($parsedPath, '/storage/')) {
                    $storageFile = storage_path('app/public/' . ltrim(substr($parsedPath, strlen('/storage/')), '/'));
                    if (is_file($storageFile)) return $storageFile;
                }
                $publicFile = public_path(ltrim($parsedPath, '/'));
                if (is_file($publicFile)) return $publicFile;
            }
        }

        if (str_starts_with($candidate, 'storage/')) {
            $storageFile = storage_path('app/public/' . ltrim(substr($candidate, strlen('storage/')), '/'));
            if (is_file($storageFile)) return $storageFile;
        }

        $publicFile = public_path(ltrim($candidate, '/'));
        if (is_file($publicFile)) return $publicFile;

        return null;
    };

    $toDataUri = function (?string $filePath): ?string {
        if (!$filePath || !is_file($filePath)) return null;
        $mime = mime_content_type($filePath) ?: 'image/png';
        $raw = file_get_contents($filePath);
        if ($raw === false) return null;
        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    };

    $logoPath = $resolvePdfImagePath($systemSettings['logo'] ?? null, asset('logos/hyper-local-logo.png'));
    $logoSrc = $toDataUri($logoPath) ?: (!empty($systemSettings['logo']) ? $systemSettings['logo'] : null);

    $orderDisplayId = $order->order_number ?: $order->id;
    $recipientName = trim((string) ($order->shipping_name ?? ''));
    $recipientPhone = trim((string) ($order->shipping_phone ?? ''));
    $address1 = trim((string) ($order->shipping_address_1 ?? ''));
    $address2 = trim((string) ($order->shipping_address_2 ?? ''));
    $landmark = trim((string) ($order->shipping_landmark ?? ''));
    $zip = trim((string) ($order->shipping_zip ?? ''));
    $city = trim((string) ($order->shipping_city ?? ''));
    $state = trim((string) ($order->shipping_state ?? ''));
    $country = trim((string) ($order->shipping_country ?? ''));

    $fromName = trim((string) ($systemSettings['appName'] ?? 'Pethiyan.com'));
    $fromAddress = trim((string) ($systemSettings['companyAddress'] ?? ''));
@endphp

<div class="slip">
    <div class="section">
        <div class="label">Order ID</div>
        <div class="order-id">{{ $orderDisplayId }}</div>
    </div>

    <div class="section">
        <div class="label">To</div>
        <div class="to-name">{{ $recipientName ?: 'N/A' }}</div>
        @if($recipientPhone !== '')
            <div class="mobile">Mobile: {{ $recipientPhone }}</div>
        @endif

        @if($address1 !== '')
            <div class="address-line">Address: {{ $address1 }}</div>
        @endif
        @if($address2 !== '')
            <div class="address-line">{{ $address2 }}</div>
        @endif
        @if($landmark !== '')
            <div class="address-line">Land Mark: {{ $landmark }}</div>
        @endif
        <div class="address-line">
            {{ $zip }}@if($zip !== '' && $city !== ''), @endif{{ $city }}
        </div>
        @if($state !== '' || $country !== '')
            <div class="address-line">
                {{ $state }}@if($state !== '' && $country !== ''), @endif{{ $country }}
            </div>
        @endif
    </div>

    <div class="section">
        <div class="row">
            <div class="two-col-left">
                <div class="label">From</div>
                <div class="from-name">{{ $fromName }}</div>
                @if($fromAddress !== '')
                    <div class="from-address">Address: {{ $fromAddress }}</div>
                @endif
            </div>
            <div class="two-col-right">
                @if($logoSrc)
                    <img class="logo" src="{{ $logoSrc }}" alt="{{ $fromName }}">
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>
