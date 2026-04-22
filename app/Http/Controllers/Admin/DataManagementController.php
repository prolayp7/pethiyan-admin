<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Types\Api\ApiResponseType;
use App\Enums\DefaultSystemRolesEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DataManagementController extends Controller
{

    private const CATEGORIES = [
        'orders' => [
            'label' => 'Orders & Related Data',
            'description' => 'Removes all orders, order items, returns, seller orders, shipping parcels, and delivery assignments.',
            'tables' => [
                'delivery_boy_assignments',
                'shipping_parcel_items',
                'shipping_parcels',
                'order_item_returns',
                'seller_order_items',
                'seller_orders',
                'order_items',
                'orders',
            ],
        ],
        'carts' => [
            'label' => 'Carts & Related Data',
            'description' => 'Removes all active carts, cart items, and saved-for-later items.',
            'tables' => [
                'cart_save_for_later_items',
                'cart_items',
                'carts',
            ],
        ],
        'transactions' => [
            'label' => 'Transactions & Related Data',
            'description' => 'Removes all order payment transactions and wallet transactions.',
            'tables' => [
                'wallet_transactions',
                'order_payment_transactions',
            ],
        ],
        'payments' => [
            'label' => 'Payments & Related Data',
            'description' => 'Removes all payment settlements, webhook logs, refunds, and disputes.',
            'tables' => [
                'payment_webhook_logs',
                'payment_disputes',
                'payment_refunds',
                'payment_settlements',
            ],
        ],
    ];

    public function index(): View
    {
        $this->authorizeDataManagement();

        $categories = self::CATEGORIES;
        return view('admin.settings.data_management', compact('categories'));
    }

    public function truncate(Request $request): JsonResponse
    {
        $this->authorizeDataManagement();

        $validated = $request->validate([
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(self::CATEGORIES))],
            'password' => ['required', 'string'],
        ]);

        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return ApiResponseType::sendJsonResponse(false, 'Unauthorized.', [], 401);
        }

        if (!Hash::check($validated['password'], $admin->password)) {
            return ApiResponseType::sendJsonResponse(false, 'Invalid password.', [], 422);
        }

        $category = $validated['category'];
        $tables = self::CATEGORIES[$category]['tables'];

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::beginTransaction();

            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, 'Truncation failed: ' . $e->getMessage(), [], 500);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $label = self::CATEGORIES[$category]['label'];
        return ApiResponseType::sendJsonResponse(true, "{$label} have been cleared successfully.", [
            'category' => $category,
            'tables_cleared' => $tables,
        ]);
    }

    private function authorizeDataManagement(): void
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            abort(403, 'Unauthorized.');
        }

        if (!$admin->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
            abort(403, 'Only super admins can access data management.');
        }
    }
}
