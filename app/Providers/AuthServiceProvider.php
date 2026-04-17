<?php

namespace App\Providers;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryBoy;
use App\Models\DeliveryBoyAssignment;
use App\Models\DeliveryBoyWithdrawalRequest;
use App\Models\Faq;
use App\Models\Promo;
use App\Models\SellerStatement;
use App\Models\SellerWithdrawalRequest;
use App\Models\GlobalProductAttribute;
use App\Models\GlobalProductAttributeValue;
use App\Models\Product;
use App\Models\ProductCondition;
use App\Models\ProductFaq;
use App\Models\Review;
use App\Models\Seller;
use App\Models\SellerFeedback;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\OrderItemReturn;
use App\Models\Setting;
use App\Models\Store;
use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\AdminUser;
use App\Models\User;
use App\Models\Notification;
use App\Policies\BannerPolicy;
use App\Policies\BrandPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\DeliveryBoyPolicy;
use App\Policies\DeliveryBoyAssignmentPolicy;
use App\Policies\DeliveryBoyWithdrawalRequestPolicy;
use App\Policies\FaqPolicy;
use App\Policies\SellerStatementPolicy;
use App\Policies\SellerWithdrawalRequestPolicy;
use App\Policies\WalletPolicy;
use App\Policies\GlobalAttributePolicy;
use App\Policies\GlobalAttributeValuePolicy;
use App\Policies\ProductConditionPolicy;
use App\Policies\ProductFaqPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PromoPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\RolePolicy;
use App\Policies\OrderPolicy;
use App\Policies\SellerFeedbackPolicy;
use App\Policies\SellerPolicy;
use App\Policies\SettingPolicy;
use App\Policies\StorePolicy;
use App\Policies\SystemUserPolicy;
use App\Policies\TaxClassPolicy;
use App\Policies\OrderReturnPolicy;
use App\Support\InstallationState;
use App\Models\Wallet;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Role::class => RolePolicy::class,
        Brand::class => BrandPolicy::class,
        Category::class => CategoryPolicy::class,
        ProductCondition::class => ProductConditionPolicy::class,
        GlobalProductAttribute::class => GlobalAttributePolicy::class,
        GlobalProductAttributeValue::class => GlobalAttributeValuePolicy::class,
        Seller::class => SellerPolicy::class,
        SellerFeedback::class => SellerFeedbackPolicy::class,
        SellerOrder::class => OrderPolicy::class,
        SellerOrderItem::class => OrderPolicy::class,
        OrderItemReturn::class => OrderReturnPolicy::class,
        TaxClass::class => TaxClassPolicy::class,
        TaxRate::class => TaxClassPolicy::class,
        User::class => SystemUserPolicy::class,
        AdminUser::class => SystemUserPolicy::class,
        Setting::class => SettingPolicy::class,
        Store::class => StorePolicy::class,
        Product::class => ProductPolicy::class,
        ProductFaq::class => ProductFaqPolicy::class,
        Promo::class => PromoPolicy::class,
        Faq::class => FaqPolicy::class,
        Banner::class => BannerPolicy::class,
        Review::class => ReviewPolicy::class,
        DeliveryBoy::class => DeliveryBoyPolicy::class,
        DeliveryBoyAssignment::class => DeliveryBoyAssignmentPolicy::class,
        DeliveryBoyWithdrawalRequest::class => DeliveryBoyWithdrawalRequestPolicy::class,
        SellerWithdrawalRequest::class => SellerWithdrawalRequestPolicy::class,
        Notification::class => NotificationPolicy::class,
        Wallet::class => WalletPolicy::class,
        SellerStatement::class => SellerStatementPolicy::class,
    ];
    /**
     * Register services.
     */

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability) {
            if (!method_exists($user, 'hasRole') || !method_exists($user, 'getDefaultGuardName')) {
                return null;
            }

            if ($user->getDefaultGuardName() !== 'admin') {
                return null;
            }

            return $user->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN()) ? true : null;
        });

        $admin_defined = AdminPermissionEnum::values();
        if ($admin_defined && InstallationState::hasTable('permissions')) {
            foreach ($admin_defined as $perm) {
                Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
            }
        }

        $seller_defined = SellerPermissionEnum::values();
        if ($seller_defined && InstallationState::hasTable('permissions')) {
            foreach ($seller_defined as $perm) {
                Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'seller']);
            }
        }
    }
}
