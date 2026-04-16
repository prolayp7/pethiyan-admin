<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DateRangeFilterEnum;
use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPromoLine;
use App\Models\OrderPaymentTransaction;
use App\Models\PaymentDispute;
use App\Models\PaymentRefund;
use App\Models\PaymentSettlement;
use App\Models\PaymentWebhookLog;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use AuthorizesRequests;

    // ── Views ──────────────────────────────────────────────────────────────

    public function sales(): View
    {
        return view('admin.reports.sales');
    }

    public function orders(): View
    {
        return view('admin.reports.orders');
    }

    public function products(): View
    {
        return view('admin.reports.products');
    }

    public function customers(): View
    {
        return view('admin.reports.customers');
    }

    public function promos(): View
    {
        return view('admin.reports.promos');
    }

    public function payments(): View
    {
        $summary = [
            'transactions' => OrderPaymentTransaction::count(),
            'refunds' => PaymentRefund::count(),
            'disputes' => PaymentDispute::count(),
            'settlements' => PaymentSettlement::count(),
            'webhook_logs' => PaymentWebhookLog::count(),
        ];

        return view('admin.reports.payments', [
            'summary' => $summary,
            'paymentMethodOptions' => [
                PaymentTypeEnum::COD() => 'COD',
                PaymentTypeEnum::RAZORPAY() => 'Razorpay',
                PaymentTypeEnum::EASEPAY() => 'Easebuzz',
            ],
            'paymentStatusOptions' => array_values(array_unique(array_merge(
                PaymentStatusEnum::values(),
                ['created', 'processed', 'processing', 'received', 'rejected', 'under_review', 'won', 'lost', 'closed', 'action_required', 'settled']
            ))),
            'dateRangeOptions' => DateRangeFilterEnum::values(),
            'transactionColumns' => [
                ['label' => 'ID', 'data' => 'id'],
                ['label' => __('labels.order'), 'data' => 'order'],
                ['label' => __('labels.transaction_id'), 'data' => 'transaction_id'],
                ['label' => __('labels.payment_method'), 'data' => 'payment_method'],
                ['label' => __('labels.payment_status'), 'data' => 'payment_status'],
                ['label' => __('labels.amount'), 'data' => 'amount'],
                ['label' => __('labels.updated_at'), 'data' => 'updated_at'],
                ['label' => __('labels.details'), 'data' => 'details'],
            ],
            'refundColumns' => [
                ['label' => 'ID', 'data' => 'id'],
                ['label' => __('labels.order'), 'data' => 'order'],
                ['label' => __('labels.payment_id'), 'data' => 'payment_id'],
                ['label' => __('labels.refund_id'), 'data' => 'refund_id'],
                ['label' => __('labels.status'), 'data' => 'status'],
                ['label' => __('labels.amount'), 'data' => 'amount'],
                ['label' => __('labels.speed'), 'data' => 'speed'],
                ['label' => __('labels.updated_at'), 'data' => 'updated_at'],
            ],
            'disputeColumns' => [
                ['label' => 'ID', 'data' => 'id'],
                ['label' => __('labels.order'), 'data' => 'order'],
                ['label' => __('labels.payment_id'), 'data' => 'payment_id'],
                ['label' => __('labels.dispute_id'), 'data' => 'dispute_id'],
                ['label' => __('labels.status'), 'data' => 'status'],
                ['label' => __('labels.amount'), 'data' => 'amount'],
                ['label' => __('labels.reason'), 'data' => 'reason'],
                ['label' => __('labels.respond_by'), 'data' => 'respond_by'],
                ['label' => __('labels.updated_at'), 'data' => 'updated_at'],
            ],
            'settlementColumns' => [
                ['label' => 'ID', 'data' => 'id'],
                ['label' => __('labels.order'), 'data' => 'order'],
                ['label' => __('labels.payment_id'), 'data' => 'payment_id'],
                ['label' => __('labels.settlement_id'), 'data' => 'settlement_id'],
                ['label' => __('labels.status'), 'data' => 'status'],
                ['label' => __('labels.amount'), 'data' => 'amount'],
                ['label' => __('labels.reference'), 'data' => 'reference'],
                ['label' => __('labels.settled_at'), 'data' => 'settled_at'],
                ['label' => __('labels.updated_at'), 'data' => 'updated_at'],
            ],
            'webhookLogColumns' => [
                ['label' => 'ID', 'data' => 'id'],
                ['label' => __('labels.payment_method'), 'data' => 'gateway'],
                ['label' => __('labels.webhook_event'), 'data' => 'event_name'],
                ['label' => __('labels.order'), 'data' => 'order'],
                ['label' => __('labels.transaction_id'), 'data' => 'transaction_id'],
                ['label' => __('labels.status'), 'data' => 'status'],
                ['label' => __('labels.signature'), 'data' => 'signature'],
                ['label' => __('labels.updated_at'), 'data' => 'updated_at'],
                ['label' => __('labels.details'), 'data' => 'details'],
            ],
        ]);
    }

    // ── Data endpoints ─────────────────────────────────────────────────────

    /**
     * Sales revenue grouped by day within [from, to].
     */
    public function salesData(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        $to   = Carbon::parse($request->input('to',   now()->toDateString()))->endOfDay();

        $rows = Order::whereBetween('created_at', [$from, $to])
            ->where('payment_status', 'completed')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(final_total) as revenue'),
                DB::raw('COUNT(*) as order_count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $summary = Order::whereBetween('created_at', [$from, $to])
            ->where('payment_status', 'completed')
            ->selectRaw('SUM(final_total) as total_revenue, COUNT(*) as total_orders, AVG(final_total) as avg_order_value')
            ->first();

        return response()->json([
            'chart'   => $rows,
            'summary' => $summary,
        ]);
    }

    public function exportSales(Request $request): StreamedResponse
    {
        $from = Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $rows = Order::whereBetween('created_at', [$from, $to])
            ->where('payment_status', 'completed')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(final_total) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    $row->date,
                    $row->order_count,
                    $row->revenue,
                ];
            })
            ->all();

        return $this->streamCsvDownload(
            filename: 'sales-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv',
            headers: ['Date', 'Orders', 'Revenue'],
            rows: $rows,
        );
    }

    /**
     * Orders grouped by status within [from, to].
     */
    public function ordersData(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        $to   = Carbon::parse($request->input('to',   now()->toDateString()))->endOfDay();

        $byStatus = OrderItem::whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        $byDay = Order::whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'by_status' => $byStatus,
            'by_day'    => $byDay,
        ]);
    }

    public function exportOrders(Request $request): StreamedResponse
    {
        $from = Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $rows = [];
        $serialNumber = 1;

        Order::with(['user:id,name,email,mobile,company_name,gstin', 'items'])
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('id')
            ->chunk(100, function ($orders) use (&$rows, &$serialNumber) {
                foreach ($orders as $order) {
                    foreach ($order->items as $item) {
                        $deliveryAddress = collect([
                            $order->shipping_address_1,
                            $order->shipping_address_2,
                            $order->shipping_landmark,
                            $order->shipping_city,
                            $order->shipping_state,
                            $order->shipping_zip,
                            $order->shipping_country,
                        ])->filter(fn ($value) => filled($value))->implode(', ');

                        $rows[] = [
                            $serialNumber++,
                            $order->created_at?->format('Y-m-d'),
                            $order->invoice_number,
                            $order->order_number,
                            $order->user?->name ?? $order->billing_name,
                            $order->user?->email ?? $order->email,
                            $order->user?->mobile ?? $order->billing_phone,
                            $deliveryAddress,
                            $order->user?->company_name ?? '',
                            $order->user?->gstin ?? '',
                            $order->shipping_state,
                            $order->shipping_zip,
                            $item->title ?: ($item->variant_title ?: 'N/A'),
                            $item->quantity,
                            $item->price,
                            $order->subtotal,
                            $order->delivery_charge,
                            ($order->promo_discount ?? 0) + ($order->gift_card_discount ?? 0),
                            $order->final_total,
                        ];
                    }
                }
            });

        return $this->streamCsvDownload(
            filename: 'orders-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv',
            headers: [
                'Sr. No',
                'Order Date',
                'Invoice No',
                'Order Number',
                'User Name',
                'User Email',
                'User Mobile',
                'Delivery Address',
                'Company Name',
                'GST Number',
                'State',
                'Pincode',
                'Product Name',
                'Quantity',
                'Amount',
                'Sub Total Amount',
                'Shipping Amount',
                'Discount Amount',
                'Total',
            ],
            rows: $rows,
        );
    }

    /**
     * Top selling products by quantity sold within [from, to].
     */
    public function productsData(Request $request): JsonResponse
    {
        $from  = Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        $to    = Carbon::parse($request->input('to',   now()->toDateString()))->endOfDay();
        $limit = (int)$request->input('limit', 15);

        $topProducts = OrderItem::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'failed', 'returned'])
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as qty_sold'),
                DB::raw('SUM(price * quantity) as revenue')
            )
            ->groupBy('product_id')
            ->orderByDesc('qty_sold')
            ->limit($limit)
            ->with('product:id,title,slug')
            ->get();

        return response()->json(['products' => $topProducts]);
    }

    public function exportProducts(Request $request): StreamedResponse
    {
        $from = Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
        $limit = (int) $request->input('limit', 15);

        $rows = OrderItem::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'failed', 'returned'])
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as qty_sold'),
                DB::raw('SUM(price * quantity) as revenue')
            )
            ->groupBy('product_id')
            ->orderByDesc('qty_sold')
            ->limit($limit)
            ->with('product:id,title,slug')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                return [
                    $index + 1,
                    $row->product_id,
                    $row->product?->title ?? '—',
                    $row->product?->slug ?? '—',
                    $row->qty_sold,
                    $row->revenue,
                ];
            })
            ->all();

        return $this->streamCsvDownload(
            filename: 'products-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv',
            headers: ['Rank', 'Product ID', 'Product Name', 'Slug', 'Qty Sold', 'Revenue'],
            rows: $rows,
        );
    }

    /**
     * New vs returning customers, registrations by day.
     */
    public function customersData(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        $to   = Carbon::parse($request->input('to',   now()->toDateString()))->endOfDay();

        $registrations = User::whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $summary = [
            'total_customers'    => User::count(),
            'new_in_period'      => User::whereBetween('created_at', [$from, $to])->count(),
            'customers_with_orders' => Order::whereBetween('created_at', [$from, $to])
                ->distinct('user_id')
                ->count('user_id'),
        ];

        return response()->json([
            'registrations' => $registrations,
            'summary'       => $summary,
        ]);
    }

    public function exportCustomers(Request $request): StreamedResponse
    {
        $from = Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $rows = User::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('users.id', 'users.name', 'users.email', 'users.mobile', 'users.gstin', 'users.created_at')
            ->selectSub(
                Order::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('orders.user_id', 'users.id'),
                'orders_count'
            )
            ->selectSub(
                Order::query()
                    ->selectRaw('COALESCE(SUM(final_total), 0)')
                    ->whereColumn('orders.user_id', 'users.id'),
                'total_spent'
            )
            ->orderByDesc('users.created_at')
            ->get()
            ->values()
            ->map(function ($user, $index) {
                return [
                    $index + 1,
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->mobile,
                    $user->gstin,
                    $user->created_at?->format('Y-m-d H:i:s'),
                    $user->orders_count,
                    $user->total_spent,
                ];
            })
            ->all();

        return $this->streamCsvDownload(
            filename: 'customers-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv',
            headers: ['Sr. No', 'Customer ID', 'Name', 'Email', 'Mobile', 'GSTIN', 'Registered At', 'Orders Count', 'Total Spent'],
            rows: $rows,
        );
    }

    /**
     * Promo code performance report.
     */
    public function promosData(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        $to   = Carbon::parse($request->input('to',   now()->toDateString()))->endOfDay();

        // Per-code breakdown from order_promo_line joined with orders
        $byCode = OrderPromoLine::whereBetween('order_promo_line.created_at', [$from, $to])
            ->join('orders', 'orders.id', '=', 'order_promo_line.order_id')
            ->select(
                'order_promo_line.promo_code',
                DB::raw('COUNT(DISTINCT order_promo_line.order_id) as uses'),
                DB::raw('SUM(order_promo_line.discount_amount) as total_discount'),
                DB::raw('AVG(order_promo_line.discount_amount) as avg_discount'),
                DB::raw('SUM(orders.subtotal) as gross_revenue'),
                DB::raw('MAX(order_promo_line.cashback_flag) as is_cashback')
            )
            ->groupBy('order_promo_line.promo_code')
            ->orderByDesc('uses')
            ->get();

        $summary = [
            'total_promo_orders'   => OrderPromoLine::whereBetween('order_promo_line.created_at', [$from, $to])->distinct('order_id')->count('order_id'),
            'total_discount_given' => OrderPromoLine::whereBetween('order_promo_line.created_at', [$from, $to])->sum('discount_amount'),
            'unique_codes_used'    => OrderPromoLine::whereBetween('order_promo_line.created_at', [$from, $to])->distinct('promo_code')->count('promo_code'),
        ];

        // Daily discount trend
        $daily = OrderPromoLine::whereBetween('order_promo_line.created_at', [$from, $to])
            ->select(
                DB::raw('DATE(order_promo_line.created_at) as date'),
                DB::raw('COUNT(DISTINCT order_promo_line.order_id) as orders'),
                DB::raw('SUM(order_promo_line.discount_amount) as discount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'summary' => $summary,
            'by_code' => $byCode,
            'daily'   => $daily,
        ]);
    }

    public function paymentTransactionsDatatable(Request $request): JsonResponse
    {
        $draw = $request->get('draw');
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $search = $request->get('search')['value'] ?? '';
        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';

        $columnsMap = ['id', 'order_id', 'transaction_id', 'payment_method', 'payment_status', 'amount', 'updated_at'];
        $orderColumn = $columnsMap[$orderColumnIndex] ?? 'id';

        $query = OrderPaymentTransaction::with('order');
        $totalRecords = $query->count();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('payment_status', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($orderQuery) use ($search) {
                        $orderQuery->where('id', 'like', "%{$search}%")
                            ->orWhere('uuid', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyTransactionFilters($query, $request);

        $filteredRecords = $query->count();

        $data = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'order' => $this->renderOrderLink($transaction->order),
                    'transaction_id' => e($transaction->transaction_id ?? '—'),
                    'payment_method' => '<span class="badge bg-azure-lt">' . e(Str::headline((string) $transaction->payment_method)) . '</span>',
                    'payment_status' => $this->renderPaymentBadge((string) $transaction->payment_status),
                    'amount' => $this->formatMoney($transaction->amount, $transaction->currency),
                    'updated_at' => $transaction->updated_at?->format('Y-m-d H:i:s') ?? '—',
                    'details' => $this->renderTransactionDetails($transaction),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'draw' => (int) $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function paymentRefundsDatatable(Request $request): JsonResponse
    {
        return $this->datatableFromModel(
            request: $request,
            baseQuery: PaymentRefund::with('order'),
            columnsMap: ['id', 'order_id', 'razorpay_payment_id', 'razorpay_refund_id', 'status', 'amount', 'speed', 'updated_at'],
            searchCallback: function ($query, string $search): void {
                $query->where(function ($q) use ($search) {
                    $q->where('razorpay_payment_id', 'like', "%{$search}%")
                        ->orWhere('razorpay_refund_id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('speed', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($orderQuery) use ($search) {
                            $orderQuery->where('id', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%");
                        });
                });
            },
            filterCallback: function ($query, Request $request): void {
                $this->applyGatewayAndStatusFilters(
                    query: $query,
                    request: $request,
                    statusColumn: 'status',
                    dateColumn: 'updated_at',
                    gatewayCallback: function ($filterQuery, string $gateway): void {
                        if ($gateway === PaymentTypeEnum::EASEPAY()) {
                            $filterQuery->where(function ($subQuery) {
                                $subQuery->where('razorpay_refund_id', 'like', 'ebz-%')
                                    ->orWhere('razorpay_payment_id', 'like', 'ebz-%');
                            });
                            return;
                        }

                        if ($gateway === PaymentTypeEnum::RAZORPAY()) {
                            $filterQuery->where(function ($subQuery) {
                                $subQuery->where('razorpay_refund_id', 'not like', 'ebz-%')
                                    ->orWhereNull('razorpay_refund_id');
                            });
                        }
                    }
                );
            },
            rowMapper: function (PaymentRefund $refund): array {
                return [
                    'id' => $refund->id,
                    'order' => $this->renderOrderLink($refund->order),
                    'payment_id' => e($refund->razorpay_payment_id),
                    'refund_id' => e($refund->razorpay_refund_id),
                    'status' => $this->renderGenericBadge((string) $refund->status),
                    'amount' => $this->formatMoney($refund->amount, $refund->currency),
                    'speed' => e($refund->speed ?? '—'),
                    'updated_at' => $refund->updated_at?->format('Y-m-d H:i:s') ?? '—',
                ];
            }
        );
    }

    public function paymentDisputesDatatable(Request $request): JsonResponse
    {
        return $this->datatableFromModel(
            request: $request,
            baseQuery: PaymentDispute::with('order'),
            columnsMap: ['id', 'order_id', 'razorpay_payment_id', 'razorpay_dispute_id', 'status', 'amount', 'reason_description', 'respond_by', 'updated_at'],
            searchCallback: function ($query, string $search): void {
                $query->where(function ($q) use ($search) {
                    $q->where('razorpay_payment_id', 'like', "%{$search}%")
                        ->orWhere('razorpay_dispute_id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('reason_code', 'like', "%{$search}%")
                        ->orWhere('reason_description', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($orderQuery) use ($search) {
                            $orderQuery->where('id', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%");
                        });
                });
            },
            filterCallback: function ($query, Request $request): void {
                $this->applyGatewayAndStatusFilters(
                    query: $query,
                    request: $request,
                    statusColumn: 'status',
                    dateColumn: 'updated_at',
                    gatewayCallback: function ($filterQuery, string $gateway): void {
                        if ($gateway !== PaymentTypeEnum::RAZORPAY()) {
                            $filterQuery->whereRaw('1 = 0');
                        }
                    }
                );
            },
            rowMapper: function (PaymentDispute $dispute): array {
                return [
                    'id' => $dispute->id,
                    'order' => $this->renderOrderLink($dispute->order),
                    'payment_id' => e($dispute->razorpay_payment_id),
                    'dispute_id' => e($dispute->razorpay_dispute_id),
                    'status' => $this->renderGenericBadge((string) $dispute->status),
                    'amount' => $this->formatMoney($dispute->amount, $dispute->currency),
                    'reason' => e($dispute->reason_description ?: ($dispute->reason_code ?? '—')),
                    'respond_by' => $dispute->respond_by?->format('Y-m-d H:i:s') ?? '—',
                    'updated_at' => $dispute->updated_at?->format('Y-m-d H:i:s') ?? '—',
                ];
            }
        );
    }

    public function paymentSettlementsDatatable(Request $request): JsonResponse
    {
        return $this->datatableFromModel(
            request: $request,
            baseQuery: PaymentSettlement::with('order'),
            columnsMap: ['id', 'order_id', 'razorpay_payment_id', 'razorpay_settlement_id', 'status', 'amount', 'settlement_reference', 'settled_at', 'updated_at'],
            searchCallback: function ($query, string $search): void {
                $query->where(function ($q) use ($search) {
                    $q->where('razorpay_payment_id', 'like', "%{$search}%")
                        ->orWhere('razorpay_settlement_id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('settlement_reference', 'like', "%{$search}%")
                        ->orWhere('utr', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($orderQuery) use ($search) {
                            $orderQuery->where('id', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%");
                        });
                });
            },
            filterCallback: function ($query, Request $request): void {
                $this->applyGatewayAndStatusFilters(
                    query: $query,
                    request: $request,
                    statusColumn: 'status',
                    dateColumn: 'updated_at',
                    gatewayCallback: function ($filterQuery, string $gateway): void {
                        if ($gateway !== PaymentTypeEnum::RAZORPAY()) {
                            $filterQuery->whereRaw('1 = 0');
                        }
                    }
                );
            },
            rowMapper: function (PaymentSettlement $settlement): array {
                return [
                    'id' => $settlement->id,
                    'order' => $this->renderOrderLink($settlement->order),
                    'payment_id' => e($settlement->razorpay_payment_id ?? '—'),
                    'settlement_id' => e($settlement->razorpay_settlement_id),
                    'status' => $this->renderGenericBadge((string) $settlement->status),
                    'amount' => $this->formatMoney($settlement->amount, $settlement->currency),
                    'reference' => e($settlement->settlement_reference ?: ($settlement->utr ?? '—')),
                    'settled_at' => $settlement->settled_at?->format('Y-m-d H:i:s') ?? '—',
                    'updated_at' => $settlement->updated_at?->format('Y-m-d H:i:s') ?? '—',
                ];
            }
        );
    }

    public function paymentWebhookLogsDatatable(Request $request): JsonResponse
    {
        return $this->datatableFromModel(
            request: $request,
            baseQuery: PaymentWebhookLog::with(['order', 'transaction']),
            columnsMap: ['id', 'gateway', 'event_name', 'order_id', 'order_payment_transaction_id', 'status', 'signature_valid', 'updated_at'],
            searchCallback: function ($query, string $search): void {
                $query->where(function ($q) use ($search) {
                    $q->where('gateway', 'like', "%{$search}%")
                        ->orWhere('event_name', 'like', "%{$search}%")
                        ->orWhere('delivery_id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('transaction', function ($transactionQuery) use ($search) {
                            $transactionQuery->where('transaction_id', 'like', "%{$search}%");
                        })
                        ->orWhereHas('order', function ($orderQuery) use ($search) {
                            $orderQuery->where('id', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%");
                        });
                });
            },
            filterCallback: function ($query, Request $request): void {
                $paymentMethod = (string) $request->get('payment_method', '');
                $paymentStatus = (string) $request->get('payment_status', '');

                if ($paymentMethod !== '') {
                    $query->where('gateway', $paymentMethod);
                }

                if ($paymentStatus !== '') {
                    $query->where('status', $paymentStatus);
                }

                $this->applyDateFilters($query, $request, 'updated_at');
            },
            rowMapper: function (PaymentWebhookLog $webhookLog): array {
                return [
                    'id' => $webhookLog->id,
                    'gateway' => '<span class="badge bg-azure-lt">' . e(Str::headline((string) $webhookLog->gateway)) . '</span>',
                    'event_name' => e($webhookLog->event_name ?? '—'),
                    'order' => $this->renderOrderLink($webhookLog->order),
                    'transaction_id' => e($webhookLog->transaction?->transaction_id ?? '—'),
                    'status' => $this->renderGenericBadge((string) $webhookLog->status),
                    'signature' => $webhookLog->signature_valid
                        ? '<span class="badge bg-green-lt">Valid</span>'
                        : '<span class="badge bg-red-lt">Invalid</span>',
                    'updated_at' => $webhookLog->updated_at?->format('Y-m-d H:i:s') ?? '—',
                    'details' => $this->renderWebhookLogDetails($webhookLog),
                ];
            }
        );
    }

    public function paymentsSummary(Request $request): JsonResponse
    {
        $transactions = OrderPaymentTransaction::query();
        $refunds = PaymentRefund::query();
        $disputes = PaymentDispute::query();
        $settlements = PaymentSettlement::query();
        $webhookLogs = PaymentWebhookLog::query();

        $this->applyTransactionFilters($transactions, $request);
        $this->applyRefundGatewayAndStatusFilters($refunds, $request);
        $this->applyRazorpayOnlyStatusFilters($disputes, $request);
        $this->applyRazorpayOnlyStatusFilters($settlements, $request);

        $paymentMethod = (string) $request->get('payment_method', '');
        $paymentStatus = (string) $request->get('payment_status', '');

        if ($paymentMethod !== '') {
            $webhookLogs->where('gateway', $paymentMethod);
        }

        if ($paymentStatus !== '') {
            $webhookLogs->where('status', $paymentStatus);
        }

        $this->applyDateFilters($webhookLogs, $request, 'updated_at');

        return response()->json([
            'transactions' => $transactions->count(),
            'refunds' => $refunds->count(),
            'disputes' => $disputes->count(),
            'settlements' => $settlements->count(),
            'webhook_logs' => $webhookLogs->count(),
        ]);
    }

    public function exportTransactions(Request $request): StreamedResponse
    {
        $query = OrderPaymentTransaction::with('order');
        $this->applyTransactionFilters($query, $request);

        return $this->streamCsvDownload(
            filename: 'payment-transactions-' . now()->format('Ymd-His') . '.csv',
            headers: ['ID', 'Order', 'Transaction ID', 'Payment Method', 'Payment Status', 'Amount', 'Currency', 'Updated At', 'Message'],
            rows: $query->orderByDesc('id')->get()->map(function (OrderPaymentTransaction $transaction) {
                return [
                    $transaction->id,
                    $transaction->order?->slug ?: $transaction->order?->uuid ?: $transaction->order_id,
                    $transaction->transaction_id,
                    $transaction->payment_method,
                    $transaction->payment_status,
                    $transaction->amount,
                    $transaction->currency,
                    $transaction->updated_at?->format('Y-m-d H:i:s'),
                    $transaction->message,
                ];
            })->all(),
        );
    }

    public function exportRefunds(Request $request): StreamedResponse
    {
        $query = PaymentRefund::with('order');
        $this->applyRefundGatewayAndStatusFilters($query, $request);

        return $this->streamCsvDownload(
            filename: 'payment-refunds-' . now()->format('Ymd-His') . '.csv',
            headers: ['ID', 'Order', 'Payment ID', 'Refund ID', 'Status', 'Amount', 'Currency', 'Speed', 'Updated At'],
            rows: $query->orderByDesc('id')->get()->map(function (PaymentRefund $refund) {
                return [
                    $refund->id,
                    $refund->order?->slug ?: $refund->order?->uuid ?: $refund->order_id,
                    $refund->razorpay_payment_id,
                    $refund->razorpay_refund_id,
                    $refund->status,
                    $refund->amount,
                    $refund->currency,
                    $refund->speed,
                    $refund->updated_at?->format('Y-m-d H:i:s'),
                ];
            })->all(),
        );
    }

    public function exportDisputes(Request $request): StreamedResponse
    {
        $query = PaymentDispute::with('order');
        $this->applyRazorpayOnlyStatusFilters($query, $request);

        return $this->streamCsvDownload(
            filename: 'payment-disputes-' . now()->format('Ymd-His') . '.csv',
            headers: ['ID', 'Order', 'Payment ID', 'Dispute ID', 'Status', 'Amount', 'Currency', 'Reason', 'Respond By', 'Updated At'],
            rows: $query->orderByDesc('id')->get()->map(function (PaymentDispute $dispute) {
                return [
                    $dispute->id,
                    $dispute->order?->slug ?: $dispute->order?->uuid ?: $dispute->order_id,
                    $dispute->razorpay_payment_id,
                    $dispute->razorpay_dispute_id,
                    $dispute->status,
                    $dispute->amount,
                    $dispute->currency,
                    $dispute->reason_description ?: $dispute->reason_code,
                    $dispute->respond_by?->format('Y-m-d H:i:s'),
                    $dispute->updated_at?->format('Y-m-d H:i:s'),
                ];
            })->all(),
        );
    }

    public function exportSettlements(Request $request): StreamedResponse
    {
        $query = PaymentSettlement::with('order');
        $this->applyRazorpayOnlyStatusFilters($query, $request);

        return $this->streamCsvDownload(
            filename: 'payment-settlements-' . now()->format('Ymd-His') . '.csv',
            headers: ['ID', 'Order', 'Payment ID', 'Settlement ID', 'Status', 'Amount', 'Currency', 'Reference', 'UTR', 'Settled At', 'Updated At'],
            rows: $query->orderByDesc('id')->get()->map(function (PaymentSettlement $settlement) {
                return [
                    $settlement->id,
                    $settlement->order?->slug ?: $settlement->order?->uuid ?: $settlement->order_id,
                    $settlement->razorpay_payment_id,
                    $settlement->razorpay_settlement_id,
                    $settlement->status,
                    $settlement->amount,
                    $settlement->currency,
                    $settlement->settlement_reference,
                    $settlement->utr,
                    $settlement->settled_at?->format('Y-m-d H:i:s'),
                    $settlement->updated_at?->format('Y-m-d H:i:s'),
                ];
            })->all(),
        );
    }

    public function exportWebhookLogs(Request $request): StreamedResponse
    {
        $query = PaymentWebhookLog::with(['order', 'transaction']);
        $paymentMethod = (string) $request->get('payment_method', '');
        $paymentStatus = (string) $request->get('payment_status', '');

        if ($paymentMethod !== '') {
            $query->where('gateway', $paymentMethod);
        }

        if ($paymentStatus !== '') {
            $query->where('status', $paymentStatus);
        }

        $this->applyDateFilters($query, $request, 'updated_at');

        return $this->streamCsvDownload(
            filename: 'payment-webhook-logs-' . now()->format('Ymd-His') . '.csv',
            headers: ['ID', 'Gateway', 'Event', 'Delivery ID', 'Order', 'Transaction ID', 'Status', 'Signature Valid', 'HTTP Status', 'Processed At', 'Updated At', 'Message'],
            rows: $query->orderByDesc('id')->get()->map(function (PaymentWebhookLog $webhookLog) {
                return [
                    $webhookLog->id,
                    $webhookLog->gateway,
                    $webhookLog->event_name,
                    $webhookLog->delivery_id,
                    $webhookLog->order?->slug ?: $webhookLog->order?->uuid ?: $webhookLog->order_id,
                    $webhookLog->transaction?->transaction_id,
                    $webhookLog->status,
                    $webhookLog->signature_valid ? 'yes' : 'no',
                    $webhookLog->http_status,
                    $webhookLog->processed_at?->format('Y-m-d H:i:s'),
                    $webhookLog->updated_at?->format('Y-m-d H:i:s'),
                    $webhookLog->message,
                ];
            })->all(),
        );
    }

    private function datatableFromModel(Request $request, $baseQuery, array $columnsMap, callable $searchCallback, callable $rowMapper, ?callable $filterCallback = null): JsonResponse
    {
        $draw = $request->get('draw');
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $search = $request->get('search')['value'] ?? '';
        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';
        $orderColumn = $columnsMap[$orderColumnIndex] ?? 'id';

        $query = clone $baseQuery;
        $totalRecords = $query->count();

        if ($search !== '') {
            $searchCallback($query, $search);
        }

        if ($filterCallback !== null) {
            $filterCallback($query, $request);
        }

        $filteredRecords = $query->count();
        $data = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map($rowMapper)
            ->values()
            ->all();

        return response()->json([
            'draw' => (int) $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function renderOrderLink(?Order $order): string
    {
        if (!$order) {
            return '—';
        }

        $label = $order->slug ?: ($order->uuid ?: '#' . $order->id);

        return '<a href="' . route('admin.orders.show', $order->id) . '" class="text-decoration-none fw-medium">' . e($label) . '</a>';
    }

    private function renderPaymentBadge(string $status): string
    {
        $color = match ($status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            'refunded', 'partially_refunded' => 'azure',
            default => 'secondary',
        };

        return '<span class="badge bg-' . $color . '-lt text-capitalize">' . e(str_replace('_', ' ', $status)) . '</span>';
    }

    private function renderGenericBadge(string $status): string
    {
        $normalized = strtolower($status);
        $color = match ($normalized) {
            'processed', 'completed', 'won', 'closed', 'settled' => 'green',
            'failed', 'lost' => 'red',
            'under_review', 'action_required', 'created', 'pending' => 'yellow',
            default => 'secondary',
        };

        return '<span class="badge bg-' . $color . '-lt text-capitalize">' . e(str_replace('_', ' ', $status)) . '</span>';
    }

    private function formatMoney($amount, ?string $currency): string
    {
        return e(strtoupper((string) ($currency ?: 'INR'))) . ' ' . number_format((float) $amount, 2);
    }

    private function renderTransactionDetails($transaction): string
    {
        $details = [];
        $event = data_get($transaction->payment_details, 'event');
        $failureReason = data_get($transaction->payment_details, 'failure.error_reason');
        $message = $transaction->message;

        if ($event) {
            $details[] = '<div><span class="text-muted">Event:</span> ' . e($event) . '</div>';
        }
        if ($failureReason) {
            $details[] = '<div><span class="text-muted">Failure:</span> ' . e($failureReason) . '</div>';
        }
        if ($message) {
            $details[] = '<div class="text-muted small mt-1">' . e(Str::limit($message, 120)) . '</div>';
        }

        return !empty($details) ? implode('', $details) : '—';
    }

    private function renderWebhookLogDetails(PaymentWebhookLog $webhookLog): string
    {
        $details = [];

        if ($webhookLog->delivery_id) {
            $details[] = '<div><span class="text-muted">Delivery:</span> ' . e($webhookLog->delivery_id) . '</div>';
        }

        if ($webhookLog->http_status) {
            $details[] = '<div><span class="text-muted">HTTP:</span> ' . e((string) $webhookLog->http_status) . '</div>';
        }

        if ($webhookLog->message) {
            $details[] = '<div class="text-muted small mt-1">' . e(Str::limit($webhookLog->message, 140)) . '</div>';
        }

        return !empty($details) ? implode('', $details) : '—';
    }

    private function applyTransactionFilters($query, Request $request): void
    {
        $paymentMethod = (string) $request->get('payment_method', '');
        $paymentStatus = (string) $request->get('payment_status', '');

        if ($paymentMethod !== '') {
            $query->where('payment_method', $paymentMethod);
        }

        if ($paymentStatus !== '') {
            $query->where('payment_status', $paymentStatus);
        }

        $this->applyDateFilters($query, $request, 'updated_at');
    }

    private function applyGatewayAndStatusFilters($query, Request $request, string $statusColumn, string $dateColumn, callable $gatewayCallback): void
    {
        $paymentMethod = (string) $request->get('payment_method', '');
        $paymentStatus = (string) $request->get('payment_status', '');

        if ($paymentMethod !== '') {
            $gatewayCallback($query, $paymentMethod);
        }

        if ($paymentStatus !== '') {
            $query->where($statusColumn, $paymentStatus);
        }

        $this->applyDateFilters($query, $request, $dateColumn);
    }

    private function applyRefundGatewayAndStatusFilters($query, Request $request): void
    {
        $this->applyGatewayAndStatusFilters(
            query: $query,
            request: $request,
            statusColumn: 'status',
            dateColumn: 'updated_at',
            gatewayCallback: function ($filterQuery, string $gateway): void {
                if ($gateway === PaymentTypeEnum::EASEPAY()) {
                    $filterQuery->where(function ($subQuery) {
                        $subQuery->where('razorpay_refund_id', 'like', 'ebz-%')
                            ->orWhere('razorpay_payment_id', 'like', 'ebz-%');
                    });
                    return;
                }

                if ($gateway === PaymentTypeEnum::RAZORPAY()) {
                    $filterQuery->where(function ($subQuery) {
                        $subQuery->where('razorpay_refund_id', 'not like', 'ebz-%')
                            ->orWhereNull('razorpay_refund_id');
                    });
                    return;
                }

                $filterQuery->whereRaw('1 = 0');
            }
        );
    }

    private function applyRazorpayOnlyStatusFilters($query, Request $request): void
    {
        $this->applyGatewayAndStatusFilters(
            query: $query,
            request: $request,
            statusColumn: 'status',
            dateColumn: 'updated_at',
            gatewayCallback: function ($filterQuery, string $gateway): void {
                if ($gateway !== PaymentTypeEnum::RAZORPAY()) {
                    $filterQuery->whereRaw('1 = 0');
                }
            }
        );
    }

    private function applyDateFilters($query, Request $request, string $dateColumn): void
    {
        $fromDateInput = $request->get('from_date');
        $toDateInput = $request->get('to_date');
        $dateRange = $request->get('date_range');

        if (!empty($fromDateInput)) {
            $query->where($dateColumn, '>=', Carbon::parse($fromDateInput)->startOfDay());
        }

        if (!empty($toDateInput)) {
            $query->where($dateColumn, '<=', Carbon::parse($toDateInput)->endOfDay());
        }

        if (empty($fromDateInput) && empty($toDateInput) && !empty($dateRange)) {
            $fromDate = $this->getDateRange($dateRange);
            if ($fromDate) {
                $query->where($dateColumn, '>=', $fromDate);
            }
        }
    }

    private function getDateRange(?string $dateRange): ?Carbon
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

    private function streamCsvDownload(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);

            foreach ($rows as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
