<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PinServiceArea;
use App\Models\ShippingTariff;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Shipping')]
class ShippingRateApiController extends Controller
{
    // Weight brackets in grams
    private const BRACKET_250  = 250;
    private const BRACKET_500  = 500;
    private const BRACKET_2000 = 2000;

    /**
     * Get shipping rates for a pincode and package weight.
     *
     * Returns serviceability status, zone info, and per-partner
     * shipping cost breakdown (base rate + fuel surcharge + GST).
     */
    #[QueryParameter('pincode', description: '6-digit delivery pincode.', type: 'string', example: '560001')]
    #[QueryParameter('weight', description: 'Package weight in grams (default: 500).', type: 'int', example: 500)]
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'pincode' => 'required|digits:6',
            'weight'  => 'nullable|integer|min:1|max:100000',
        ]);

        $pincode      = $request->input('pincode');
        $weightGrams  = (int) $request->input('weight', 500);

        // 1. Check serviceability — JOIN pin_zones to get zone data in one query
        $pin = PinServiceArea::where('pin_service_areas.pincode', $pincode)
            ->where('pin_service_areas.is_serviceable', true)
            ->leftJoin('pin_zones', 'pin_zones.id', '=', 'pin_service_areas.zone_id')
            ->select(
                'pin_service_areas.*',
                'pin_zones.id as zone_ref_id',
                'pin_zones.code as zone_ref_code',
                'pin_zones.default_delivery_time as zone_ref_delivery_time',
            )
            ->first();

        if (!$pin) {
            // Check if pincode exists but is not serviceable
            $exists = PinServiceArea::where('pincode', $pincode)->exists();

            return ApiResponseType::sendJsonResponse(true, 'Shipping rates fetched.', [
                'pincode'        => $pincode,
                'is_serviceable' => false,
                'reason'         => $exists ? 'Pincode is not serviceable.' : 'Pincode not found.',
                'delivery_time'  => null,
                'weight_grams'   => $weightGrams,
                'rates'          => [],
                'cheapest'       => null,
            ]);
        }

        $zoneCode     = $pin->zone_ref_code ?? $pin->zone;
        $deliveryTime = $pin->zone_ref_delivery_time ?? $pin->delivery_time;

        if (!$pin->zone_ref_id) {
            return ApiResponseType::sendJsonResponse(true, 'Shipping rates fetched.', [
                'pincode'        => $pincode,
                'is_serviceable' => true,
                'delivery_time'  => $deliveryTime,
                'weight_grams'   => $weightGrams,
                'rates'          => [],
                'cheapest'       => null,
            ]);
        }

        // 2. Fetch active tariffs for this zone
        $tariffs = ShippingTariff::where('zone_id', $pin->zone_ref_id)
            ->where('is_active', true)
            ->with('deliveryPartner:id,name,is_active')
            ->get()
            ->filter(fn($t) => $t->deliveryPartner?->is_active);

        // 3. Calculate rates for each partner
        $rates = $tariffs->map(function (ShippingTariff $tariff) use ($weightGrams) {
            $baseRate = $this->calculateBaseRate($tariff, $weightGrams);

            $fuelAmount = round($baseRate * ((float) $tariff->fuel_surcharge_percent / 100), 2);
            $subtotal   = $baseRate + $fuelAmount;
            $gstAmount  = round($subtotal * ((float) $tariff->gst_percent / 100), 2);
            $total      = round($subtotal + $gstAmount, 2);

            return [
                'delivery_partner_id' => $tariff->delivery_partner_id,
                'delivery_partner'    => $tariff->deliveryPartner?->name ?? '—',
                'base_rate'           => $baseRate,
                'fuel_surcharge_pct'  => (float) $tariff->fuel_surcharge_percent,
                'fuel_surcharge'      => $fuelAmount,
                'gst_pct'             => (float) $tariff->gst_percent,
                'gst'                 => $gstAmount,
                'total'               => $total,
            ];
        })->values()->all();

        // 4. Find cheapest
        $cheapest = count($rates) > 0
            ? collect($rates)->sortBy('total')->first()
            : null;

        return ApiResponseType::sendJsonResponse(true, 'Shipping rates fetched.', [
            'pincode'        => $pincode,
            'is_serviceable' => true,
            'delivery_time'  => $deliveryTime,
            'weight_grams'   => $weightGrams,
            'rates'          => $rates,
            'cheapest'       => $cheapest,
        ]);
    }

    /**
     * Calculate base shipping rate for a given weight.
     *
     * Brackets:
     *   ≤ 250g              → upto_250
     *   251–500g            → upto_500
     *   501–2000g           → upto_500 + ceil((weight - 500) / 500) × every_500
     *   > 2000g             → per_kg × ceil(weight / 1000)
     */
    private function calculateBaseRate(ShippingTariff $tariff, int $weightGrams): float
    {
        if ($weightGrams <= self::BRACKET_250) {
            return (float) $tariff->upto_250;
        }

        if ($weightGrams <= self::BRACKET_500) {
            return (float) $tariff->upto_500;
        }

        if ($weightGrams <= self::BRACKET_2000) {
            $extraSlabs = (int) ceil(($weightGrams - self::BRACKET_500) / self::BRACKET_500);
            return round((float) $tariff->upto_500 + $extraSlabs * (float) $tariff->every_500, 2);
        }

        // Above 2kg — per_kg × ceil(weight in kg)
        $kg = ceil($weightGrams / 1000);
        return round($kg * (float) $tariff->per_kg, 2);
    }
}
