<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\SpatieMediaCollectionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Order\CreateItemReturnRequest;
use App\Http\Requests\User\Order\CreateOrderRequest;
use App\Http\Resources\User\OrderPaymentResource;
use App\Http\Resources\User\OrderResource;
use App\Models\Order;
use App\Models\SellerOrder;
use App\Models\User;
use App\Services\OrderService;
use App\Types\Api\ApiResponseType;
use Barryvdh\DomPDF\Facade\Pdf;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

#[Group('Orders')]
class OrderApiController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Create a new order
     *
     * Creates a new order from the user's cart with the provided payment and address information.
     */
    public function createOrder(CreateOrderRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }
        $result = $this->orderService->createOrder($user, $request->validated());

        // When successful, ensure we return a plain array including `slug` so
        // frontend callers (and non-Laravel JSON consumers) reliably receive it.
        if ($result['success'] && isset($result['data'])) {
            $resource = new OrderResource($result['data']);
            $payload = $resource->toArray($request);
        } else {
            $payload = $result['data'] ?? null;
        }

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $payload
        );
    }

    /**
     * Get order details
     *
     * Retrieves the details of a specific order by its slug.
     */
    public function getOrder(string $orderSlug): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }

        $result = $this->orderService->getOrder($user, $orderSlug);

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $result['success'] ? new OrderResource($result['data']) : $result['data']
        );
    }

    /**
     * Get user's orders
     *
     * Retrieves all orders for the authenticated user.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of Orders Per Page', type: 'int', default: 1, example: 1)]
    public function getUserOrders(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 15);

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }

        $result = $this->orderService->getUserOrders(user: $user, perPage: $perPage);
        $orders = $result['data'];
        $orders->getCollection()->transform(fn($order) => new OrderResource($order));
        return ApiResponseType::sendJsonResponse(
            success: $result['success'],
            message: $result['message'],
            data: ['current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'data' => $orders->items(),]
        );
    }

    /**
     * Get Order Delivery Boy Location
     *
     */

    public function getOrderDeliveryBoyLocation($orderSlug): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }

        if (!$orderSlug) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.order_slug_required'),
                []
            );
        }

        $result = $this->orderService->getOrderDeliveryBoyLocation(user: $user, orderSlug: $orderSlug);

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $result['data']
        );
    }

    /**
     * Cancel an order item
     *
     * Cancels a specific order item if it meets the cancellation criteria.
     */
    public function cancelOrderItem(int $orderItemId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }

        $result = $this->orderService->cancelOrderItem($user, $orderItemId);

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $result['data']
        );
    }

    /**
     * Get user's payment transactions
     *
     * Retrieves all payment transactions for the authenticated user.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of Orders Per Page', type: 'int', default: 1, example: 1)]
    #[QueryParameter('payment_status', description: 'Filter by payment status', type: 'string', example: 'completed')]
    #[QueryParameter('search', description: 'Search by transaction reference or other fields', type: 'string', example: 'TX123')]
    public function getTransactions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $perPage = $request->input('per_page', 15);
        $paymentStatus = $request->input('payment_status');
        $search = $request->input('search');

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }

        $query = $user->OrderPaymentTransactions()->latest();

        // Apply payment_status filter
        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        // Apply search filter (e.g. transaction reference or other fields)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('payment_status', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        // Paginate
        $transactions = $query->paginate($perPage);

        // Transform using resource collection
        $transactions->getCollection()->transform(function ($transaction) {
            return new OrderPaymentResource($transaction);
        });

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.transactions_retrieved_successfully'),
            [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'data' => $transactions->items(),
            ]
        );
    }

    /**
     * Get a specific payment transaction
     *
     * Retrieves details of a specific payment transaction by its ID.
     */
    public function getTransaction($id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }

        $transaction = $user->OrderPaymentTransactions()->where('id', $id)->first();

        if (!$transaction) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.transaction_not_found'),
                []
            );
        }

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.transaction_retrieved_successfully'),
            OrderPaymentResource::make($transaction)
        );
    }

    /**
     * Return Order Item
     *
     * Return an order item if it meets the return criteria.
     */
    public function returnOrderItem(int $orderItemId, CreateItemReturnRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }

        $validated = $request->validated();
        $validated['order_item_id'] = $orderItemId;

        $result = $this->orderService->returnOrderItem($user, $validated);

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $result['data'] ?? []
        );
    }

    /**
     * Download GST Invoice PDF
     *
     * Downloads the GST-compliant invoice PDF for a specific order belonging to the authenticated user.
     */
    public function downloadInvoice(string $orderSlug): Response
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, __('labels.user_not_authenticated'));
        }

        $sellerOrders = SellerOrder::with(
            'order',
            'seller',
            'order.promoLine',
            'items.product',
            'items.orderItem.store',
            'items.variant',
            'items.orderItem'
        )
            ->whereHas('order', fn($q) => $q->where('uuid', $orderSlug)->where('user_id', $user->id))
            ->get();

        if ($sellerOrders->isEmpty()) {
            abort(404, __('labels.order_not_found'));
        }

        foreach ($sellerOrders as $so) {
            if ($so->seller) {
                $so->seller->authorized_signature = $so->seller->getFirstMediaUrl(
                    SpatieMediaCollectionName::AUTHORIZED_SIGNATURE()
                ) ?? null;
            }
        }

        $order = $sellerOrders->first()->order;
        $systemSettingResource = app(\App\Services\SettingService::class)->getSettingByVariable('system');
        $systemSettings = $systemSettingResource?->toArray(request())['value'] ?? [];

        if (!$this->canCustomerDownloadInvoice($order->status, $systemSettings)) {
            abort(403, 'Invoice is available after order is marked as dispatched.');
        }

        $pdf = Pdf::loadView('layouts.order-invoice', [
            'order'          => $order,
            'sellerOrder'    => $sellerOrders,
            'systemSettings' => $systemSettings,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename = 'invoice-' . ($order->invoice_number ?? $order->order_number ?? $order->uuid ?? $order->id) . '.pdf';

        return $pdf->download($filename);
    }

    private function canCustomerDownloadInvoice(?string $currentStatus, array $systemSettings): bool
    {
        $enabled = (bool)($systemSettings['customerInvoiceDownloadEnabled'] ?? true);
        if (!$enabled) {
            return false;
        }

        $requiredStatus = $systemSettings['customerInvoiceDownloadMinStatus'] ?? 'out_for_delivery';
        $statusOrder = [
            'pending',
            'awaiting_store_response',
            'partially_accepted',
            'accepted_by_seller',
            'ready_for_pickup',
            'assigned',
            'preparing',
            'collected',
            'out_for_delivery',
            'delivered',
        ];

        $requiredIndex = array_search($requiredStatus, $statusOrder, true);
        if ($requiredIndex === false) {
            $requiredIndex = array_search('out_for_delivery', $statusOrder, true);
        }

        $currentIndex = array_search((string)$currentStatus, $statusOrder, true);
        if ($currentIndex === false) {
            return false;
        }

        return $currentIndex >= $requiredIndex;
    }

    /**
     * Cancel Return Request
     * @param $orderItemId
     * @return JsonResponse
     */

    public function cancelReturnRequest($orderItemId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }

        $result = $this->orderService->cancelReturnRequest(user:$user, orderItemId: $orderItemId);

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $result['data'] ?? []
        );
    }

    // ─── POST /api/orders/track (public — no auth required) ──────────────────────

    public function trackPublicOrder(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('query', ''));

        if (empty($query)) {
            return ApiResponseType::sendJsonResponse(false, 'Please provide an order number or tracking code.', []);
        }

        $order = Order::query()
            ->where('order_number', $query)
            ->orWhere('tracking_code', $query)
            ->with(['items.product', 'items.variant'])
            ->first();

        if (!$order) {
            return ApiResponseType::sendJsonResponse(false, 'Order not found.', []);
        }

        return ApiResponseType::sendJsonResponse(true, 'Order found.', new OrderResource($order));
    }
}
