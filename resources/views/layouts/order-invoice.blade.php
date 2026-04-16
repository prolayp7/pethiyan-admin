@php use Illuminate\Support\Str; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - {{ $systemSettings['appName'] }}</title>
    <style>
        @page { margin: 12px 10px; }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; background: #fff; }
        h2 { font-size: 18px; }
        h4 { font-size: 13px; margin-bottom: 4px; }
        h5 { font-size: 12px; margin: 8px 0 4px; }
        p  { margin: 2px 0; }

        .page { padding: 8px 8px 54px; }
        .page-break { page-break-before: always; }

        /* ── Header grid ── */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .header-table td { vertical-align: top; padding: 5px; }

        /* ── Generic tables ── */
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tfoot { display: table-row-group; }
        th, td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: left;
            font-size: 10px;
            overflow-wrap: anywhere;
            word-break: break-word;
            page-break-inside: avoid;
        }
        th { background: #f0f0f0; font-weight: bold; }
        tfoot td { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .no-border td { border: none; }

        /* ── GST badges ── */
        .badge-intra { color: #155724; background: #d4edda; border-radius: 3px; padding: 1px 5px; }
        .badge-inter { color: #004085; background: #cce5ff; border-radius: 3px; padding: 1px 5px; }

        /* ── Totals summary box ── */
        .totals-table { width: 320px; margin-left: auto; margin-top: 12px; }
        .totals-table td { border: 1px solid #ccc; padding: 5px 7px; font-size: 10px; }
        .totals-table .label { text-align: right; color: #555; }
        .totals-table .grand { font-weight: bold; font-size: 12px; background: #f0f0f0; }

        .section-title { font-size: 13px; font-weight: bold; margin: 14px 0 6px; border-bottom: 2px solid #333; padding-bottom: 4px; }

        .signatory { text-align: center; width: 220px; margin: 30px 0 0 auto; }
        .signatory-line { border-top: 1px solid #000; margin-top: 8px; padding-top: 4px; font-weight: bold; font-size: 11px; }
        .clearfix::after { content: ""; display: table; clear: both; }

        .footer-note {
            position: fixed;
            left: 10px;
            right: 10px;
            bottom: 6px;
            text-align: center;
            font-size: 10px;
            color: #555;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            background: #fff;
        }
    </style>
</head>
<body>
@php
    $currency   = $systemSettings['currencySymbol'] ?? '₹';
    $supplyType = $order['supply_type'] ?? 'intra';
    $isIntra    = $supplyType === 'intra';

    $resolvePdfImagePath = function (?string $value, ?string $fallback = null): ?string {
        $candidate = $value ?: $fallback;
        if (!$candidate) return null;

        // data-uri can be consumed directly by dompdf
        if (str_starts_with($candidate, 'data:')) {
            return $candidate;
        }

        // Absolute local path
        if (str_starts_with($candidate, '/')) {
            if (is_file($candidate)) return $candidate;
            $publicPath = public_path(ltrim($candidate, '/'));
            if (is_file($publicPath)) return $publicPath;
        }

        // Handle URLs like http://.../storage/xxx.png
        $parsedPath = parse_url($candidate, PHP_URL_PATH);
        if (is_string($parsedPath) && $parsedPath !== '') {
            if (str_starts_with($parsedPath, '/storage/')) {
                $storageFile = storage_path('app/public/' . ltrim(substr($parsedPath, strlen('/storage/')), '/'));
                if (is_file($storageFile)) return $storageFile;
            }
            $publicFile = public_path(ltrim($parsedPath, '/'));
            if (is_file($publicFile)) return $publicFile;
        }

        // Handle relative storage/public references
        if (str_starts_with($candidate, 'storage/')) {
            $storageFile = storage_path('app/public/' . ltrim(substr($candidate, strlen('storage/')), '/'));
            if (is_file($storageFile)) return $storageFile;
        }
        $publicFile = public_path(ltrim($candidate, '/'));
        if (is_file($publicFile)) return $publicFile;

        return null;
    };

    $invoiceLogoPath = $resolvePdfImagePath($systemSettings['logo'] ?? null, asset('logos/hyper-local-logo.png'));
    $adminSignaturePath = $resolvePdfImagePath($systemSettings['adminSignature'] ?? null);

    $toDataUri = function (?string $filePath): ?string {
        if (!$filePath || !is_file($filePath)) return null;
        $mime = mime_content_type($filePath) ?: 'image/png';
        $raw = file_get_contents($filePath);
        if ($raw === false) return null;
        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    };

    $invoiceLogoSrc = $toDataUri($invoiceLogoPath) ?: (!empty($systemSettings['logo']) ? $systemSettings['logo'] : null);
    $adminSignatureSrc = $toDataUri($adminSignaturePath) ?: (!empty($systemSettings['adminSignature']) ? $systemSettings['adminSignature'] : null);

    $shipName = $order['shipping_name'] ?? $order['billing_name'] ?? '';
    $shipAddress1 = $order['shipping_address_1'] ?? $order['billing_address_1'] ?? '';
    $shipAddress2 = $order['shipping_address_2'] ?? $order['billing_address_2'] ?? '';
    $shipLandmark = $order['shipping_landmark'] ?? $order['billing_landmark'] ?? '';
    $shipCity = $order['shipping_city'] ?? $order['billing_city'] ?? '';
    $shipState = $order['shipping_state'] ?? $order['billing_state'] ?? '';
    $shipZip = $order['shipping_zip'] ?? $order['billing_zip'] ?? '';
    $shipCountry = $order['shipping_country'] ?? $order['billing_country'] ?? '';
    $shipPhone = $order['shipping_phone'] ?? $order['billing_phone'] ?? '';
    $customerCompanyName = data_get($order, 'shipping_company_name')
        ?: data_get($order, 'billing_company_name')
        ?: data_get($order, 'company_name')
        ?: data_get($order, 'user.company_name')
        ?: '';
    $customerGstin = data_get($order, 'customer_gstin')
        ?: data_get($order, 'gstin')
        ?: data_get($order, 'user.gstin')
        ?: '';
    $displayStoreTaxNumber = function ($store): string {
        $taxNumber = data_get($store, 'tax_number');
        if (filled($taxNumber)) {
            return (string) $taxNumber;
        }

        $taxName = data_get($store, 'tax_name');
        if (filled($taxName)) {
            return (string) $taxName;
        }

        return 'N/A';
    };
    $formatWeight = function ($weight, $unit): ?string {
        if ($weight === null || $weight === '') {
            return null;
        }

        $formattedWeight = rtrim(rtrim(number_format((float) $weight, 3, '.', ''), '0'), '.');

        return trim($formattedWeight . ' ' . (string) $unit);
    };
@endphp

<div class="page">

    {{-- ══ PAGE 1: CONSOLIDATED INVOICE ══════════════════════════════════ --}}
    <table class="header-table" style="margin-bottom:10px;">
        <tr>
            <td style="border:none; width:45%; padding:0; vertical-align:top;">
                @if(!empty($invoiceLogoSrc))
                    <img src="{{ $invoiceLogoSrc }}" alt="Logo" style="max-width:220px; max-height:84px; width:auto; height:auto; object-fit:contain; display:block;">
                @endif
            </td>
            <td style="border:none; width:55%; padding:0; vertical-align:top; text-align:right;">
                <h2 style="margin:0;">TAX INVOICE</h2>
                <p style="font-size:10px; margin-top:4px;">
                    Supply Type:
                    @if($isIntra)
                        <span class="badge-intra">Intra-State (CGST + SGST)</span>
                    @else
                        <span class="badge-inter">Inter-State (IGST)</span>
                    @endif
                </p>
            </td>
        </tr>
    </table>

    {{-- Header: Seller / Invoice Info / Buyer ─────────────────────────── --}}
    <table class="header-table">
        <tr>
                {{-- Supplier --}}
                <td style="border:1px solid #ccc; border-radius:4px;">
                <strong>{{ $systemSettings['appName'] }}</strong><br>
                @if(!empty($systemSettings['companyAddress']))
                    {!! nl2br(e($systemSettings['companyAddress'])) !!}<br>
                @endif
                @if(!empty($systemSettings['sellerSupportEmail']))
                    Email: {{ $systemSettings['sellerSupportEmail'] }}<br>
                @endif
                @if(!empty($systemSettings['sellerSupportNumber']))
                    Phone: {{ $systemSettings['sellerSupportNumber'] }}<br>
                @endif
                @if(!empty($systemSettings['gstin'] ?? null))
                    <strong>GSTIN:</strong> {{ $systemSettings['gstin'] }}
                @endif
            </td>

            {{-- Invoice Meta --}}
            <td style="border:1px solid #ccc; border-radius:4px;">
                <strong>Invoice #:</strong> {{ $order->invoice_number }}<br>
                <strong>Order Date:</strong> {{ $order['created_at']->format('d M Y H:i') }}<br>
                <strong>Payment:</strong> {{ strtoupper(str_replace('_',' ',$order['payment_method'] ?? 'N/A')) }}<br>
                @if(!empty($order['customer_state']))
                    <strong>Ship-to State:</strong> {{ $order['customer_state'] }}
                    @if(!empty($order['customer_state_code']))
                        ({{ $order['customer_state_code'] }})
                    @endif
                @endif
            </td>

                {{-- Buyer --}}
                <td style="border:1px solid #ccc; border-radius:4px;">
                    <strong>Bill To:</strong><br>
                {{ $shipName }}<br>
                @if($customerCompanyName)
                    Company: {{ $customerCompanyName }}<br>
                @endif
                {{ $shipAddress1 }}
                @if(!empty($shipLandmark)), {{ $shipLandmark }}@endif<br>
                @if(!empty($shipAddress2))
                    {{ $shipAddress2 }}<br>
                @endif
                {{ $shipCity }}, {{ $shipState }} - {{ $shipZip }}<br>
                {{ $shipCountry }}<br>
                Phone: {{ $shipPhone }}<br>
                Email: {{ $order['email'] }}
                @if($customerGstin)
                    <br>GSTIN: {{ $customerGstin }}
                @endif
            </td>
        </tr>
    </table>

    {{-- ── Order Items (consolidated) ─────────────────────────────────── --}}
    <div class="section-title">Order Summary</div>

    @foreach($sellerOrder as $vendor)
        @php $store = $vendor['items'][0]['orderItem']['store'] ?? null; @endphp

        <h5>
            Sold by: {{ $store['name'] ?? ($vendor['seller']['stores'][0]['name'] ?? 'N/A') }}
            @if($store && !empty($store['gstin']))
                &nbsp;|&nbsp; GSTIN: <strong>{{ $store['gstin'] }}</strong>
            @elseif($store)
                &nbsp;|&nbsp; {{ $displayStoreTaxNumber($store) }}
            @endif
            @if($store && !empty($store['state_code']))
                &nbsp;|&nbsp; State: {{ $store['state_name'] ?? $store['state'] ?? '' }} ({{ $store['state_code'] }})
            @endif
        </h5>

        <table style="margin-bottom:10px;">
            <thead>
            <tr>
                <th style="width:20%">Item</th>
                <th style="width:7%">HSN</th>
                <th style="width:5%">Qty</th>
                <th style="width:8%">Unit Price</th>
                <th style="width:8%">Taxable Amt</th>
                <th style="width:6%">GST%</th>
                @if($isIntra)
                    <th style="width:8%">CGST</th>
                    <th style="width:8%">SGST</th>
                @else
                    <th style="width:10%">IGST</th>
                @endif
                <th style="width:10%">Tax Amt</th>
                <th style="width:10%">Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($vendor['items'] as $item)
                @php
                    $oi  = $item['orderItem'];
                    $qty = (float)($item['quantity'] ?? 1);
                    $taxableAmt = (float)($oi['taxable_amount'] ?? ($item['price'] * $qty));
                    $gstRate    = (float)($oi['gst_rate']       ?? 0);
                    $cgst       = (float)($oi['cgst_amount']    ?? 0);
                    $sgst       = (float)($oi['sgst_amount']    ?? 0);
                    $igst       = (float)($oi['igst_amount']    ?? 0);
                    $totalTax   = (float)($oi['total_tax_amount'] ?? ($cgst + $sgst + $igst));
                    $lineTotal  = $taxableAmt + $totalTax;
                    $productTitle = $item['product']['title'] ?? $item['title'] ?? 'Item';
                    $hsn        = $oi['hsn_code'] ?? ($item['product']['hsn_code'] ?? '—');
                    $variantTitle = trim((string) ($item['variant']['title'] ?? $item['variant_title'] ?? ''));
                    if ($variantTitle !== '' && strcasecmp($variantTitle, $productTitle) === 0) {
                        $variantTitle = '';
                    }
                    $weightLabel = $formatWeight($item['variant']['weight'] ?? null, $item['variant']['weight_unit'] ?? '');
                    $itemMeta = collect([$variantTitle, $weightLabel])->filter(fn ($value) => filled($value))->implode(' | ');
                @endphp
                <tr>
                    <td>{{ $productTitle }}<br>
                        @if($itemMeta)
                            <small style="color:#666;">{{ $itemMeta }}</small>
                        @endif
                    </td>
                    <td>{{ $hsn }}</td>
                    <td class="text-center">{{ $qty }}</td>
                    <td class="text-right">{{ $currency }}{{ number_format($item['price'], 2) }}</td>
                    <td class="text-right">{{ $currency }}{{ number_format($taxableAmt, 2) }}</td>
                    <td class="text-center">{{ $gstRate }}%</td>
                    @if($isIntra)
                        <td class="text-right">{{ $currency }}{{ number_format($cgst, 2) }}<br><small>({{ $gstRate/2 }}%)</small></td>
                        <td class="text-right">{{ $currency }}{{ number_format($sgst, 2) }}<br><small>({{ $gstRate/2 }}%)</small></td>
                    @else
                        <td class="text-right">{{ $currency }}{{ number_format($igst, 2) }}<br><small>({{ $gstRate }}%)</small></td>
                    @endif
                    <td class="text-right">{{ $currency }}{{ number_format($totalTax, 2) }}</td>
                    <td class="text-right"><strong>{{ $currency }}{{ number_format($lineTotal, 2) }}</strong></td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <td colspan="{{ $isIntra ? 9 : 8 }}" class="text-right"><strong>Store Subtotal:</strong></td>
                <td class="text-right"><strong>{{ $currency }}{{ number_format($vendor['total_price'], 2) }}</strong></td>
            </tr>
            </tfoot>
        </table>
    @endforeach

    {{-- ── GST + Payment Summary ────────────────────────────────────────── --}}
    <table class="totals-table">
        <tr>
            <td class="label">Items Subtotal:</td>
            <td class="text-right">{{ $currency }}{{ number_format($order['subtotal'], 2) }}</td>
        </tr>
        @if(($order['total_taxable_amount'] ?? 0) > 0)
        <tr>
            <td class="label">Taxable Amount:</td>
            <td class="text-right">{{ $currency }}{{ number_format($order['total_taxable_amount'], 2) }}</td>
        </tr>
        @endif
        @if($isIntra && ($order['total_cgst'] ?? 0) > 0)
        <tr>
            <td class="label">CGST:</td>
            <td class="text-right">{{ $currency }}{{ number_format($order['total_cgst'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">SGST:</td>
            <td class="text-right">{{ $currency }}{{ number_format($order['total_sgst'], 2) }}</td>
        </tr>
        @elseif(!$isIntra && ($order['total_igst'] ?? 0) > 0)
        <tr>
            <td class="label">IGST:</td>
            <td class="text-right">{{ $currency }}{{ number_format($order['total_igst'], 2) }}</td>
        </tr>
        @endif
        @if(($order['total_gst'] ?? 0) > 0)
        <tr>
            <td class="label"><strong>Total GST:</strong></td>
            <td class="text-right"><strong>{{ $currency }}{{ number_format($order['total_gst'], 2) }}</strong></td>
        </tr>
        @endif
        @if(($order['delivery_charge'] ?? 0) > 0)
        <tr>
            <td class="label">Shipping:</td>
            <td class="text-right">{{ $currency }}{{ number_format($order['delivery_charge'], 2) }}</td>
        </tr>
        @endif
        @if(($order['handling_charges'] ?? 0) > 0)
        <tr>
            <td class="label">Handling:</td>
            <td class="text-right">{{ $currency }}{{ number_format($order['handling_charges'], 2) }}</td>
        </tr>
        @endif
        @if(($order['promo_discount'] ?? 0) > 0)
        <tr>
            <td class="label">Promo ({{ $order['promo_code'] ?? '' }}):</td>
            <td class="text-right">- {{ $currency }}{{ number_format($order['promo_discount'], 2) }}</td>
        </tr>
        @endif
        @if(($order['wallet_balance'] ?? 0) > 0)
        <tr>
            <td class="label">Wallet Used:</td>
            <td class="text-right">- {{ $currency }}{{ number_format($order['wallet_balance'], 2) }}</td>
        </tr>
        @endif
        <tr class="grand">
            <td class="label grand">Amount Payable:</td>
            <td class="text-right grand">{{ $currency }}{{ number_format($order['total_payable'], 2) }}</td>
        </tr>
    </table>

    {{-- Signatory --}}
    <div class="clearfix" style="margin-top:30px;">
        <div class="signatory">
            @if(!empty($adminSignatureSrc))
                <img src="{{ $adminSignatureSrc }}" style="max-height:60px; max-width:180px;">
            @else
                <div style="height:50px;"></div>
            @endif
            <div class="signatory-line">Authorized Signatory</div>
        </div>
    </div>

    <div class="footer-note">
        Computer Generated Invoice - No Signature Required.
        @if(!empty($systemSettings['sellerSupportEmail']))
            &nbsp;|&nbsp; {{ $systemSettings['sellerSupportEmail'] }}
        @endif
        @if(!empty($systemSettings['copyrightDetails']))
            &nbsp;|&nbsp; {{ $systemSettings['copyrightDetails'] }}
        @endif
    </div>

</div>
</body>
</html>
