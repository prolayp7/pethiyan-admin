<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;

/**
 * GstService — Indian GST calculation engine (2026 slabs)
 *
 * Rules:
 *  - Intra-state supply (buyer & seller in same state)
 *      → CGST = slab/2  +  SGST = slab/2
 *  - Inter-state supply (buyer & seller in different states)
 *      → IGST = full slab
 *
 * GST slabs (2026): 0%, 5%, 12%, 18%, 28%
 */
class GstService
{
    /**
     * Standard Indian state codes (2-letter abbreviations).
     * Used to determine intra vs inter supply.
     */
    public const STATE_CODES = [
        'AN' => 'Andaman and Nicobar Islands',
        'AP' => 'Andhra Pradesh',
        'AR' => 'Arunachal Pradesh',
        'AS' => 'Assam',
        'BR' => 'Bihar',
        'CH' => 'Chandigarh',
        'CG' => 'Chhattisgarh',
        'DD' => 'Daman and Diu',
        'DL' => 'Delhi',
        'DN' => 'Dadra and Nagar Haveli',
        'GA' => 'Goa',
        'GJ' => 'Gujarat',
        'HR' => 'Haryana',
        'HP' => 'Himachal Pradesh',
        'JK' => 'Jammu and Kashmir',
        'JH' => 'Jharkhand',
        'KA' => 'Karnataka',
        'KL' => 'Kerala',
        'LA' => 'Ladakh',
        'LD' => 'Lakshadweep',
        'MP' => 'Madhya Pradesh',
        'MH' => 'Maharashtra',
        'MN' => 'Manipur',
        'ML' => 'Meghalaya',
        'MZ' => 'Mizoram',
        'NL' => 'Nagaland',
        'OD' => 'Odisha',
        'PY' => 'Puducherry',
        'PB' => 'Punjab',
        'RJ' => 'Rajasthan',
        'SK' => 'Sikkim',
        'TN' => 'Tamil Nadu',
        'TS' => 'Telangana',
        'TR' => 'Tripura',
        'UP' => 'Uttar Pradesh',
        'UK' => 'Uttarakhand',
        'WB' => 'West Bengal',
    ];

    /**
     * Current GST slabs (2026).
     * key = rate%, value = [CGST%, SGST%, IGST%, label, common goods]
     */
    public const GST_SLABS = [
        0  => ['cgst' => 0,   'sgst' => 0,   'igst' => 0,   'label' => 'Nil (0%)',  'examples' => 'Fresh produce, milk, eggs, printed books, newspapers'],
        5  => ['cgst' => 2.5, 'sgst' => 2.5, 'igst' => 5,   'label' => '5% GST',   'examples' => 'Sugar, tea, edible oils, handloom fabrics, small restaurants'],
        12 => ['cgst' => 6,   'sgst' => 6,   'igst' => 12,  'label' => '12% GST',  'examples' => 'Paper/paperboard packaging (HSN 4819), processed food, computers'],
        18 => ['cgst' => 9,   'sgst' => 9,   'igst' => 18,  'label' => '18% GST',  'examples' => 'Plastic packaging (HSN 3923), electronics, most services, AC restaurants'],
        28 => ['cgst' => 14,  'sgst' => 14,  'igst' => 28,  'label' => '28% GST',  'examples' => 'Luxury goods, automobiles, tobacco, aerated drinks'],
    ];

    /**
     * Determine supply type for an order.
     *
     * @param  string|null  $storeStateCode    e.g. 'MH'
     * @param  string|null  $customerStateCode e.g. 'DL'
     * @return 'intra'|'inter'
     */
    public function supplyType(?string $storeStateCode, ?string $customerStateCode): string
    {
        if (empty($storeStateCode) || empty($customerStateCode)) {
            return 'intra'; // default safe assumption
        }

        return strtoupper($storeStateCode) === strtoupper($customerStateCode)
            ? 'intra'
            : 'inter';
    }

