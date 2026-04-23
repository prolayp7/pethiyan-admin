<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\AdminPermissionEnum;
use App\Enums\DateRangeFilterEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\Product\ProductTypeEnum;
use App\Enums\SellerPermissionEnum;
use App\Http\Resources\OrderResource;
use App\Enums\SpatieMediaCollectionName;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Services\CurrencyService;
use App\Services\OrderService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use PanelAware, AuthorizesRequests, ChecksPermissions;

    private const ADMIN_ORDER_STATUS_OPTIONS = [
        'accepted_by_seller' => 'Order Accepted',
        'preparing' => 'Order Start Packing',
        'ready_for_pickup' => 'Order Packing Done',
        'assigned' => 'Order Ready for Pickup',
        'collected' => 'Order Collected',
        'cancelled' => 'Order Cancelled',
        'failed' => 'Order Failed',
        'delivered' => 'Order Completed',
    ];

    public bool $editPermission = false;
    protected OrderService $orderService;
    protected CurrencyService $currencyService;

    public function __construct(OrderService $orderService, CurrencyService $currencyService)
    {
        $this->orderService = $orderService;
        $this->currencyService = $currencyService;
        $user = auth()->user();
        if ($user) {
            $this->editPermission = $this->hasPermission(SellerPermissionEnum::ORDER_EDIT()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
        }
    }

    /**
     * Display a listing of the seller's orders.
     *
     * @return View
     */
    public function index(): View
    {
        $this->authorize('viewAny', SellerOrder::class);

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'order_date', 'name' => 'order_date', 'title' => __('labels.order_date'), 'orderable' => false, 'searchable' => false],
            ['data' => 'order_details', 'name' => 'order_details', 'title' => __('labels.order_details'), 'orderable' => false, 'searchable' => false],
            ['data' => 'product_details', 'name' => 'product_details', 'title' => __('labels.product_details'), 'orderable' => false, 'searchable' => false],
            ['data' => 'promo', 'name' => 'promo', 'title' => 'Promo', 'orderable' => false, 'searchable' => false],
            ['data' => 'payment_status', 'name' => 'payment_status', 'title' => __('labels.payment_status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'actions', 'name' => 'actions', 'title' => __('labels.actions'), 'orderable' => false, 'searchable' => false],
        ];
        return view($this->panelView('orders.index'), compact('columns'));
    }

    /**
     * Get orders datatable data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrders(Request $request): JsonResponse
    {
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';
        $status = $request->get('status');
        $paymentType = $request->get('payment_type');
        $dateRange = $request->get('range');
        $promoCode = trim($request->get('promo_code', ''));

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';

        if ($this->getPanel() === 'admin') {
            $columns = ['id', 'created_at', 'slug', 'status', 'payment_status'];
            $orderColumn = $columns[$orderColumnIndex] ?? 'id';

            $query = Order::with([
                'items.product',
                'items.variant',
                'items.store',
                'user',
            ]);

            $totalRecords = $query->count();

            if ($status !== null && $status !== '') {
                $query->where('status', $status);
            }

            if ($paymentType !== null && $paymentType !== '') {
                $query->where('payment_method', $paymentType);
            }

            if ($dateRange !== null && $dateRange !== '') {
                $fromDate = $this->getDateRange($dateRange);
                if ($fromDate) {
                    $query->where('created_at', '>=', $fromDate);
                }
            }

            if (!empty($promoCode)) {
                $query->where('promo_code', 'like', "%$promoCode%");
            }

            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('id', 'like', "%$searchValue%")
                        ->orWhere('slug', 'like', "%$searchValue%")
                        ->orWhere('payment_method', 'like', "%$searchValue%")
                        ->orWhere('status', 'like', "%$searchValue%")
                        ->orWhere('shipping_name', 'like', "%$searchValue%")
                        ->orWhereHas('items.product', function ($productQuery) use ($searchValue) {
                            $productQuery->where('title', 'like', "%$searchValue%");
                        })
                        ->orWhereHas('items.variant', function ($variantQuery) use ($searchValue) {
                            $variantQuery->where('title', 'like', "%$searchValue%");
                        });
                });
            }

            $filteredRecords = $query->count();

            $data = $query
                ->orderBy($orderColumn, $orderDirection)
                ->skip($start)
                ->take($length)
                ->get()
                ->map(function (Order $order) {
                    return $this->getAdminOrderReturnData($order);
                });

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        }

        $columns = ['id', 'order_id', 'price', 'status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = SellerOrderItem::with(['sellerOrder', 'orderItem', 'orderItem.store', 'variant', 'product'])
            ->whereHas('product', function ($q) {
                $q->whereNotNull('id');
            });

        if ($this->getPanel() === 'seller') {
            $user = auth()->user();
            $seller = $user?->seller();

            if (!$seller) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.seller_not_found'),
                    data: []
                );
            }

            $query->whereHas('sellerOrder', function ($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            });
            $query->whereHas('orderItem', function ($q) {
                $q->where('status', '!=', OrderItemStatusEnum::PENDING());
            });
        }
        $totalRecords = $query->count();

        // Filter by status if provided
        if ($status !== null && $status !== '') {
            $query->whereHas('orderItem', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }
        // Filter by status if provided
        if ($paymentType !== null && $paymentType !== '') {
            $query->whereHas('sellerOrder', function ($q) use ($paymentType) {
                $q->whereHas('order', function ($q) use ($paymentType) {
                    $q->where('payment_method', $paymentType);
                });
            });
        }

        // Filter by date range if provided
        if ($dateRange !== null && $dateRange !== '') {

            $fromDate = $this->getDateRange($dateRange);
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            }
        }

        // Filter by promo code
        if (!empty($promoCode)) {
            $query->whereHas('sellerOrder.order', function ($q) use ($promoCode) {
                $q->where('promo_code', 'like', "%$promoCode%");
            });
        }

        // Search functionality
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('seller_order_id', 'like', "%$searchValue%")
                    ->orWhereHas('sellerOrder', function ($orderQuery) use ($searchValue) {
                        $orderQuery->where('total_price', 'like', "%$searchValue%")
                            ->orWhereHas('order', function ($orderQuery) use ($searchValue) {
                                $orderQuery->where('shipping_name', 'like', "%$searchValue%");
                            });
                    })
                    ->orWhereHas('orderItem', function ($orderItemQuery) use ($searchValue) {
                        $orderItemQuery->where('status', 'like', "%$searchValue%");
                    })
                    ->orWhereHas('product', function ($productQuery) use ($searchValue) {
                        $productQuery->where('title', 'like', "%$searchValue%");
                    })
                    ->orWhereHas('variant', function ($variantQuery) use ($searchValue) {
                        $variantQuery->where('title', 'like', "%$searchValue%");
                    });
            });
        }
        $filteredRecords = $query->count();

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($sellerOrderItem) {
                return $this->getOrderReturnData($sellerOrderItem);
            });

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    private function getAdminOrderReturnData(Order $order): array
    {
        $orderNo = $order->slug ?: $order->id;
        $orderNoDisplay = $orderNo ? '#' . ltrim((string) $orderNo, '#') : 'N/A';

        $orderSubtotal = (float) ($order->subtotal ?? $order->sub_total ?? 0);
        $orderShipping = (float) ($order->delivery_charge ?? 0);
        $orderHandling = (float) ($order->handling_charges ?? 0);
        $orderDropOff = (float) ($order->per_store_drop_off_fee ?? 0);
        $orderGst = (float) ($order->total_gst ?? 0);
        $orderPromo = (float) ($order->promo_discount ?? 0);
        $orderGift = (float) ($order->gift_card_discount ?? 0);
        $orderTotalPayable = (float) ($order->total_payable ?? $order->final_total ?? 0);

        $amountBreakdownHtml = "<div class='mt-1'>
                        <p class='m-0'>" . __('labels.subtotal') . ": " . $this->currencyService->format($orderSubtotal) . "</p>
                        <p class='m-0'>Shipping Cost: " . $this->currencyService->format($orderShipping) . "</p>" .
                        ($orderHandling > 0 ? "<p class='m-0'>" . __('labels.handling_charges') . ": " . $this->currencyService->format($orderHandling) . "</p>" : "") .
                        ($orderDropOff > 0 ? "<p class='m-0'>" . __('labels.per_store_drop_off_fee') . ": " . $this->currencyService->format($orderDropOff) . "</p>" : "") .
                        ($orderGst > 0 ? "<p class='m-0'>GST: " . $this->currencyService->format($orderGst) . "</p>" : "") .
                        ($orderPromo > 0 ? "<p class='m-0'>" . __('labels.promo_discount') . ": - " . $this->currencyService->format($orderPromo) . "</p>" : "") .
                        ($orderGift > 0 ? "<p class='m-0'>Gift Card Discount: - " . $this->currencyService->format($orderGift) . "</p>" : "") . "
                        <p class='m-0 fw-bold'>" . __('labels.total_payable') . ": " . $this->currencyService->format($orderTotalPayable) . "</p>
                        </div>";

        $productDetailsHtml = $order->items
            ->take(2)
            ->map(function ($orderItem) {
                $product = $orderItem->product;
                $variantTitle = $orderItem->variant?->title ?? '';
                $storeName = $orderItem->store?->name ?? 'N/A';

                return "<div class='mb-2'>
                        " . ($product
                            ? "<a href='" . route('admin.products.show', ['id' => $product->id]) . "' class='m-0 fw-medium text-primary'>" . __('labels.product_name') . ": {$product->title}</a>"
                            : "<p class='m-0 fw-medium text-primary'>" . __('labels.product_name') . ": N/A</p>") . "
                        <p class='m-0 fw-medium text-primary'>" . __('labels.variant_name') . ": " . e($variantTitle) . "</p>
                        <p class='m-0 fw-medium text-capitalize'>" . __('labels.store_name') . ": " . e($storeName) . "</p>
                        <p class='m-0'>" . __('labels.sku') . ": " . e($orderItem->sku ?? 'N/A') . "</p>
                        <p class='m-0 fw-medium'>" . __('labels.quantity') . ": " . e((string) ($orderItem->quantity ?? 0)) . "</p>
                        <p class='m-0 fw-medium'>" . __('labels.item_sub_total') . ": " . $this->currencyService->format($orderItem->subtotal ?? 0) . "</p>
                        </div>";
            })
            ->implode("<hr class='my-2'>");

        if ($order->items->count() > 2) {
            $remaining = $order->items->count() - 2;
            $productDetailsHtml .= "<p class='m-0 text-muted small'>+" . e((string) $remaining) . " more item(s)</p>";
        }

        return [
            'id' => $order->id,
            'order_date' =>
                "<div><p class='m-0 fw-medium'>" . $order->created_at->diffForHumans() . "</p>
                        {$order->created_at->format('Y-m-d H:i:s')}
                        </div>",
            'order_details' => "<div>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.order_number') . ": " . e($orderNoDisplay) . "</p>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.order_id') . ": " . e((string) $order->id) . "</p>
                        <p class='m-0'>" . __('labels.buyer_name') . ": " . e($order->shipping_name ?? 'N/A') . "</p>
                        <p class='m-0'>" . __('labels.payment_method') . ": " . e($order->payment_method ?? 'N/A') . "</p>
                        <p class='m-0'>" . __('labels.is_rush_order') . ": " . (($order->is_rush_order ?? false) ? 'Yes' : 'No') . "</p>
                        <p class='m-0'>" . __('labels.order_status') . ": " . Str::ucfirst(Str::replace("_", " ", $order->status ?? 'pending')) . "</p>" .
                        $amountBreakdownHtml .
                        "</div>",
            'product_details' => $productDetailsHtml,
            'promo' => $orderPromo > 0
                ? "<div class='text-center'>
                     <span class='badge bg-green-lt text-uppercase fw-bold'>" . e($order->promo_code ?? '') . "</span>
                     <div class='text-danger small mt-1'>−" . $this->currencyService->format($orderPromo) . "</div>
                   </div>"
                : "<span class='text-muted'>—</span>",
            'payment_status' => $this->renderPaymentStatusBadge((string) ($order->payment_status ?? PaymentStatusEnum::PENDING())),
            'actions' => view('partials.order-actions', [
                'panel' => 'admin',
                'uuid' => $order->uuid ?? '',
                'id' => $order->id,
                'hierarchy' => OrderItem::getStatusHierarchy(),
                'route' => route('admin.orders.show', $order->id),
                'title' => __('labels.edit_order') . $order->id,
                'status' => $order->status ?? OrderItemStatusEnum::PENDING(),
                'editPermission' => false,
            ])->render(),
        ];
    }

    private function getOrderReturnData($sellerOrderItem): array
    {
        $product = $sellerOrderItem->product;
        $variant = $sellerOrderItem->variant;
        $orderItem = $sellerOrderItem->orderItem;
        $sellerOrder = $sellerOrderItem->sellerOrder;
        $order = $sellerOrder?->order;
        $orderNo = $order?->slug ?: $order?->order_id ?: $sellerOrder?->order_id;
        $orderNoDisplay = $orderNo ? '#' . ltrim((string)$orderNo, '#') : 'N/A';

        $variantTitle = $product && $product->type !== ProductTypeEnum::SIMPLE() ? ($variant?->title ?? "") : "";
        $storeName = $orderItem?->store?->name ?? 'N/A';
        $orderNote = !empty($order?->order_note) ? "<textarea class='form-control' rows='1' readonly disabled>order note:- {$order->order_note}</textarea>" : null;
        $productImage = !empty($variant?->image) ? $variant->image : ($product?->main_image ?? null);
        $orderSubtotal = (float)($order?->subtotal ?? $order?->sub_total ?? 0);
        $orderShipping = (float)($order?->delivery_charge ?? 0);
        $orderHandling = (float)($order?->handling_charges ?? 0);
        $orderDropOff = (float)($order?->per_store_drop_off_fee ?? 0);
        $orderGst = (float)($order?->total_gst ?? 0);
        $orderPromo = (float)($order?->promo_discount ?? 0);
        $orderGift = (float)($order?->gift_card_discount ?? 0);
        $orderTotalPayable = (float)($order?->total_payable ?? $order?->final_total ?? 0);

        $amountBreakdownHtml = "<div class='mt-1'>
                        <p class='m-0'>" . __('labels.subtotal') . ": " . $this->currencyService->format($orderSubtotal) . "</p>
                        <p class='m-0'>Shipping Cost: " . $this->currencyService->format($orderShipping) . "</p>" .
                        ($orderHandling > 0 ? "<p class='m-0'>" . __('labels.handling_charges') . ": " . $this->currencyService->format($orderHandling) . "</p>" : "") .
                        ($orderDropOff > 0 ? "<p class='m-0'>" . __('labels.per_store_drop_off_fee') . ": " . $this->currencyService->format($orderDropOff) . "</p>" : "") .
                        ($orderGst > 0 ? "<p class='m-0'>GST: " . $this->currencyService->format($orderGst) . "</p>" : "") .
                        ($orderPromo > 0 ? "<p class='m-0'>" . __('labels.promo_discount') . ": - " . $this->currencyService->format($orderPromo) . "</p>" : "") .
                        ($orderGift > 0 ? "<p class='m-0'>Gift Card Discount: - " . $this->currencyService->format($orderGift) . "</p>" : "") . "
                        <p class='m-0 fw-bold'>" . __('labels.total_payable') . ": " . $this->currencyService->format($orderTotalPayable) . "</p>
                        </div>";

        $orderRoute = $this->getPanel() === 'seller'
            ? ($sellerOrder?->id ?? $sellerOrderItem->seller_order_id)
            : ($sellerOrder?->order_id ?? $sellerOrderItem->seller_order_id);

        return [
            'id' => $sellerOrderItem->order_item_id,
            'order_date' =>
                "<div><p class='m-0 fw-medium'>" . $sellerOrderItem->created_at->diffForHumans() . "</p>
                        {$sellerOrderItem->created_at->format('Y-m-d H:i:s')}
                        </div>",
            'order_details' => "<div class='d-flex justify-content-start align-items-center'><div class='pe-2'>" .
                view('partials.image', [
                    'image' => $productImage,
                ])->render() .
                "</div><div>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.order_number') . ": " . e($orderNoDisplay) . "</p>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.order_id') . ": " . e($sellerOrder?->order_id ?? 'N/A') . "</p>
                        <p class='m-0'>" . __('labels.buyer_name') . ": " . e($order?->shipping_name ?? 'N/A') . "</p>
                        <p class='m-0'>" . __('labels.payment_method') . ": " . e($order?->payment_method ?? 'N/A') . "</p>
                        <p class='m-0'>" . __('labels.is_rush_order') . ": " . (($order?->is_rush_order ?? false) ? 'Yes' : 'No') . "</p>
                        <p class='m-0'>" . __('labels.order_status') . ": " . Str::ucfirst(Str::replace("_", " ", $order?->status ?? 'pending')) . "</p>" .
                        $amountBreakdownHtml
                        . $orderNote .
                        "</div></div>",
            'product_details' => "<div>" .
                        ($product
                            ? "<a href='" . route($this->getPanel() . '.products.show', ['id' => $product->id]) . "' class='m-0 fw-medium text-primary'>" . __('labels.product_name') . ": {$product->title}</a>"
                            : "<p class='m-0 fw-medium text-primary'>" . __('labels.product_name') . ": N/A</p>") . "
                        <p class='m-0 fw-medium text-primary'>" . __('labels.variant_name') . ": $variantTitle</p>
                        <p class='m-0 fw-medium text-capitalize'>" . __('labels.store_name') . ": $storeName</p>
                        <p class='m-0'>" . __('labels.sku') . ": " . e($orderItem?->sku ?? 'N/A') . "</p>
                        <p class='m-0 fw-medium'>" . __('labels.quantity') . ": " . e((string)($orderItem?->quantity ?? 0)) . "</p>
                        <p class='m-0 fw-medium'>" . __('labels.item_sub_total') . ": " . $this->currencyService->format($orderItem?->subtotal ?? 0) . "</p>
                        </div>",
            'promo' => $orderPromo > 0
                ? "<div class='text-center'>
                     <span class='badge bg-green-lt text-uppercase fw-bold'>" . e($order?->promo_code ?? '') . "</span>
                     <div class='text-danger small mt-1'>−" . $this->currencyService->format($orderPromo) . "</div>
                   </div>"
                : "<span class='text-muted'>—</span>",
            'payment_status' => $this->renderPaymentStatusBadge((string) ($order?->payment_status ?? PaymentStatusEnum::PENDING())),
            'actions' => view('partials.order-actions', [
                'panel' => $this->getPanel(),
                'uuid' => $order?->uuid ?? '',
                'id' => $orderItem?->id ?? $sellerOrderItem->order_item_id,
                'hierarchy' => OrderItem::getStatusHierarchy(),
                'route' => route($this->panelView('orders.show'), $orderRoute),
                'title' => __('labels.edit_order') . ($sellerOrder?->id ?? ''),
                'status' => $orderItem?->status ?? OrderItemStatusEnum::PENDING(),
                'editPermission' => $this->getPanel() === 'admin' ? false : $this->editPermission,
            ])->render(),
        ];
    }

    private function renderPaymentStatusBadge(string $paymentStatus): string
    {
        $color = match ($paymentStatus) {
            PaymentStatusEnum::COMPLETED(), 'paid' => 'green',
            PaymentStatusEnum::FAILED() => 'red',
            PaymentStatusEnum::REFUNDED(), PaymentStatusEnum::PARTIALLY_REFUNDED() => 'azure',
            default => 'yellow',
        };

        return '<span class="badge bg-' . $color . '-lt text-uppercase">' . e(Str::replace('_', ' ', $paymentStatus)) . '</span>';
    }

    private function getDateRange($dateRange): ?Carbon
    {
        $fromDate = null;
        $now = Carbon::now();
        switch ($dateRange) {
            case DateRangeFilterEnum::LAST_30_MINUTES():
                $fromDate = $now->copy()->subMinutes(30);
                break;
            case DateRangeFilterEnum::LAST_1_HOUR():
                $fromDate = $now->copy()->subHour();
                break;
            case DateRangeFilterEnum::LAST_5_HOURS():
                $fromDate = $now->copy()->subHours(5);
                break;
            case DateRangeFilterEnum::LAST_1_DAY():
                $fromDate = $now->copy()->subDay();
                break;
            case DateRangeFilterEnum::LAST_7_DAYS():
                $fromDate = $now->copy()->subDays(7);
                break;
            case DateRangeFilterEnum::LAST_30_DAYS():
                $fromDate = $now->copy()->subDays(30);
                break;
            case DateRangeFilterEnum::LAST_365_DAYS():
                $fromDate = $now->copy()->subDays(365);
                break;
        }
        return $fromDate;
    }


    /**
     * Display the specified order.
     *
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        if ($this->getPanel() === 'seller') {
            $user = auth()->user();
            $seller = $user?->seller();

            if (!$seller) {
                abort(404, __('labels.seller_not_found'));
            }
            $order = SellerOrder::where('id', $id)
                ->with(['order', 'items.product', 'items.variant', 'items.orderItem', 'order.items.store'])
                ->where('seller_id', $seller->id)
                ->firstOrFail();
        } else {
            $order = Order::with([
                'user',
                'items',
                'items.product',
                'items.variant',
                'items.store',
                'promoLine',
                'paymentTransactions' => fn($query) => $query->latest()->with(['settlements', 'order']),
            ])
                ->findOrFail($id);
        }
        $this->authorize('view', $order);
        // Transform the order data using the resource
        $orderData = new OrderResource($order);
        $adminOrderStatusOptions = $this->getAdminOrderStatusOptions((string) $order->status);

        return view($this->panelView('orders.show'), [
            'order' => $orderData->toArray(request()),
            'canManageOrder' => $this->canManageAdminOrder(),
            'orderStatusOptions' => $adminOrderStatusOptions,
            'paymentStatusOptions' => PaymentStatusEnum::values(),
            'isCodOrder' => strtolower((string)$order->payment_method) === PaymentTypeEnum::COD(),
            'currentOrderStatusLabel' => $this->resolveAdminOrderStatusLabel((string) $order->status),
        ]);
    }

    public function updateAdminOrder(Request $request, int $id): RedirectResponse
    {
        if ($this->getPanel() === 'seller') {
            abort(404);
        }

        $order = Order::findOrFail($id);
        $this->authorize('view', $order);

        $allowedStatusValues = array_keys($this->getAdminOrderStatusOptions((string) $order->status));

        $validated = $request->validate([
            'status' => ['required', Rule::in($allowedStatusValues)],
            'payment_status' => ['nullable', Rule::in(PaymentStatusEnum::values())],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'tracking_code' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->orderService->updateOrderByAdmin($order, $validated, Auth::id());

        return redirect()
            ->route('admin.orders.show', $order->id)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function downloadShippingAddress(int $id)
    {
        if ($this->getPanel() === 'seller') {
            abort(404);
        }

        $order = Order::with('user')->findOrFail($id);
        $this->authorize('view', $order);

        $pdf = Pdf::loadView('admin.orders.shipping-address-pdf', [
            'order' => $order,
        ])->setPaper('a5', 'portrait');

        return $pdf->download('shipping-address-' . $order->order_number . '.pdf');
    }

    private function getAdminOrderStatusOptions(?string $currentStatus = null): array
    {
        $options = self::ADMIN_ORDER_STATUS_OPTIONS;

        if ($currentStatus !== null && !array_key_exists($currentStatus, $options)) {
            $options = [$currentStatus => Str::headline($currentStatus)] + $options;
        }

        return $options;
    }

    private function resolveAdminOrderStatusLabel(string $status): string
    {
        return $this->getAdminOrderStatusOptions($status)[$status] ?? Str::headline($status);
    }

    /**
     * Update the order status.
     *
     * @param int $id
     * @param string $status
     * @return JsonResponse
     */
    public function updateStatus(int $id, string $status): JsonResponse
    {
        try {
            $seller = auth()->user()->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'));
            }

            // Find the order item to authorize the action
            $orderItem = SellerOrderItem::where('order_item_id', $id)
                ->whereHas('sellerOrder', function ($q) use ($seller) {
                    $q->where('seller_id', $seller->id);
                })
                ->first();

            if (!$orderItem) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.order_item_not_found'),
                    data: []
                );
            }

            $this->authorize('updateStatus', $orderItem);

            // Use the OrderService to update the status
            $result = $this->orderService->updateOrderStatusBySeller($id, $status, $seller->id);
            if (!$result['success']) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: $result['message'],
                    data: $result['data'],
                );
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.unauthorized_action'),
                data: []
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.order_status_update_failed'),
                data: []
            );
        }
    }

    public function orderInvoice(Request $request): View
    {
        try {
            $orderId = $request->input('id');
            $sellerOrder = SellerOrder::with('order', 'seller', 'order.promoLine', 'items.product', 'items.orderItem.store', 'items.variant', 'items.orderItem')
                ->whereHas('order', function ($q) use ($orderId) {
                    $q->where('uuid', $orderId);
                })
                ->get();
            if (count($sellerOrder) === 0) {
                abort(404, __('labels.order_not_found'));
            }
            foreach ($sellerOrder as $so) {
                if ($so->seller) {
                    $so->seller->authorized_signature = $so->seller->getFirstMediaUrl(SpatieMediaCollectionName::AUTHORIZED_SIGNATURE()) ?? null;
                }
            }
            $orderData = $sellerOrder->first()->order;
            $systemSettingResource = app(\App\Services\SettingService::class)
                ->getSettingByVariable('system');
            $systemSettings = $systemSettingResource?->toArray(request())['value'] ?? [];

            return view('layouts.order-invoice', [
                'order'          => $orderData,
                'sellerOrder'    => $sellerOrder,
                'systemSettings' => $systemSettings,
            ]);
        } catch (AuthorizationException) {
            abort(403, __('messages.unauthorized_action'));
        }
    }

    /**
     * Download a GST-compliant PDF invoice for an order.
     * Route: GET /admin/orders/{id}/invoice/download
     */
    public function downloadInvoice(int $id)
    {
        try {
            $this->authorize('viewAny', Order::class);

            $sellerOrder = SellerOrder::with(
                'order',
                'seller',
                'order.promoLine',
                'items.product',
                'items.orderItem.store',
                'items.variant',
                'items.orderItem'
            )
                ->whereHas('order', fn($q) => $q->where('id', $id))
                ->get();

            if ($sellerOrder->isEmpty()) {
                abort(404, __('labels.order_not_found'));
            }

            foreach ($sellerOrder as $so) {
                if ($so->seller) {
                    $so->seller->authorized_signature = $so->seller->getFirstMediaUrl(
                        SpatieMediaCollectionName::AUTHORIZED_SIGNATURE()
                    ) ?? null;
                }
            }

            $order = $sellerOrder->first()->order;
            $systemSettingResource = app(\App\Services\SettingService::class)
                ->getSettingByVariable('system');
            $systemSettings = $systemSettingResource?->toArray(request())['value'] ?? [];

            $pdf = Pdf::loadView('layouts.order-invoice', [
                'order'          => $order,
                'sellerOrder'    => $sellerOrder,
                'systemSettings' => $systemSettings,
            ])
                ->setPaper('a4', 'portrait')
                ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

            $filename = 'invoice-' . ($order->invoice_number ?? $order->order_number ?? $order->uuid ?? $order->id) . '.pdf';

            return $pdf->download($filename);

        } catch (AuthorizationException) {
            abort(403, __('messages.unauthorized_action'));
        }
    }

    private function canManageAdminOrder(): bool
    {
        if ($this->getPanel() === 'seller' || !Auth::check()) {
            return false;
        }

        return $this->hasPermission(AdminPermissionEnum::ORDER_VIEW());
    }
}
