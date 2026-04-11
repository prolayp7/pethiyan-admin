<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use App\Models\Seller;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GiftCardController extends Controller
{
    use PanelAware;

    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $columns = [
            ['data' => 'id',                    'name' => 'id',                    'title' => __('labels.id')],
            ['data' => 'title',                 'name' => 'title',                 'title' => 'Title'],
            ['data' => 'barcode',               'name' => 'barcode',               'title' => 'Barcode'],
            ['data' => 'discount',              'name' => 'discount',              'title' => 'Discount'],
            ['data' => 'minimum_order_amount',  'name' => 'minimum_order_amount',  'title' => 'Min. Order'],
            ['data' => 'validity',              'name' => 'validity',              'title' => 'Validity', 'orderable' => false, 'searchable' => false],
            ['data' => 'status',               'name' => 'status',                'title' => __('labels.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'action',               'name' => 'action',                'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        return view('admin.gift_cards.index', compact('columns'));
    }

    // ─── Datatable ────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        try {
            $draw        = $request->get('draw');
            $start       = (int) $request->get('start', 0);
            $length      = (int) $request->get('length', 10);
            $search      = $request->get('search')['value'] ?? '';
            $filterUsed  = $request->get('used');

            $orderColIdx = $request->get('order')[0]['column'] ?? 0;
            $orderDir    = $request->get('order')[0]['dir'] ?? 'desc';
            $cols        = ['id', 'title', 'barcode', 'discount', 'minimum_order_amount', 'validity', 'status'];
            $orderCol    = in_array($cols[$orderColIdx] ?? '', ['id', 'title', 'discount', 'minimum_order_amount'])
                ? ($cols[$orderColIdx] ?? 'id') : 'id';

            $query = GiftCard::query();
            $totalRecords = $query->count();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('title',   'like', "%{$search}%")
                      ->orWhere('barcode','like', "%{$search}%");
                });
            }

            if ($filterUsed !== null && $filterUsed !== '') {
                $query->where('used', $filterUsed);
            }

            $filteredRecords = $query->count();

            $cards = $query->orderBy($orderCol, $orderDir)->skip($start)->take($length)->get();

            $data = $cards->map(function (GiftCard $card) {
                $active  = $card->isActive();
                $status  = $card->isUsed()
                    ? '<span class="badge bg-red-lt">Used</span>'
                    : ($active
                        ? '<span class="badge bg-green-lt">Active</span>'
                        : '<span class="badge bg-secondary-lt">Inactive</span>');

                $start = $card->start_time?->format('d M Y') ?? '—';
                $end   = $card->end_time?->format('d M Y')   ?? '—';

                return [
                    'id'                   => $card->id,
                    'title'                => Str::limit($card->title, 40),
                    'barcode'              => '<code>' . $card->barcode . '</code>',
                    'discount'             => '₹' . number_format($card->discount, 2),
                    'minimum_order_amount' => '₹' . number_format($card->minimum_order_amount, 2),
                    'validity'             => "{$start} → {$end}",
                    'status'               => $status,
                    'action'               => $this->renderActions($card),
                ];
            })->toArray();

            return response()->json([
                'draw'            => intval($draw),
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data'            => $data,
            ]);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, 'Failed to load gift cards: ' . $e->getMessage(), []);
        }
    }

    // ─── Show (for edit modal) ────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $card = GiftCard::findOrFail($id);
        return ApiResponseType::sendJsonResponse(true, 'Gift card fetched.', $card);
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'                => 'required|string|max:250',
            'discount'             => 'required|numeric|min:0',
            'minimum_order_amount' => 'required|numeric|min:0',
            'start_time'           => 'nullable|date',
            'end_time'             => 'nullable|date|after_or_equal:start_time',
        ]);

        $card = GiftCard::create($validated);

        return ApiResponseType::sendJsonResponse(true, 'Gift card created.', $card);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $card = GiftCard::findOrFail($id);

        $validated = $request->validate([
            'title'                => 'required|string|max:250',
            'discount'             => 'required|numeric|min:0',
            'minimum_order_amount' => 'required|numeric|min:0',
            'start_time'           => 'nullable|date',
            'end_time'             => 'nullable|date|after_or_equal:start_time',
        ]);

        $card->update($validated);

        return ApiResponseType::sendJsonResponse(true, 'Gift card updated.', $card);
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        try {
            GiftCard::findOrFail($id)->delete();
            return ApiResponseType::sendJsonResponse(true, 'Gift card deleted.');
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, 'Failed to delete: ' . $e->getMessage(), []);
        }
    }

    // ─── Validate barcode (API — used at checkout) ────────────────────────────

    public function validate(Request $request): JsonResponse
    {
        $request->validate(['barcode' => 'required|string']);

        $card = GiftCard::where('barcode', $request->barcode)->first();

        if (!$card) {
            return ApiResponseType::sendJsonResponse(false, 'Invalid gift card code.');
        }

        if ($card->isUsed()) {
            return ApiResponseType::sendJsonResponse(false, 'This gift card has already been used.');
        }

        if (!$card->isActive()) {
            return ApiResponseType::sendJsonResponse(false, 'This gift card is not currently active.');
        }

        return ApiResponseType::sendJsonResponse(true, 'Gift card is valid.', [
            'id'                   => $card->id,
            'title'                => $card->title,
            'discount'             => $card->discount,
            'minimum_order_amount' => $card->minimum_order_amount,
        ]);
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function renderActions(GiftCard $card): string
    {
        $editUrl   = route('admin.gift-cards.show', $card->id);
        $deleteUrl = route('admin.gift-cards.destroy', $card->id);

        return <<<HTML
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-url="{$editUrl}" title="Edit">
                    <i class="ti ti-pencil fs-5"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-url="{$deleteUrl}" title="Delete">
                    <i class="ti ti-trash fs-5"></i>
                </button>
            </div>
        HTML;
    }
}
