<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use App\Services\DashboardService;
use App\Services\WalletService;
use App\Models\Seller;
use App\Models\User;
use App\Models\Order;
use App\Traits\ChecksPermissions;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ChecksPermissions;
    protected DashboardService $dashboardService;
    protected CurrencyService $currencyService;
    protected WalletService $walletService;
    protected bool $viewPermission = true;

    public function __construct(
        DashboardService $dashboardService,
        CurrencyService  $currencyService,
        WalletService    $walletService
    )
    {
        $this->dashboardService = $dashboardService;
        $this->currencyService = $currencyService;
        $this->walletService = $walletService;
        $this->viewPermission = $this->hasPermission(AdminPermissionEnum::DASHBOARD_VIEW());
    }

    /**
     * Display the admin dashboard with dynamic data.
     */
    public function index(Request $request): View
    {
        $currencyService = $this->currencyService;
        $dashboardService = $this->dashboardService;

        $adminInsights = $dashboardService->getAdminInsightsData();
        $conversionRateData = $dashboardService->getAdminConversionRateData(days: 30);
        $revenueDataBg = $dashboardService->getRevenueData(days: 30);
        $dailyPurchaseHistory = $dashboardService->getDailyPurchaseHistory(days: 30);
        $todaysEarning = $dashboardService->getTodaysEarning();
        $categoryProductWeightage = $dashboardService->getCategoryProductWeightage();
        $newUserRegistrationsData = $dashboardService->getNewUserRegistrationsData(days: 30);
        $repeatedCustomersData = $dashboardService->getRepeatedCustomersData(days: 30);

        // New analytics data
        $topSellingProducts = $dashboardService->getTopSellingProducts(days: 30, limit: 5);
        $categoriesWithFilters = $dashboardService->getCategoriesWithFilters(sortBy: 'products_count', filterBy: 'all');

        $viewPermission = $this->viewPermission;
        return view('admin.dashboard', compact(
            'currencyService',
            'adminInsights',
            'conversionRateData',
            'revenueDataBg',
            'dailyPurchaseHistory',
            'todaysEarning',
            'categoryProductWeightage',
            'newUserRegistrationsData',
            'repeatedCustomersData',
            'topSellingProducts',
            'categoriesWithFilters',
            'viewPermission'
        ));
    }

    /**
     * Get dashboard data via AJAX for dynamic updates.
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $days = (int)$request->input('days', 7);
        $limit = $request->input('limit', 5);
        $sortBy = $request->input('sort_by', 'name');
        $filterBy = $request->input('filter_by', 'all');

        switch ($type) {
            case 'sales':
                $data = $this->dashboardService->getAdminConversionRateData(days: $days);
                break;
            case 'revenue':
                $data = $this->dashboardService->getRevenueData(days: $days);
                break;
            case 'new_users':
                $data = $this->dashboardService->getNewUserRegistrationsData(days: $days);
                break;
            case 'repeated_customers':
                $data = $this->dashboardService->getRepeatedCustomersData(days: $days);
                break;
            case 'top_products':
                $data = $this->dashboardService->getTopSellingProducts(days: $days, limit: $limit);
                break;
            case 'categories':
                $data = $this->dashboardService->getCategoriesWithFilters(sortBy: $sortBy, filterBy: $filterBy);
                break;
            default:
                return response()->json(['error' => 'Invalid data type requested'], 400);
        }

        return response()->json($data);
    }
}