    /**
     * Calculate GST amounts for a single line item.
     *
     * @param  float        $unitPrice       Price per unit (inclusive or exclusive of tax)
     * @param  int          $quantity
     * @param  int          $gstRatePct      GST slab: 0, 5, 12, 18, or 28
     * @param  string       $supplyType      'intra' or 'inter'
     * @param  bool         $priceInclusive  true = price already includes GST
     * @return array{
     *   taxable_amount: float,
     *   cgst_rate: float,
     *   cgst_amount: float,
     *   sgst_rate: float,
     *   sgst_amount: float,
     *   igst_rate: float,
     *   igst_amount: float,
     *   total_tax_amount: float,
     *   total_amount: float,
     *   gst_type: string,
     *   gst_rate: float
     * }
     */
    public function calculateLineItem(
        float  $unitPrice,
        int    $quantity,
        int    $gstRatePct,
        string $supplyType     = 'intra',
        bool   $priceInclusive = false
    ): array {
        $slab = self::GST_SLABS[$gstRatePct] ?? self::GST_SLABS[0];

        $grossAmount = round($unitPrice * $quantity, 2);

        // Derive taxable (ex-GST) amount
        if ($priceInclusive && $gstRatePct > 0) {
            $taxableAmount = round($grossAmount / (1 + $gstRatePct / 100), 2);
        } else {
            $taxableAmount = $grossAmount;
        }

        if ($supplyType === 'intra') {
            $cgstAmount = round($taxableAmount * $slab['cgst'] / 100, 2);
            $sgstAmount = round($taxableAmount * $slab['sgst'] / 100, 2);
            $igstAmount = 0;
        } else {
            $cgstAmount = 0;
            $sgstAmount = 0;
            $igstAmount = round($taxableAmount * $slab['igst'] / 100, 2);
        }

        $totalTax    = round($cgstAmount + $sgstAmount + $igstAmount, 2);
        $totalAmount = round($taxableAmount + $totalTax, 2);

        return [
            'taxable_amount'   => $taxableAmount,
            'gst_rate'         => (float) $gstRatePct,
            'gst_type'         => $supplyType,
            'cgst_rate'        => $slab['cgst'],
            'cgst_amount'      => $cgstAmount,
            'sgst_rate'        => $slab['sgst'],
            'sgst_amount'      => $sgstAmount,
            'igst_rate'        => $slab['igst'],
            'igst_amount'      => $igstAmount,
            'total_tax_amount' => $totalTax,
            'total_amount'     => $totalAmount,
        ];
    }

    /**
     * Compute order-level GST totals from a set of line-item results.
     *
     * @param  array  $lineItems  Array of calculateLineItem() results
     * @return array{total_taxable_amount, total_cgst, total_sgst, total_igst, total_gst}
     */
    public function orderTotals(array $lineItems): array
    {
        $totals = [
            'total_taxable_amount' => 0,
            'total_cgst'           => 0,
            'total_sgst'           => 0,
            'total_igst'           => 0,
            'total_gst'            => 0,
        ];

        foreach ($lineItems as $line) {
            $totals['total_taxable_amount'] += $line['taxable_amount'];
            $totals['total_cgst']           += $line['cgst_amount'];
            $totals['total_sgst']           += $line['sgst_amount'];
            $totals['total_igst']           += $line['igst_amount'];
            $totals['total_gst']            += $line['total_tax_amount'];
        }

        return array_map(fn($v) => round($v, 2), $totals);
    }

    /**
     * Resolve the effective GST rate for a product.
     * Priority: product-level gst_rate > tax class rate > 0
     */
    public function resolveProductGstRate(Product $product): int
    {
        // Product-level override
        if (!empty($product->gst_rate)) {
            return (int) $product->gst_rate;
        }

        // Tax class rate via ProductTax → TaxClass → TaxRate
        $taxClass = $product->taxes()->with('taxClass.taxRates')->first()?->taxClass;
        if ($taxClass) {
            $rate = $taxClass->taxRates->first()?->rate;
            if ($rate !== null) {
                // Map to nearest valid slab
                return $this->nearestSlab((float) $rate);
            }
        }

        return 0; // Nil rated by default
    }

    /**
     * Round a rate to the nearest valid GST slab.
     */
    public function nearestSlab(float $rate): int
    {
        $slabs = [0, 5, 12, 18, 28];
        $nearest = 0;
        $minDiff  = PHP_INT_MAX;

        foreach ($slabs as $slab) {
            $diff = abs($rate - $slab);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearest = $slab;
            }
        }

        return $nearest;
    }

    /**
     * Validate a GSTIN number (format check only).
     * Format: 2-digit state code + 10-char PAN + 1-digit entity + Z + 1 check digit
     * e.g. 27AABCU9603R1ZV
     */
    public function validateGstin(string $gstin): bool
    {
        return (bool) preg_match(
            '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            strtoupper(trim($gstin))
        );
    }

    /**
     * Return state name from GSTIN prefix (first 2 digits map to state).
     */
    public function stateFromGstin(string $gstin): ?string
    {
        $stateNumericMap = [
            '01' => 'JK', '02' => 'HP', '03' => 'PB', '04' => 'CH',
            '05' => 'UK', '06' => 'HR', '07' => 'DL', '08' => 'RJ',
            '09' => 'UP', '10' => 'BR', '11' => 'SK', '12' => 'AR',
            '13' => 'NL', '14' => 'MN', '15' => 'MZ', '16' => 'TR',
            '17' => 'ML', '18' => 'AS', '19' => 'WB', '20' => 'JH',
            '21' => 'OD', '22' => 'CG', '23' => 'MP', '24' => 'GJ',
            '25' => 'DD', '26' => 'DN', '27' => 'MH', '28' => 'AP',
            '29' => 'KA', '30' => 'GA', '31' => 'LD', '32' => 'KL',
            '33' => 'TN', '34' => 'PY', '35' => 'AN', '36' => 'TS',
            '37' => 'AP', '38' => 'LA',
        ];

        $prefix = substr(trim($gstin), 0, 2);

        return $stateNumericMap[$prefix] ?? null;
    }
}
