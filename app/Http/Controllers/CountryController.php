<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends \Illuminate\Routing\Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->input('search', '');
        if (!empty($request->input('find'))) {
            $countries = Country::where('name', $request->input('find'))->get();
        } else {
            $countries = Country::when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%$query%")
                        ->orWhere('iso3', 'like', "%$query%")
                        ->orWhere('iso3', 'like', "%$query%")
                        ->orWhere('iso2', 'like', "%$query%")
                        ->orWhere('phonecode', 'like', "%$query%");
                });
            })
                ->limit(20)
                ->get();
        }
        // Format for TomSelect
        $results = $countries->map(function ($country) {
            return [
                'phonecode' => $country->phonecode,
                'value' => $country->name,
                'text' => $country->name,
                'currency' => $country->currency,
                'customProperties' => '<span class="flag flag-xs flag-country-' . strtolower($country->iso2) . '"></span>',
            ];
        });

        return response()->json($results);
    }

    public function getCurrency(Request $request): JsonResponse
    {
        $query = $request->input('search', '');

        // Canonical iso2 per currency — avoids "American Samoa" for USD, etc.
        $canonicalIso2 = [
            'INR' => 'IN', // India → ₹
            'USD' => 'US', // United States → $
            'EUR' => 'DE', // Germany (Eurozone representative)
            'GBP' => 'GB',
            'JPY' => 'JP',
            'AUD' => 'AU',
            'CAD' => 'CA',
            'CNY' => 'CN',
            'AED' => 'AE',
            'SGD' => 'SG',
        ];

        if (!empty($request->input('find'))) {
            $currencyCode = $request->input('find');
            $preferredIso = $canonicalIso2[$currencyCode] ?? null;

            $countries = Country::where('currency', $currencyCode)
                ->orderByRaw($preferredIso
                    ? "CASE WHEN iso2 = ? THEN 0 ELSE 1 END"
                    : "id", $preferredIso ? [$preferredIso] : [])
                ->limit(1)
                ->get();
        } else {
            $countries = Country::when($query, function ($q) use ($query) {
                    $q->where(function ($sub) use ($query) {
                        $sub->where('name', 'like', "%$query%")
                            ->orWhere('currency', 'like', "%$query%")
                            ->orWhere('currency_symbol', 'like', "%$query%")
                            ->orWhere('iso3', 'like', "%$query%")
                            ->orWhere('iso2', 'like', "%$query%");
                    });
                })
                // Pin INR (India) first, then USD (US), then rest
                ->orderByRaw("CASE WHEN iso2 = 'IN' THEN 0 WHEN iso2 = 'US' THEN 1 ELSE 2 END")
                ->orderBy('currency')
                ->limit(50)
                ->get()
                // Deduplicate: one row per currency code, prefer canonical country
                ->groupBy('currency')
                ->map(function ($group) use ($canonicalIso2) {
                    $currency = $group->first()->currency;
                    $preferred = $canonicalIso2[$currency] ?? null;
                    return $preferred
                        ? ($group->firstWhere('iso2', $preferred) ?? $group->first())
                        : $group->first();
                })
                ->values();
        }

        $results = $countries->map(function ($country) {
            return [
                'value'            => $country->currency,
                'text'             => $country->currency . ' - ' . $country->currency_symbol,
                'currency'         => $country->currency,
                'currency_symbol'  => $country->currency_symbol,
                'customProperties' => '<span class="flag flag-xs flag-country-' . strtolower($country->iso2) . '"></span>',
            ];
        });

        return response()->json($results->values());
    }
}
