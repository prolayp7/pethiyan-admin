<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPromoLine;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
}
