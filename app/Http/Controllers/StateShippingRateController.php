<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\PinZone;
use App\Models\ShippingTariff;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StateShippingRateController extends Controller
{
    use AuthorizesRequests, PanelAware;

    public function index(): View
    {
        $columns = [
            ['data' => 'id',                 'name' => 'id',                 'title' => __('labels.id')],
            ['data' => 'delivery_partner',   'name' => 'delivery_partner',   'title' => 'Delivery Partner'],
            ['data' => 'zone',               'name' => 'zone',               'title' => 'Zone'],
            ['data' => 'upto_250',           'name' => 'upto_250',           'title' => 'Upto 250g (₹)'],
            ['data' => 'upto_500',           'name' => 'upto_500',           'title' => 'Upto 500g (₹)'],
            ['data' => 'every_500',          'name' => 'every_500',          'title' => 'Every 500g (₹)'],
            ['data' => 'per_kg',             'name' => 'per_kg',             'title' => 'Per KG (₹)'],
            ['data' => 'fuel_surcharge',     'name' => 'fuel_surcharge',     'title' => 'Fuel %'],
            ['data' => 'gst_percent',        'name' => 'gst_percent',        'title' => 'GST %'],
            ['data' => 'is_active',          'name' => 'is_active',          'title' => __('labels.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'action',             'name' => 'action',             'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $partners = DeliveryPartner::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $zones = PinZone::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('admin.state-shipping-rates.index', compact('columns', 'partners', 'zones'));
    }

    public function togglePartnerStatus(int $id): JsonResponse
    {
        $partner = DeliveryPartner::query()->findOrFail($id);
        $partner->update([
            'is_active' => !$partner->is_active,
        ]);

        return ApiResponseType::sendJsonResponse(true, 'Delivery partner status updated.', [
            'id' => $partner->id,
            'name' => $partner->name,
            'is_active' => (bool) $partner->is_active,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = ShippingTariff::query()
            ->with(['deliveryPartner:id,name', 'zone:id,code,name']);

        $search = $request->input('search.value');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('deliveryPartner', function ($partnerQuery) use ($search) {
                    $partnerQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('zone', function ($zoneQuery) use ($search) {
                    $zoneQuery->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            });
        }

        $total = ShippingTariff::query()->count();
        $filtered = (clone $query)->count();

        $sortable = ['id', 'upto_250', 'upto_500', 'every_500', 'per_kg', 'fuel_surcharge_percent', 'gst_percent'];
        $requestedName = (string) $request->input('order.0.name', 'id');
        $orderCol = in_array($requestedName, $sortable, true) ? $requestedName : 'id';
        $orderDir = $request->input('order.0.dir', 'asc');
        $query->orderBy($orderCol, $orderDir);

        $query->skip($request->input('start', 0))->take($request->input('length', 15));

        $rows = $query->get()->map(function (ShippingTariff $r) {
            return [
                'id'                  => $r->id,
                'delivery_partner'    => e($r->deliveryPartner?->name ?? '—'),
                'zone'                => '<span class="badge bg-blue-lt">' . e($r->zone?->code ?? '—') . '</span>',
                'upto_250'            => '₹' . number_format((float)$r->upto_250, 2),
                'upto_500'            => '₹' . number_format((float)$r->upto_500, 2),
                'every_500'           => '₹' . number_format((float)$r->every_500, 2),
                'per_kg'              => '₹' . number_format((float)$r->per_kg, 2),
                'fuel_surcharge'      => number_format((float)$r->fuel_surcharge_percent, 2) . '%',
                'gst_percent'         => number_format((float)$r->gst_percent, 2) . '%',
                'is_active'           => $r->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>',
                'action' => view('admin.state-shipping-rates._actions', ['rate' => $r])->render(),
            ];
        });

        return response()->json([
            'draw'            => (int)$request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_partner_id' => 'required|exists:delivery_partners,id',
            'zone_id'             => 'required|exists:pin_zones,id',
            'upto_250'            => 'required|numeric|min:0',
            'upto_500'            => 'required|numeric|min:0',
            'every_500'           => 'required|numeric|min:0',
            'per_kg'              => 'required|numeric|min:0',
            'kg_2'                => 'required|numeric|min:0',
            'above_5_surface'     => 'required|numeric|min:0',
            'above_5_air'         => 'required|numeric|min:0',
            'fuel_surcharge_percent' => 'required|numeric|min:0|max:100',
            'gst_percent'         => 'required|numeric|min:0|max:100',
            'is_active'           => 'nullable|boolean',
            'notes'               => 'nullable|string|max:500',
        ]);

        $validated['is_active']  = $request->boolean('is_active', true);

        $rate = ShippingTariff::updateOrCreate(
            [
                'delivery_partner_id' => $validated['delivery_partner_id'],
                'zone_id' => $validated['zone_id'],
            ],
            $validated
        );

        return ApiResponseType::sendJsonResponse(true, 'Shipping tariff saved.', $rate);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rate = ShippingTariff::findOrFail($id);

        $validated = $request->validate([
            'delivery_partner_id' => 'required|exists:delivery_partners,id',
            'zone_id'             => 'required|exists:pin_zones,id',
            'upto_250'            => 'required|numeric|min:0',
            'upto_500'            => 'required|numeric|min:0',
            'every_500'           => 'required|numeric|min:0',
            'per_kg'              => 'required|numeric|min:0',
            'kg_2'                => 'required|numeric|min:0',
            'above_5_surface'     => 'required|numeric|min:0',
            'above_5_air'         => 'required|numeric|min:0',
            'fuel_surcharge_percent' => 'required|numeric|min:0|max:100',
            'gst_percent'         => 'required|numeric|min:0|max:100',
            'is_active'           => 'nullable|boolean',
            'notes'               => 'nullable|string|max:500',
        ]);

        $existsForPair = ShippingTariff::query()
            ->where('delivery_partner_id', $validated['delivery_partner_id'])
            ->where('zone_id', $validated['zone_id'])
            ->where('id', '!=', $rate->id)
            ->exists();

        if ($existsForPair) {
            return ApiResponseType::sendJsonResponse(false, 'Tariff already exists for this partner and zone.');
        }

        $validated['is_active']  = $request->boolean('is_active', true);

        $rate->update($validated);

        return ApiResponseType::sendJsonResponse(true, 'Shipping tariff updated.', $rate);
    }

    public function destroy(int $id): JsonResponse
    {
        $rate = ShippingTariff::findOrFail($id);
        $rate->delete();
        return ApiResponseType::sendJsonResponse(true, 'Shipping tariff deleted.');
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(true, '', ShippingTariff::findOrFail($id));
    }
}
