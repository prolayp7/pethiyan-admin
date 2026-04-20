<?php

use App\Http\Controllers\MenuController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\SystemUpdateController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminTotpController;
use App\Http\Controllers\Admin\DeliveryBoyCashCollectionController;
use App\Http\Controllers\Admin\DeliveryBoyEarningController;
use App\Http\Controllers\Admin\DeliveryBoyWithdrawalController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SellerWithdrawalController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DeliveryBoyController;
use App\Http\Controllers\DeliveryZoneController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\FeaturedSectionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductFaqController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SellerEarningController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SystemUserController;
use App\Http\Controllers\TaxClassController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\StateShippingRateController;
use App\Http\Controllers\Admin\PinServiceAreaController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\GiftCardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\HeroSectionController;
use App\Http\Controllers\Admin\VideoStorySectionController;
use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\PinLocationMasterController;
use App\Http\Controllers\GlobalAttributeController;
use App\Http\Controllers\GlobalAttributeValueController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['guest:admin'])->group(function () {
        Route::get('/', [AuthController::class, 'loginAdmin'])->name('login');
        Route::get('login', [AuthController::class, 'loginAdmin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');

        // Password Reset Routes
        Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
    });

    Route::middleware(['auth:admin', 'validate.admin'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
        Route::get('dashboard/data', [DashboardController::class, 'getDashboardData'])->name('dashboard.data');

        // profile
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
            Route::post('/update', [ProfileController::class, 'update'])->name('update');
            Route::post('/password-update', [ProfileController::class, 'changePassword'])->name('password.update');
        });

        // admin totp
        Route::prefix('security/totp')->name('security.totp.')->group(function () {
            Route::get('/status', [AdminTotpController::class, 'status'])->name('status');
            Route::post('/setup', [AdminTotpController::class, 'setup'])->name('setup');
            Route::post('/enable', [AdminTotpController::class, 'enable'])->name('enable');
            Route::post('/disable', [AdminTotpController::class, 'disable'])->name('disable');
            Route::post('/recovery-codes', [AdminTotpController::class, 'regenerateRecoveryCodes'])->name('recovery-codes');
        });

        // settings
        Route::prefix('settings')->namespace('Settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::post('payment/unlock', [SettingController::class, 'unlockPaymentSettings'])->name('payment.unlock');
            Route::post('payment/lock', [SettingController::class, 'lockPaymentSettings'])->name('payment.lock');
            Route::post('authentication/unlock', [SettingController::class, 'unlockAuthenticationSettings'])->name('authentication.unlock');
            Route::post('authentication/lock', [SettingController::class, 'lockAuthenticationSettings'])->name('authentication.lock');
            Route::get('{setting}', [SettingController::class, 'show'])->name('show');
            Route::post('store', [SettingController::class, 'store'])->name('store');
        });

        Route::prefix('video-stories-section')->name('video-stories-section.')->group(function () {
            Route::get('/', [VideoStorySectionController::class, 'show'])->name('show');
            Route::post('/videos', [VideoStorySectionController::class, 'store'])->name('videos.store');
            Route::post('/videos/{id}', [VideoStorySectionController::class, 'update'])->name('videos.update');
            Route::delete('/videos/{id}', [VideoStorySectionController::class, 'destroy'])->name('videos.destroy');
            Route::post('/videos/{id}/toggle', [VideoStorySectionController::class, 'toggle'])->name('videos.toggle');
            Route::post('/videos/reorder', [VideoStorySectionController::class, 'reorder'])->name('videos.reorder');
            Route::post('/settings', [VideoStorySectionController::class, 'updateSettings'])->name('settings.update');
        });

        // system updates
        Route::prefix('system-updates')->name('system-updates.')->group(function () {
            Route::get('/', [SystemUpdateController::class, 'index'])->name('index');
            Route::post('/', [SystemUpdateController::class, 'store'])->name('store');
            Route::get('/datatable', [SystemUpdateController::class, 'datatable'])->name('datatable');
            // Live log endpoints
            Route::get('/latest', [SystemUpdateController::class, 'latest'])->name('latest');
            Route::get('/{update}/log', [SystemUpdateController::class, 'showLog'])->name('log');
        });

        // categories
        Route::prefix('categories')->namespace('Categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');
            Route::get('/{id}/edit', [CategoryController::class, 'show'])->name('edit');
            Route::post('/{id}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [CategoryController::class, 'getCategories'])->name('datatable');
            Route::get('/search', [CategoryController::class, 'search'])->name('search')->name('search');
        });

        // brands
        Route::prefix('brands')->namespace('Brands')->name('brands.')->group(function () {
            Route::get('/', [BrandController::class, 'index'])->name('index');
            Route::post('/', [BrandController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [BrandController::class, 'show'])->name('edit');
            Route::post('/{id}', [BrandController::class, 'update'])->name('update');
            Route::delete('/{id}', [BrandController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [BrandController::class, 'getBrands'])->name('datatable');
            Route::get('/search', [BrandController::class, 'search'])->name('search');
        });

        // promos
        Route::prefix('promos')->name('promos.')->group(function () {
            Route::get('/', [PromoController::class, 'index'])->name('index');
            Route::post('/', [PromoController::class, 'store'])->name('store');
            Route::get('/datatable', [PromoController::class, 'datatable'])->name('datatable');
            Route::get('/{id}', [PromoController::class, 'show'])->name('show');
            Route::put('/{id}', [PromoController::class, 'update'])->name('update');
            Route::delete('/{id}', [PromoController::class, 'destroy'])->name('destroy');
        });

        // customers (web panel users)
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/',                                       [CustomerController::class, 'index'])->name('index');
            Route::post('/',                                      [CustomerController::class, 'store'])->name('store');
            Route::get('/datatable',                              [CustomerController::class, 'datatable'])->name('datatable');
            Route::get('/export',                                 [CustomerController::class, 'export'])->name('export');
            Route::get('/{id}',                                   [CustomerController::class, 'show'])->name('show');
            Route::put('/{id}',                                   [CustomerController::class, 'update'])->name('update');
            Route::delete('/{id}',                                [CustomerController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/toggle-status',                   [CustomerController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/toggle-status',                    [CustomerController::class, 'toggleStatus']);
            // Addresses
            Route::get('/{id}/addresses',                         [CustomerController::class, 'addresses'])->name('addresses');
            Route::post('/{id}/addresses',                        [CustomerController::class, 'storeAddress'])->name('addresses.store');
            Route::put('/{id}/addresses/{addressId}',             [CustomerController::class, 'updateAddress'])->name('addresses.update');
            Route::delete('/{id}/addresses/{addressId}',          [CustomerController::class, 'destroyAddress'])->name('addresses.destroy');
            // Orders
            Route::get('/{id}/orders',                            [CustomerController::class, 'orders'])->name('orders');
        });

        // sellers
        Route::prefix('sellers')->name('sellers.')->group(function () {
            Route::get('/', [SellerController::class, 'index'])->name('index');
            Route::post('/', [SellerController::class, 'store'])->name('store');
            Route::get('/create', [SellerController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [SellerController::class, 'edit'])->name('edit');
            Route::post('/{id}', [SellerController::class, 'update'])->name('update');
            Route::delete('/{id}', [SellerController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [SellerController::class, 'getSellers'])->name('datatable');
            Route::get('/search', [SellerController::class, 'search'])->name('search')->name('search');
        });

        // taxes
        Route::prefix('tax-rates')->namespace('TaxRates')->name('tax-rates.')->group(function () {
            Route::get('/', [TaxRateController::class, 'index'])->name('index');
            Route::post('/', [TaxRateController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [TaxRateController::class, 'show'])->name('edit');
            Route::post('/{id}', [TaxRateController::class, 'update'])->name('update');
            Route::delete('/{id}', [TaxRateController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [TaxRateController::class, 'getTaxRates'])->name('datatable');
            Route::get('/search', [TaxRateController::class, 'search'])->name('search');
        });

        // tax classes
        Route::prefix('tax-classes')->namespace('TaxClasses')->name('tax-classes.')->group(function () {
            Route::get('/', [TaxClassController::class, 'index'])->name('index');
            Route::post('/', [TaxClassController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [TaxClassController::class, 'show'])->name('edit');
            Route::post('/{id}', [TaxClassController::class, 'update'])->name('update');
            Route::delete('/{id}', [TaxClassController::class, 'destroy'])->name('delete');
            Route::get('/get-tax-classes', [TaxClassController::class, 'getTaxClasses'])->name('datatable');
        });

        // Roles and Permissions
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::post('/{id}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
            Route::get('/get-roles', [RoleController::class, 'getRoles'])->name('datatable');
            Route::get('/{role}/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        });

        // permissions
        Route::prefix('permissions')->namespace('Permissions')->name('permissions.')->group(function () {
            Route::post('/', [PermissionController::class, 'store'])->name('store');
        });

        // System Users
        Route::prefix('system-users')->namespace('systemUsers')->name('system-users.')->group(function () {
            Route::get('/', [SystemUserController::class, 'index'])->name('index');
            Route::post('/', [SystemUserController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [SystemUserController::class, 'show'])->name('show');
            Route::post('/{id}', [SystemUserController::class, 'update'])->name('update');
            Route::delete('/{id}', [SystemUserController::class, 'destroy'])->name('destroy');
            Route::get('/datatable', [SystemUserController::class, 'getSystemUsers'])->name('datatable');
        });

        // seller stores
        Route::prefix('sellers/store')->name('sellers.store.')->group(function () {
            Route::get('/', [StoreController::class, 'index'])->name('index');
            Route::post('/', [StoreController::class, 'store'])->name('store');
            Route::get('/create', [StoreController::class, 'create'])->name('create');
            Route::get('/datatable', [StoreController::class, 'getStores'])->name('datatable');
            Route::get('/search', [StoreController::class, 'search'])->name('search');
            Route::get('/view/{id}', [StoreController::class, 'index'])->name('show.index');
            Route::get('/{id}/edit', [StoreController::class, 'edit'])->name('edit');
            Route::post('/{id}', [StoreController::class, 'update'])->name('update');
            Route::delete('/{id}', [StoreController::class, 'destroy'])->name('destroy');

        // reviews (admin UI)
        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ReviewController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\ReviewController::class, 'store'])->name('store');
            Route::post('/{id}/approve', [\App\Http\Controllers\ReviewController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\ReviewController::class, 'reject'])->name('reject');
        });
        
        // Expose top-level /admin/reviews routes so admin menu links work
        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ReviewController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\ReviewController::class, 'store'])->name('store');
            Route::post('/{id}/approve', [\App\Http\Controllers\ReviewController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\ReviewController::class, 'reject'])->name('reject');
        });
            Route::get('/{id}', [StoreController::class, 'show'])->name('show');
            Route::post('/{id}/verify', [StoreController::class, 'verify'])->name('verify');
        });

        // stores list (used by product form pricing section)
        Route::prefix('stores')->name('stores.')->group(function () {
            Route::get('/list', [StoreController::class, 'StoreList'])->name('list');
        });

        // Support Tickets
        Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
            Route::get('/',             [SupportTicketController::class, 'index'])->name('index');
            Route::get('/datatable',    [SupportTicketController::class, 'datatable'])->name('datatable');
            Route::get('/{id}',         [SupportTicketController::class, 'show'])->name('show');
            Route::delete('/{id}',      [SupportTicketController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/reply',  [SupportTicketController::class, 'reply'])->name('reply');
            Route::post('/{id}/status', [SupportTicketController::class, 'updateStatus'])->name('status');
            // Ticket types
            Route::post('/types',        [SupportTicketController::class, 'storeType'])->name('types.store');
            Route::post('/types/{type}', [SupportTicketController::class, 'updateType'])->name('types.update');
            Route::delete('/types/{type}', [SupportTicketController::class, 'destroyType'])->name('types.destroy');
        });

        // Gift Cards
        Route::prefix('gift-cards')->name('gift-cards.')->group(function () {
            Route::get('/',                [GiftCardController::class, 'index'])->name('index');
            Route::get('/datatable',       [GiftCardController::class, 'datatable'])->name('datatable');
            Route::post('/',               [GiftCardController::class, 'store'])->name('store');
            Route::get('/{id}',            [GiftCardController::class, 'show'])->name('show');
            Route::post('/{id}',           [GiftCardController::class, 'update'])->name('update');
            Route::delete('/{id}',         [GiftCardController::class, 'destroy'])->name('destroy');
        });

        // FAQs
        Route::prefix('faqs')->name('faqs.')->group(function () {
            Route::get('/', [FaqController::class, 'index'])->name('index');
            Route::post('/', [FaqController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [FaqController::class, 'edit'])->name('edit');
            Route::post('/{id}', [FaqController::class, 'update'])->name('update');
            Route::delete('/{id}', [FaqController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [FaqController::class, 'getFaqs'])->name('datatable');
        });

        // banners
        Route::prefix('banners')->name('banners.')->group(function () {
            Route::get('/', [BannerController::class, 'index'])->name('index');
            Route::post('/', [BannerController::class, 'store'])->name('store');
            Route::get('/create', [BannerController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [BannerController::class, 'edit'])->name('edit');
            Route::post('/{id}', [BannerController::class, 'update'])->name('update');
            Route::delete('/{id}', [BannerController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [BannerController::class, 'getBanners'])->name('datatable');
        });

        Route::get('products/search', [ProductController::class, 'search'])->name('products.search');

        // Pin Service Areas — specific routes must come before /{id}
        Route::prefix('pin-service')->name('pin-service.')->group(function () {
            Route::get('/',             [PinServiceAreaController::class, 'index'])->name('index');
            Route::get('/masters',      [PinLocationMasterController::class, 'index'])->name('masters.index');
            Route::post('/masters/districts', [PinLocationMasterController::class, 'storeDistrict'])->name('masters.districts.store');
            Route::post('/masters/districts/{id}', [PinLocationMasterController::class, 'updateDistrict'])->name('masters.districts.update');
            Route::delete('/masters/districts/{id}', [PinLocationMasterController::class, 'destroyDistrict'])->name('masters.districts.destroy');
            Route::post('/masters/cities', [PinLocationMasterController::class, 'storeCity'])->name('masters.cities.store');
            Route::post('/masters/cities/{id}', [PinLocationMasterController::class, 'updateCity'])->name('masters.cities.update');
            Route::delete('/masters/cities/{id}', [PinLocationMasterController::class, 'destroyCity'])->name('masters.cities.destroy');
            Route::get('/datatable',    [PinServiceAreaController::class, 'datatable'])->name('datatable');
            Route::get('/districts',    [PinServiceAreaController::class, 'districts'])->name('districts');
            Route::get('/cities',       [PinServiceAreaController::class, 'cities'])->name('cities');
            Route::post('/',            [PinServiceAreaController::class, 'store'])->name('store');
            Route::post('/bulk-toggle', [PinServiceAreaController::class, 'bulkToggle'])->name('bulk-toggle');
            Route::post('/import',      [PinServiceAreaController::class, 'importCsv'])->name('import');
            Route::get('/{id}',         [PinServiceAreaController::class, 'show'])->name('show');
            Route::post('/{id}',        [PinServiceAreaController::class, 'update'])->name('update');
            Route::delete('/{id}',      [PinServiceAreaController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle', [PinServiceAreaController::class, 'toggleServiceable'])->name('toggle');
        });

        // state shipping rates
        Route::prefix('state-shipping-rates')->name('state-shipping-rates.')->group(function () {
            Route::get('/', [StateShippingRateController::class, 'index'])->name('index');
            Route::post('/', [StateShippingRateController::class, 'store'])->name('store');
            Route::get('/datatable', [StateShippingRateController::class, 'datatable'])->name('datatable');
            Route::post('/partners/{id}/toggle', [StateShippingRateController::class, 'togglePartnerStatus'])->name('partners.toggle');
            Route::get('/{id}', [StateShippingRateController::class, 'show'])->name('show');
            Route::post('/{id}', [StateShippingRateController::class, 'update'])->name('update');
            Route::delete('/{id}', [StateShippingRateController::class, 'destroy'])->name('delete');
        });

        // Inventory Management
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::get('/datatable', [InventoryController::class, 'datatable'])->name('datatable');
            Route::post('/{id}/stock', [InventoryController::class, 'updateStock'])->name('stock.update');
            Route::post('/bulk-stock', [InventoryController::class, 'bulkUpdateStock'])->name('stock.bulk');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/sales',     [ReportController::class, 'sales'])->name('sales');
            Route::get('/orders',    [ReportController::class, 'orders'])->name('orders');
            Route::get('/products',  [ReportController::class, 'products'])->name('products');
            Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
            Route::get('/payments',  [ReportController::class, 'payments'])->name('payments');
            // Data endpoints
            Route::get('/sales/data',     [ReportController::class, 'salesData'])->name('sales.data');
            Route::get('/sales/export',   [ReportController::class, 'exportSales'])->name('sales.export');
            Route::get('/orders/data',    [ReportController::class, 'ordersData'])->name('orders.data');
            Route::get('/orders/export',  [ReportController::class, 'exportOrders'])->name('orders.export');
            Route::get('/products/data',  [ReportController::class, 'productsData'])->name('products.data');
            Route::get('/products/export',[ReportController::class, 'exportProducts'])->name('products.export');
            Route::get('/customers/data', [ReportController::class, 'customersData'])->name('customers.data');
            Route::get('/customers/export',[ReportController::class, 'exportCustomers'])->name('customers.export');
            Route::get('/promos',         [ReportController::class, 'promos'])->name('promos');
            Route::get('/promos/data',    [ReportController::class, 'promosData'])->name('promos.data');
            Route::get('/payments/summary', [ReportController::class, 'paymentsSummary'])->name('payments.summary');
            Route::get('/payments/transactions/datatable', [ReportController::class, 'paymentTransactionsDatatable'])->name('payments.transactions.datatable');
            Route::get('/payments/refunds/datatable', [ReportController::class, 'paymentRefundsDatatable'])->name('payments.refunds.datatable');
            Route::get('/payments/disputes/datatable', [ReportController::class, 'paymentDisputesDatatable'])->name('payments.disputes.datatable');
            Route::get('/payments/settlements/datatable', [ReportController::class, 'paymentSettlementsDatatable'])->name('payments.settlements.datatable');
            Route::get('/payments/webhook-logs/datatable', [ReportController::class, 'paymentWebhookLogsDatatable'])->name('payments.webhook-logs.datatable');
            Route::get('/payments/transactions/export', [ReportController::class, 'exportTransactions'])->name('payments.transactions.export');
            Route::get('/payments/refunds/export', [ReportController::class, 'exportRefunds'])->name('payments.refunds.export');
            Route::get('/payments/disputes/export', [ReportController::class, 'exportDisputes'])->name('payments.disputes.export');
            Route::get('/payments/settlements/export', [ReportController::class, 'exportSettlements'])->name('payments.settlements.export');
            Route::get('/payments/webhook-logs/export', [ReportController::class, 'exportWebhookLogs'])->name('payments.webhook-logs.export');
        });

        // Hero Section
        Route::prefix('hero-section')->name('hero-section.')->group(function () {
            Route::get('/',                                    [HeroSectionController::class, 'show'])->name('show');
            Route::post('/settings',                           [HeroSectionController::class, 'updateSettings'])->name('settings.update');
            // Slides — specific routes before wildcards
            Route::post('/slides/reorder',                     [HeroSectionController::class, 'reorderSlides'])->name('slides.reorder');
            Route::post('/slides',                             [HeroSectionController::class, 'storeSlide'])->name('slides.store');
            Route::post('/slides/{id}/toggle',                 [HeroSectionController::class, 'toggleSlide'])->name('slides.toggle');
            Route::post('/slides/{id}',                        [HeroSectionController::class, 'updateSlide'])->name('slides.update');
            Route::delete('/slides/{id}',                      [HeroSectionController::class, 'destroySlide'])->name('slides.destroy');
            // Badges — specific routes before wildcards
            Route::post('/badges/reorder',                     [HeroSectionController::class, 'reorderBadges'])->name('badges.reorder');
            Route::post('/badges',                             [HeroSectionController::class, 'storeBadge'])->name('badges.store');
            Route::post('/badges/{id}/toggle',                 [HeroSectionController::class, 'toggleBadge'])->name('badges.toggle');
            Route::post('/badges/{id}',                        [HeroSectionController::class, 'updateBadge'])->name('badges.update');
            Route::delete('/badges/{id}',                      [HeroSectionController::class, 'destroyBadge'])->name('badges.destroy');
        });

        // delivery zones
        Route::prefix('delivery-zones')->name('delivery-zones.')->group(function () {
            Route::get('/', [DeliveryZoneController::class, 'index'])->name('index');
            Route::post('/', [DeliveryZoneController::class, 'store'])->name('store');
            Route::get('/create', [DeliveryZoneController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [DeliveryZoneController::class, 'edit'])->name('edit');
            Route::post('/{id}', [DeliveryZoneController::class, 'update'])->name('update');
            Route::delete('/{id}', [DeliveryZoneController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [DeliveryZoneController::class, 'getDeliveryZones'])->name('datatable');
            Route::post('/check-exists', [DeliveryZoneController::class, 'checkExists'])->name('check_exists');
        });

        // Featured Sections Routes
        Route::prefix('featured-sections')->name('featured-sections.')->group(function () {
            Route::get('/', [FeaturedSectionController::class, 'index'])->name('index');
            Route::post('/', [FeaturedSectionController::class, 'store'])->name('store');
            Route::get('/datatable', [FeaturedSectionController::class, 'getFeaturedSections'])->name('datatable');
            // Sorting routes
            Route::get('/sort', [FeaturedSectionController::class, 'sort'])->name('sort');
            Route::post('/sort', [FeaturedSectionController::class, 'updateSort'])->name('updateSort');

            Route::get('/{id}', [FeaturedSectionController::class, 'show'])->name('show');
            Route::post('/{id}', [FeaturedSectionController::class, 'update'])->name('update');
            Route::delete('/{id}', [FeaturedSectionController::class, 'destroy'])->name('destroy');
        });

        // Notifications Routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/datatable', [NotificationController::class, 'getNotifications'])->name('datatable');
            Route::get('/{id}', [NotificationController::class, 'show'])->name('show');
            Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('mark-read');
            Route::post('/{id}/mark-unread', [NotificationController::class, 'markAsUnread'])->name('mark-unread');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        });

        // Delivery Boys Routes
        Route::prefix('delivery-boys')->name('delivery-boys.')->group(function () {
            Route::get('/', [DeliveryBoyController::class, 'index'])->name('index');
            Route::get('/datatable', [DeliveryBoyController::class, 'getDeliveryBoys'])->name('datatable');
            Route::get('search', [DeliveryBoyController::class, 'search'])->name('search');
            Route::get('/{id}', [DeliveryBoyController::class, 'show'])->name('show');
            Route::post('/{id}/verification-status', [DeliveryBoyController::class, 'updateVerificationStatus'])->name('update-verification-status');
            Route::delete('/{id}', [DeliveryBoyController::class, 'destroy'])->name('destroy');
        });

        // Delivery Boy Earnings Routes
        Route::prefix('delivery-boy-earnings')->name('delivery-boy-earnings.')->group(function () {
            Route::get('/', [DeliveryBoyEarningController::class, 'index'])->name('index');
            Route::get('/datatable', [DeliveryBoyEarningController::class, 'getEarnings'])->name('datatable');
            Route::post('/{id}/process-payment', [DeliveryBoyEarningController::class, 'processPayment'])->name('process-payment');
            Route::get('/history', [DeliveryBoyEarningController::class, 'history'])->name('history');
            Route::get('/history/datatable', [DeliveryBoyEarningController::class, 'getPaymentHistory'])->name('history.datatable');
        });

        // Delivery Boy Cash Collection Routes
        Route::prefix('delivery-boy-cash-collections')->name('delivery-boy-cash-collections.')->group(function () {
            Route::get('/', [DeliveryBoyCashCollectionController::class, 'index'])->name('index');
            Route::get('/datatable', [DeliveryBoyCashCollectionController::class, 'getCashCollections'])->name('datatable');
            Route::post('/{id}/process-submission', [DeliveryBoyCashCollectionController::class, 'processCashSubmission'])->name('process-submission');
            Route::get('/history', [DeliveryBoyCashCollectionController::class, 'history'])->name('history');
            Route::get('/history/datatable', [DeliveryBoyCashCollectionController::class, 'getCashSubmissionHistory'])->name('history.datatable');
        });

        // Delivery Boy Withdrawal Routes
        Route::prefix('delivery-boy-withdrawals')->name('delivery-boy-withdrawals.')->group(function () {
            Route::get('/', [DeliveryBoyWithdrawalController::class, 'index'])->name('index');
            Route::get('/datatable', [DeliveryBoyWithdrawalController::class, 'getWithdrawalRequests'])->name('datatable');
            Route::post('/{id}/process', [DeliveryBoyWithdrawalController::class, 'processWithdrawalRequest'])->name('process');
            Route::get('/history', [DeliveryBoyWithdrawalController::class, 'history'])->name('history');
            Route::get('/history/datatable', [DeliveryBoyWithdrawalController::class, 'getWithdrawalHistory'])->name('history.datatable');
            Route::get('/{id}', [DeliveryBoyWithdrawalController::class, 'show'])->name('show');
        });

        // Seller Withdrawal Routes
        Route::prefix('seller-withdrawals')->name('seller-withdrawals.')->group(function () {
            Route::get('/', [SellerWithdrawalController::class, 'index'])->name('index');
            Route::get('/datatable', [SellerWithdrawalController::class, 'getWithdrawalRequests'])->name('datatable');
            Route::post('/{id}/process', [SellerWithdrawalController::class, 'processWithdrawalRequest'])->name('process');
            Route::get('/history', [SellerWithdrawalController::class, 'history'])->name('history');
            Route::get('/history/datatable', [SellerWithdrawalController::class, 'getWithdrawalHistory'])->name('history.datatable');
            Route::get('/{id}', [SellerWithdrawalController::class, 'show'])->name('show');
        });

        // Commission Settlement Routes
        Route::prefix('commissions')->name('commissions.')->group(function () {
            Route::get('/', [SellerEarningController::class, 'index'])->name('index');
            // Credits
            Route::get('/datatable', [SellerEarningController::class, 'getUnsettledCommissions'])->name('datatable');
            Route::post('/{id}/settle', [SellerEarningController::class, 'settleCommission'])->name('settle');
            Route::post('/settle-all', [SellerEarningController::class, 'settleAllCommissions'])->name('settle-all');
            // Debits
            Route::get('/debits/datatable', [SellerEarningController::class, 'getUnsettledDebits'])->name('debits.datatable');
            Route::post('/debits/{id}/settle', [SellerEarningController::class, 'settleDebit'])->name('debits.settle');
            Route::post('/debits/settle-all', [SellerEarningController::class, 'settleAllDebits'])->name('debits.settle-all');
            // History
            Route::get('/history', [SellerEarningController::class, 'history'])->name('history');
            Route::get('/history/datatable', [SellerEarningController::class, 'getSettledCommissions'])->name('history.datatable');
        });

        // orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/datatable', [OrderController::class, 'getOrders'])->name('datatable');
            Route::get('invoice', [OrderController::class, 'orderInvoice']);
            Route::get('/{id}/invoice/download', [OrderController::class, 'downloadInvoice'])->name('invoice.download');
            Route::get('/{id}/shipping-address/download', [OrderController::class, 'downloadShippingAddress'])->name('shipping-address.download');
            Route::post('/{id}/manage', [OrderController::class, 'updateAdminOrder'])->name('manage');
            Route::get('/{id}', [OrderController::class, 'show'])->name('show');
            Route::post('/{id}/{status}', [OrderController::class, 'updateStatus'])->name('update_status');
        });

        // products
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('/create', [ProductController::class, 'create'])->name('create');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::get('/import/template', [ProductImportController::class, 'downloadTemplate'])->name('import.template');
            Route::post('/import', [ProductImportController::class, 'import'])->name('import.store');
            Route::get('/import/status/{jobId}', [ProductImportController::class, 'status'])->name('import.status');
            Route::get('/import/failed-report/{reportId}', [ProductImportController::class, 'downloadFailedReport'])->name('import.failed-report');
            Route::get('/datatable', [ProductController::class, 'getProducts'])->name('datatable');
            Route::get('/search', [ProductController::class, 'search'])->name('search');
            Route::post('/{id}/duplicate', [ProductController::class, 'duplicate'])->name('duplicate');
            Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
            Route::get('/{id}/pricing', [ProductController::class, 'getProductPricing'])->name('pricing');
            Route::post('/{id}', [ProductController::class, 'update'])->name('update');
            Route::post('/{id}/verification-status', [ProductController::class, 'updateVerificationStatus'])->name('update-verification-status');
            Route::post('/{id}/update-status', [ProductController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{id}', [ProductController::class, 'destroy'])->name('delete');
            Route::get('/{id}', [ProductController::class, 'show'])->name('show');
        });


        // global product attributes (values must come first to avoid {id} wildcard conflict)
        Route::prefix('attributes/values')->name('attributes.values.')->group(function () {
            Route::get('/datatable', [GlobalAttributeValueController::class, 'getAllAttributeValues'])->name('datatable');
            Route::post('/', [GlobalAttributeValueController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [GlobalAttributeValueController::class, 'edit'])->name('edit')->whereNumber('id');
            Route::post('/{id}', [GlobalAttributeValueController::class, 'update'])->name('update')->whereNumber('id');
            Route::delete('/{id}', [GlobalAttributeValueController::class, 'destroy'])->name('delete')->whereNumber('id');
        });
        Route::prefix('attributes')->name('attributes.')->group(function () {
            Route::get('/', [GlobalAttributeController::class, 'index'])->name('index');
            Route::post('/', [GlobalAttributeController::class, 'store'])->name('store');
            Route::get('/datatable', [GlobalAttributeController::class, 'getAttributes'])->name('datatable');
            Route::get('/search', [GlobalAttributeController::class, 'search'])->name('search');
            Route::get('/{id}/edit', [GlobalAttributeController::class, 'edit'])->name('edit')->whereNumber('id');
            Route::post('/{id}', [GlobalAttributeController::class, 'update'])->name('update')->whereNumber('id');
            Route::delete('/{id}', [GlobalAttributeController::class, 'destroy'])->name('delete')->whereNumber('id');
        });

        // product Faqs
        Route::prefix('product-faqs')->name('product_faqs.')->group(function () {
            Route::get('/', [ProductFaqController::class, 'index'])->name('index');
            Route::get('/datatable', [ProductFaqController::class, 'getProductFaqs'])->name('datatable');
            Route::post('/', [ProductFaqController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ProductFaqController::class, 'edit'])->name('edit');
            Route::post('/{id}', [ProductFaqController::class, 'update'])->name('update');
            Route::delete('/{id}', [ProductFaqController::class, 'destroy'])->name('delete');
    //            Route::get('/search', [ProductFaqController::class, 'search'])->name('search');
        });

        // cms pages
        Route::prefix('pages')->name('pages.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PageController::class, 'index'])->name('index');
            Route::get('/{page}/edit', [\App\Http\Controllers\Admin\PageController::class, 'edit'])->name('edit');
            Route::post('/{page}', [\App\Http\Controllers\Admin\PageController::class, 'update'])->name('update');
        });

        // blog
        Route::prefix('blog')->name('blog.')->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Admin\BlogSectionController::class, 'show'])->name('settings.show');
            Route::post('/settings', [\App\Http\Controllers\Admin\BlogSectionController::class, 'update'])->name('settings.update');

            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\BlogCategoryController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\BlogCategoryController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\BlogCategoryController::class, 'store'])->name('store');
                Route::get('/{category}/edit', [\App\Http\Controllers\Admin\BlogCategoryController::class, 'edit'])->name('edit');
                Route::post('/{category}', [\App\Http\Controllers\Admin\BlogCategoryController::class, 'update'])->name('update');
                Route::delete('/{category}', [\App\Http\Controllers\Admin\BlogCategoryController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('posts')->name('posts.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\BlogPostController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\BlogPostController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\BlogPostController::class, 'store'])->name('store');
                Route::get('/{post}/edit', [\App\Http\Controllers\Admin\BlogPostController::class, 'edit'])->name('edit');
                Route::post('/{post}', [\App\Http\Controllers\Admin\BlogPostController::class, 'update'])->name('update');
                Route::delete('/{post}', [\App\Http\Controllers\Admin\BlogPostController::class, 'destroy'])->name('destroy');
            });
        });

        // enquiries
        Route::prefix('enquiries')->name('enquiries.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EnquiryController::class, 'index'])->name('index');
            Route::get('/{enquiry}', [\App\Http\Controllers\Admin\EnquiryController::class, 'show'])->name('show');
            Route::delete('/{enquiry}', [\App\Http\Controllers\Admin\EnquiryController::class, 'destroy'])->name('destroy');
        });

        // featured products section (homepage)
        Route::prefix('featured-products-section')->name('featured-products-section.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\FeaturedProductsSectionController::class, 'show'])->name('show');
            Route::post('/settings', [\App\Http\Controllers\Admin\FeaturedProductsSectionController::class, 'updateSettings'])->name('settings.update');
            Route::post('/preview', [\App\Http\Controllers\Admin\FeaturedProductsSectionController::class, 'previewProducts'])->name('preview');
        });

        // why choose us (homepage)
        Route::prefix('why-choose-us')->name('why-choose-us.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\WhyChooseUsController::class, 'show'])->name('show');
            Route::post('/settings', [\App\Http\Controllers\Admin\WhyChooseUsController::class, 'updateSettings'])->name('settings.update');
        });

        // promo banner (homepage)
        Route::prefix('promo-banner')->name('promo-banner.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PromoBannerController::class, 'show'])->name('show');
            Route::post('/settings', [\App\Http\Controllers\Admin\PromoBannerController::class, 'updateSettings'])->name('settings.update');
        });

        // announcement bar (homepage)
        Route::prefix('announcement-bar')->name('announcement-bar.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AnnouncementBarController::class, 'show'])->name('show');
            Route::post('/settings', [\App\Http\Controllers\Admin\AnnouncementBarController::class, 'updateSettings'])->name('settings.update');
        });

        // highlight ticker (homepage bottom marquee)
        Route::prefix('highlight-ticker')->name('highlight-ticker.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\HighlightTickerController::class, 'show'])->name('show');
            Route::post('/settings', [\App\Http\Controllers\Admin\HighlightTickerController::class, 'updateSettings'])->name('settings.update');
        });

        // newsletter section (homepage)
        Route::prefix('newsletter-section')->name('newsletter-section.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\NewsletterSectionController::class, 'show'])->name('show');
            Route::post('/settings', [\App\Http\Controllers\Admin\NewsletterSectionController::class, 'updateSettings'])->name('settings.update');
        });

        // social proof / testimonials
        Route::prefix('social-proof')->name('social-proof.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SocialProofController::class, 'show'])->name('show');
            Route::post('/settings', [\App\Http\Controllers\Admin\SocialProofController::class, 'updateSettings'])->name('settings.update');
            Route::post('/testimonials', [\App\Http\Controllers\Admin\SocialProofController::class, 'store'])->name('testimonials.store');
            Route::post('/testimonials/{id}', [\App\Http\Controllers\Admin\SocialProofController::class, 'update'])->name('testimonials.update');
            Route::delete('/testimonials/{id}', [\App\Http\Controllers\Admin\SocialProofController::class, 'destroy'])->name('testimonials.destroy');
            Route::patch('/testimonials/{id}/toggle', [\App\Http\Controllers\Admin\SocialProofController::class, 'toggle'])->name('testimonials.toggle');
            Route::post('/testimonials/reorder', [\App\Http\Controllers\Admin\SocialProofController::class, 'reorder'])->name('testimonials.reorder');
        });

        // ── Navigation Menus ─────────────────────────────────────────────
        Route::prefix('menus')->name('menus.')->group(function () {
            // Menu CRUD
            Route::get('/',            [MenuController::class, 'index'])->name('index');
            Route::get('/datatable',   [MenuController::class, 'datatable'])->name('datatable');
            Route::post('/',           [MenuController::class, 'store'])->name('store');
            Route::get('/{menu}',      [MenuController::class, 'show'])->name('show');
            Route::post('/{menu}',     [MenuController::class, 'update'])->name('update');
            Route::delete('/{menu}',   [MenuController::class, 'destroy'])->name('destroy');
            Route::patch('/{menu}/toggle-active', [MenuController::class, 'toggleActive'])->name('toggle-active');

            // Menu Items
            Route::prefix('/{menu}/items')->name('items.')->group(function () {
                Route::get('/',          [MenuController::class, 'itemsIndex'])->name('index');
                Route::get('/datatable', [MenuController::class, 'itemsDatatable'])->name('datatable');
                Route::post('/',         [MenuController::class, 'storeItem'])->name('store');
                Route::post('/reorder',  [MenuController::class, 'reorderItems'])->name('reorder');
                Route::get('/{item}',    [MenuController::class, 'showItem'])->name('show');
                Route::post('/{item}',   [MenuController::class, 'updateItem'])->name('update');
                Route::delete('/{item}', [MenuController::class, 'destroyItem'])->name('destroy');
                Route::patch('/{item}/toggle-active', [MenuController::class, 'toggleItemActive'])->name('toggle-active');

                // Mega Menu Builder
                Route::prefix('/{item}/mega-menu')->name('mega-menu.')->group(function () {
                    Route::get('/', [MenuController::class, 'megaMenuIndex'])->name('index');

                    // Panels
                    Route::post('/panels',             [MenuController::class, 'storePanel'])->name('panels.store');
                    Route::post('/panels/reorder',     [MenuController::class, 'reorderPanels'])->name('panels.reorder');
                    Route::get('/panels/{panel}',      [MenuController::class, 'showPanel'])->name('panels.show');
                    Route::post('/panels/{panel}',     [MenuController::class, 'updatePanel'])->name('panels.update');
                    Route::delete('/panels/{panel}',   [MenuController::class, 'destroyPanel'])->name('panels.destroy');
                    Route::patch('/panels/{panel}/toggle-active', [MenuController::class, 'togglePanelActive'])->name('panels.toggle-active');

                    // Columns
                    Route::post('/panels/{panel}/columns',           [MenuController::class, 'storeColumn'])->name('columns.store');
                    Route::post('/panels/{panel}/columns/reorder',   [MenuController::class, 'reorderColumns'])->name('columns.reorder');
                    Route::get('/panels/{panel}/columns/{column}',   [MenuController::class, 'showColumn'])->name('columns.show');
                    Route::post('/panels/{panel}/columns/{column}',  [MenuController::class, 'updateColumn'])->name('columns.update');
                    Route::delete('/panels/{panel}/columns/{column}',[MenuController::class, 'destroyColumn'])->name('columns.destroy');

                    // Links
                    Route::post('/panels/{panel}/columns/{column}/links',          [MenuController::class, 'storeLink'])->name('links.store');
                    Route::post('/panels/{panel}/columns/{column}/links/reorder',  [MenuController::class, 'reorderLinks'])->name('links.reorder');
                    Route::get('/panels/{panel}/columns/{column}/links/{link}',    [MenuController::class, 'showLink'])->name('links.show');
                    Route::post('/panels/{panel}/columns/{column}/links/{link}',   [MenuController::class, 'updateLink'])->name('links.update');
                    Route::delete('/panels/{panel}/columns/{column}/links/{link}', [MenuController::class, 'destroyLink'])->name('links.destroy');
                });
            });
        });
    });
});
