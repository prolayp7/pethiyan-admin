<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Enquiry</title>
    <style>
        body { margin: 0; padding: 0; background: #f0f4f8; }
        table { border-collapse: collapse; }
        .outer { width: 100%; background: #f0f4f8; }
        .container { width: 680px; max-width: 680px; background: #ffffff; border: 1px solid #cdddf0; }
        .header { background: linear-gradient(135deg, #0f2f5f 0%, #1f4f8a 45%, #27a567 100%); }
        .title { font-family: Arial, sans-serif; font-size: 22px; font-weight: 700; color: #ffffff; }
        .sub { font-family: Arial, sans-serif; font-size: 13px; color: rgba(255,255,255,0.80); }
        .label { font-family: Arial, sans-serif; font-size: 11px; font-weight: 700; color: #7a8fa8; text-transform: uppercase; letter-spacing: .4px; }
        .value { font-family: Arial, sans-serif; font-size: 14px; color: #1f3d60; }
        .message-box { background: #f4f8ff; border: 1px solid #cdddf0; border-radius: 6px; padding: 16px; font-family: Arial, sans-serif; font-size: 14px; color: #1f3d60; line-height: 22px; white-space: pre-wrap; }
        .footer { background: #f4f8ff; border-top: 1px solid #cdddf0; font-family: Arial, sans-serif; font-size: 12px; color: #7a8fa8; }
        @media only screen and (max-width: 700px) {
            .container { width: 100% !important; }
        }
    </style>
</head>
<body>
@php
    $appName = $systemSettings['appName'] ?? config('app.name', 'Pethiyan');
    $logoUrl  = !empty($systemSettings['logo']) ? $systemSettings['logo'] : asset('logos/hyper-local-logo.png');

    // Resolve logo to a local file path so it can be embedded as a CID attachment.
    // This avoids broken images when APP_URL is localhost or unreachable from email clients.
    $logoPath = public_path('logos/hyper-local-logo.png');
    if (!empty($systemSettings['logo'])) {
        $parsedPath = parse_url($systemSettings['logo'], PHP_URL_PATH);
        if (is_string($parsedPath) && str_starts_with($parsedPath, '/storage/')) {
            $candidate = storage_path('app/public/' . ltrim(substr($parsedPath, strlen('/storage/')), '/'));
            if (is_file($candidate)) {
                $logoPath = $candidate;
            }
        } elseif (is_string($systemSettings['logo']) && str_starts_with($systemSettings['logo'], '/')) {
            $candidate = public_path(ltrim($systemSettings['logo'], '/'));
            if (is_file($candidate)) {
                $logoPath = $candidate;
            }
        }
    }

    $enquiry    = $enquiry ?? null;
    $receivedAt = $enquiry?->created_at?->setTimezone('Asia/Kolkata')?->format('d M Y, h:i A') . ' IST';
@endphp

<table class="outer" cellpadding="0" cellspacing="0" role="presentation">
<tr><td align="center" style="padding: 32px 16px;">
<table class="container" cellpadding="0" cellspacing="0" role="presentation">

    {{-- Header --}}
    <tr>
        <td class="header" style="padding: 28px 32px;">
            <table cellpadding="0" cellspacing="0" role="presentation" width="100%">
                <tr>
                    <td>
                        @if($logoPath || $logoUrl)
                            <img src="{{ (isset($message) && is_file($logoPath)) ? $message->embed($logoPath) : $logoUrl }}"
                                 alt="{{ $appName }}"
                                 height="40"
                                 style="display:block; margin-bottom:12px; max-width:160px; border:0;">
                        @endif
                        <div class="title">New Contact Enquiry</div>
                        <div class="sub" style="margin-top:4px;">Received via the Contact Us form</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Body --}}
    <tr>
        <td style="padding: 32px;">

            {{-- Enquiry details --}}
            <table cellpadding="0" cellspacing="0" role="presentation" width="100%" style="border:1px solid #cdddf0; border-radius:6px; overflow:hidden;">
                <tr style="background:#eef4fb;">
                    <td colspan="2" style="padding:12px 16px;">
                        <span style="font-family:Arial,sans-serif; font-size:13px; font-weight:700; color:#0f2f5f;">Enquiry Details</span>
                        @if($receivedAt)
                            <span style="font-family:Arial,sans-serif; font-size:12px; color:#7a8fa8; float:right;">{{ $receivedAt }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 16px; border-top:1px solid #e8eef5; width:35%;">
                        <div class="label">Name</div>
                        <div class="value">{{ $enquiry->name }}</div>
                    </td>
                    <td style="padding:12px 16px; border-top:1px solid #e8eef5;">
                        <div class="label">Phone</div>
                        <div class="value">{{ $enquiry->phone }}</div>
                    </td>
                </tr>
                @if($enquiry->email)
                <tr>
                    <td colspan="2" style="padding:12px 16px; border-top:1px solid #e8eef5;">
                        <div class="label">Email</div>
                        <div class="value">{{ $enquiry->email }}</div>
                    </td>
                </tr>
                @endif
                @if($enquiry->subject)
                <tr>
                    <td colspan="2" style="padding:12px 16px; border-top:1px solid #e8eef5;">
                        <div class="label">Subject</div>
                        <div class="value">{{ $enquiry->subject }}</div>
                    </td>
                </tr>
                @endif
                <tr>
                    <td colspan="2" style="padding:12px 16px; border-top:1px solid #e8eef5;">
                        <div class="label" style="margin-bottom:8px;">Message</div>
                        <div class="message-box">{{ $enquiry->message }}</div>
                    </td>
                </tr>
            </table>

            <p style="font-family:Arial,sans-serif; font-size:13px; color:#7a8fa8; margin-top:24px; text-align:center;">
                You can view and manage this enquiry in the
                <a href="{{ url('/admin/enquiries') }}" style="color:#1f4f8a;">Admin Panel → Enquiries</a>.
            </p>
        </td>
    </tr>

    {{-- Footer --}}
    <tr>
        <td class="footer" style="padding:20px 32px; text-align:center;">
            &copy; {{ date('Y') }} {{ $appName }}. This is an automated notification.
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
