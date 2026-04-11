<?php

namespace App\Providers;

use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Events\Cart\CartUpdatedByLocation;
use App\Events\Cart\ItemAddedToCart;
use App\Events\Cart\ItemRemovedFromCart;
use App\Events\DeliveryBoy\DeliveryBoyStatusUpdatedEvent;
use App\Events\Order\OrderPlaced;
use App\Events\Order\OrderStatusUpdated;
use App\Events\Product\ProductAfterCreate;
use App\Events\Product\ProductAfterUpdate;
use App\Events\Product\ProductStatusAfterUpdate;
use App\Listeners\Auth\SendLoggedInNotification;
use App\Listeners\Auth\SendWelcomeNotification;
use App\Listeners\Cart\LogCartActivity;
use App\Listeners\DeliveryBoy\StoreDeliveryBoyLocation;
use App\Listeners\Order\NewOrderNotification;
use App\Listeners\Order\OrderStatusUpdatedNotification;
use App\Listeners\Order\SendOrderPlacedEmail;
use App\Listeners\Order\SendOrderPlacedSms;
use App\Listeners\Order\SendOrderStatusEmail;
use App\Listeners\Order\SendOrderStatusSms;
use App\Listeners\Order\UpdateStockOnOrderStatusChange;
use App\Listeners\Product\ProductCreatedNotification;
use App\Listeners\Product\ProductUpdatedNotification;
use App\Listeners\Product\ProductStatusUpdatedNotification;
use App\Listeners\Product\RevalidateFrontendCache;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */

    protected array $listen = [
        UserRegistered::class => [
            SendWelcomeNotification::class,
        ],
        UserLoggedIn::class => [
            SendLoggedInNotification::class,
        ],
        // Cart Events
        ItemAddedToCart::class => [
            LogCartActivity::class . '@handleItemAdded',
        ],

        ItemRemovedFromCart::class => [
            LogCartActivity::class . '@handleItemRemoved',
        ],

        CartUpdatedByLocation::class => [
            LogCartActivity::class . '@handleCartUpdatedByLocation',
        ],

        // Order Events
        OrderStatusUpdated::class => [
            OrderStatusUpdatedNotification::class,
            UpdateStockOnOrderStatusChange::class,
            SendOrderStatusEmail::class,
            SendOrderStatusSms::class,
        ],

        OrderPlaced::class => [
            NewOrderNotification::class,
            SendOrderPlacedEmail::class,
            SendOrderPlacedSms::class,
        ],

        // DeliveryBoy Events
        DeliveryBoyStatusUpdatedEvent::class => [
            StoreDeliveryBoyLocation::class,
        ],

        // Product Events
        ProductAfterCreate::class => [
            ProductCreatedNotification::class,
            RevalidateFrontendCache::class,
        ],

        ProductAfterUpdate::class => [
            ProductUpdatedNotification::class,
            RevalidateFrontendCache::class,
        ],

        ProductStatusAfterUpdate::class => [
            ProductStatusUpdatedNotification::class,
            RevalidateFrontendCache::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

}
