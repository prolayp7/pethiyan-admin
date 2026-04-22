<?php

use App\Http\Controllers\Api\MenuApiController;
use App\Http\Controllers\Api\SupportTicketApiController;
use App\Http\Controllers\GiftCardController;
use App\Http\Controllers\Api\BannerApiController;
use App\Http\Controllers\Api\BrandApiController;
use App\Http\Controllers\Api\BlogApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\DeliveryZoneApiController;
use App\Http\Controllers\Api\ShippingRateApiController;
use App\Http\Controllers\Api\FaqApiController;
use App\Http\Controllers\Api\FeaturedSectionApiController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\Product\ProductApiController;
use App\Http\Controllers\Api\Product\ProductFaqApiController;
use App\Http\Controllers\Api\SellerFeedbackApiController;
use App\Http\Controllers\Api\SettingApiController;
use App\Http\Controllers\Api\StoreApiController;
use App\Http\Controllers\Api\User\AddressApiController;
use App\Http\Controllers\Api\User\AuthApiController;
use App\Http\Controllers\Api\User\CartApiController;
use App\Http\Controllers\Api\User\OtpAuthController;
use App\Http\Controllers\Api\User\ForgotPasswordOtpController;
use App\Http\Controllers\Api\User\OrderApiController;
use App\Http\Controllers\Api\User\ProductReviewApiController;
use App\Http\Controllers\Api\User\PromoApiController;
use App\Http\Controllers\Api\User\UserApiController;
use App\Http\Controllers\Api\User\WalletApiController;
use App\Http\Controllers\Api\BrowsingHistoryApiController;
use App\Http\Controllers\Api\BuyAgainApiController;
use App\Http\Controllers\Api\User\WishlistApiController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Payments\EasepayController;
use App\Http\Controllers\Payments\FlutterwaveController;
use App\Http\Controllers\Payments\PaystackController;
use App\Http\Controllers\Payments\RazorpayController;
use App\Http\Controllers\Payments\StripeController;
use App\Http\Controllers\Api\AnnouncementBarApiController;
use App\Http\Controllers\Api\HeroSectionApiController;
use App\Http\Controllers\Api\NewsletterSectionApiController;
use App\Http\Controllers\Api\VideoStorySectionApiController;
use App\Http\Controllers\Api\ContactApiController;
use App\Http\Controllers\Api\PageApiController;
use App\Http\Controllers\Api\SearchApiController;
use Illuminate\Support\Facades\Route;

include_once("delivery-boy-api.php");
include_once("seller-api.php");

// ── Search (public — consumed by Next.js frontend) ──────────────────────────
Route::prefix('search')->name('search.')->group(function () {
    Route::get('/',                  [SearchApiController::class, 'search'])->name('unified');
    Route::get('/top-searches',      [SearchApiController::class, 'topSearches'])->name('top-searches');
    Route::get('/trending-products', [SearchApiController::class, 'trendingProducts'])->name('trending-products');
    Route::post('/track',            [SearchApiController::class, 'track'])->name('track');
});

// Announcement Bar (public — consumed by Next.js frontend)
Route::get('announcement-bar', [AnnouncementBarApiController::class, 'index'])->name('announcement-bar.index');

// Hero Section (public — consumed by Next.js frontend)
Route::get('hero-section', [HeroSectionApiController::class, 'index'])->name('hero-section.index');
Route::get('video-story-section', [VideoStorySectionApiController::class, 'index'])->name('video-story-section.index');

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogApiController::class, 'home'])->name('home');
    Route::get('/posts', [BlogApiController::class, 'posts'])->name('posts');
    Route::get('/posts/{slug}', [BlogApiController::class, 'show'])->name('posts.show');
    Route::get('/categories', [BlogApiController::class, 'categories'])->name('categories');
    Route::get('/categories/{slug}', [BlogApiController::class, 'category'])->name('categories.show');
});
Route::get('newsletter-section', [NewsletterSectionApiController::class, 'index'])->name('newsletter-section.index');

// Shipping Rates (public)
Route::post('shipping/rates', [ShippingRateApiController::class, 'index'])->name('shipping.rates');

// User Auth Routes
Route::post('register', [AuthApiController::class, 'register'])->name('register');
Route::post('login', [AuthApiController::class, 'login'])->name('login');
Route::post('forget-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password');
Route::post('verify-user', [AuthApiController::class, 'verifyUser']);
Route::post('verify-mobile', [AuthApiController::class, 'verifyMobile'])->name('verify-mobile');

// OTP Authentication Routes
Route::prefix('auth/otp')->name('auth.otp.')->middleware('throttle:5,1')->group(function () {
    Route::post('send',   [OtpAuthController::class, 'sendOtp'])->name('send');
    Route::post('verify', [OtpAuthController::class, 'verifyOtp'])->name('verify');
    Route::post('resend', [OtpAuthController::class, 'resendOtp'])->middleware('throttle:3,10')->name('resend');
});

// Forgot Password (OTP-based, works inside the modal without page redirects)
Route::prefix('auth/forgot-password')->name('auth.forgot-password.')->middleware('throttle:5,1')->group(function () {
    Route::post('send-otp', [ForgotPasswordOtpController::class, 'sendOtp'])->name('send-otp');
    Route::post('reset',    [ForgotPasswordOtpController::class, 'resetPassword'])->name('reset');
    Route::post('resend',   [ForgotPasswordOtpController::class, 'resendOtp'])->middleware('throttle:3,10')->name('resend');
});

Route::post('auth/google/callback', [AuthApiController::class, 'googleCallback'])->name('google-callback');
Route::post('auth/apple/callback', [AuthApiController::class, 'appleCallback'])->name('apple-callback');

// settings
Route::prefix('settings')->name('api.')->group(function () {
    Route::get('/', [SettingApiController::class, 'index'])->name('settings.index');
    Route::get('footer', [SettingApiController::class, 'footer'])->name('settings.footer');
    Route::get('featured-products-section', [SettingApiController::class, 'featuredProductsSection'])->name('settings.featured-products-section');
    Route::get('why-choose-us-section', [SettingApiController::class, 'whyChooseUsSection'])->name('settings.why-choose-us-section');
    Route::get('promo-banner-section', [SettingApiController::class, 'promoBannerSection'])->name('settings.promo-banner-section');
    Route::get('social-proof-section', [SettingApiController::class, 'socialProofSection'])->name('settings.social-proof-section');
    Route::get('auth-config', [SettingApiController::class, 'authConfig'])->name('settings.auth-config');
    Route::get('firebase-config', [SettingApiController::class, 'firebaseConfig'])->name('settings.firebase-config');
    Route::get('seo', [SettingApiController::class, 'seo'])->name('settings.seo');
    Route::get('seo-advanced', [SettingApiController::class, 'seoAdvanced'])->name('settings.seo-advanced');
    Route::get('/variables', [SettingApiController::class, 'settingVariables'])->name('settings.variables');
    Route::get('/{setting}', [SettingApiController::class, 'show'])->name('settings.show');
});

// Public promo popup route
Route::get('promos/popup', [PromoApiController::class, 'getPopupPromo']);

Route::middleware('auth:sanctum')->group(function () {
    //logout
    Route::post('logout', [AuthApiController::class, 'logout']);

    // Backward-compatible user routes (legacy paths used by frontend clients)
    Route::apiResource('/addresses', AddressApiController::class);

    // users routes
    Route::prefix('user')->name('user.')->group(function () {
        // delete user account
        Route::delete('/delete-account', [UserApiController::class, 'deleteAccount'])->name('delete-account');

        // User profile
        Route::apiResource('/addresses', AddressApiController::class);

        // profile
        Route::get('/profile', [UserApiController::class, 'getProfile']);
        Route::post('/profile', [UserApiController::class, 'updateProfile']);

        // Wallet routes
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [WalletApiController::class, 'getWallet']);
            Route::post('/prepare-wallet-recharge', [WalletApiController::class, 'prepareWalletRecharge']);
            Route::post('/deduct-balance', [WalletApiController::class, 'deductBalance']);
            Route::get('/transactions', [WalletApiController::class, 'getTransactions']);
            Route::get('/transactions/{id}', [WalletApiController::class, 'getTransaction']);
        });

        // Browsing history routes
        Route::prefix('browsing-history')->name('browsing-history.')->group(function () {
            Route::get('/', [BrowsingHistoryApiController::class, 'index']);
            Route::post('/', [BrowsingHistoryApiController::class, 'store']);
        });

        // Buy again — products from past delivered orders
        Route::get('buy-again', [BuyAgainApiController::class, 'index']);

        // Wishlist routes
        Route::prefix('wishlists')->name('wishlists.')->group(function () {
            Route::get('/', [WishlistApiController::class, 'index']);
            Route::get('/summary', [WishlistApiController::class, 'summary']);
            Route::get('/titles', [WishlistApiController::class, 'getTitles']);
            Route::post('/', [WishlistApiController::class, 'store']); // Combined create wishlist and add item
            Route::post('/create', [WishlistApiController::class, 'createWishlist']); // Create wishlist only

            // Wishlist items management
            Route::delete('/items', [WishlistApiController::class, 'clearAllItems']);
            Route::delete('/items/{itemId}', [WishlistApiController::class, 'removeItem']);
            Route::put('/items/{itemId}/move', [WishlistApiController::class, 'moveItem']);

            Route::delete('/{id}/items', [WishlistApiController::class, 'clearWishlistItems']);
            Route::get('/{id}', [WishlistApiController::class, 'show']);
            Route::put('/{id}', [WishlistApiController::class, 'update']);
            Route::delete('/{id}', [WishlistApiController::class, 'destroy']);
        });

        // Cart routes
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartApiController::class, 'getCart']);
            Route::post('/add', [CartApiController::class, 'addToCart']);
            Route::post('/item/{cartItemId}', [CartApiController::class, 'updateCartItemQuantity']);
            Route::delete('/item/{cartItemId}', [CartApiController::class, 'removeFromCart']);
            Route::get('/item/save-for-later', [CartApiController::class, 'getSaveForLaterItems']);
            Route::post('/item/save-for-later/{cartItemId}', [CartApiController::class, 'saveForLater']);
            Route::get('/clear-cart', [CartApiController::class, 'clearCart']);
            Route::post('/sync', [CartApiController::class, 'syncCart']);
        });

        // Promo routes
        Route::prefix('promos')->group(function () {
            Route::get('/available', [PromoApiController::class, 'getUserAvailablePromos']);
            Route::get('/validate', [PromoApiController::class, 'validatePromoCode']);
        });

        // Order routes
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderApiController::class, 'getUserOrders']);
            Route::post('/', [OrderApiController::class, 'createOrder']);
            Route::get('/{orderSlug}', [OrderApiController::class, 'getOrder']);
            Route::get('/{orderSlug}/invoice', [OrderApiController::class, 'downloadInvoice']);
            Route::get('/{orderSlug}/delivery-boy-location', [OrderApiController::class, 'getOrderDeliveryBoyLocation']);
            Route::post('/items/{orderItemId}/cancel', [OrderApiController::class, 'cancelOrderItem']);
            Route::post('/items/{orderItemId}/return', [OrderApiController::class, 'returnOrderItem']);
            Route::post('/items/{orderItemId}/return-cancel', [OrderApiController::class, 'cancelReturnRequest']);
        });

        // Transaction routes
        Route::prefix('order-transactions')->name('transactions.')->group(function () {
            Route::get('/', [OrderApiController::class, 'getTransactions']);
            Route::get('/{id}', [OrderApiController::class, 'getTransaction']);
        });
    });
});

// reviews
Route::prefix('reviews')->group(function () {
    Route::get('/', [ProductReviewApiController::class, 'index']);
    Route::post('/', [ProductReviewApiController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/available-for-review', [ProductReviewApiController::class, 'getAvailableProductsForReview'])->middleware('auth:sanctum');
    Route::get('/{id}', [ProductReviewApiController::class, 'show']);
    Route::post('/{id}', [ProductReviewApiController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{id}', [ProductReviewApiController::class, 'destroy'])->middleware('auth:sanctum');
});

// seller feedback
Route::prefix('seller-feedback')->group(function () {
    Route::get('/', [SellerFeedbackApiController::class, 'index']);
    Route::post('/', [SellerFeedbackApiController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/ratings', [SellerFeedbackApiController::class, 'getSellerRatings']);
    Route::get('/{id}', [SellerFeedbackApiController::class, 'show']);
    Route::post('/{id}', [SellerFeedbackApiController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{id}', [SellerFeedbackApiController::class, 'destroy'])->middleware('auth:sanctum');
});

// get banners
Route::get('banners', [BannerApiController::class, 'index']);

// Pages (public)
Route::get('pages/{slug}', [PageApiController::class, 'show'])->name('pages.show');

// Contact form submission (public, rate-limited)
Route::post('contact', [ContactApiController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

// get categories
Route::get('categories', [CategoryApiController::class, 'index']);
Route::get('categories/sub-categories', [CategoryApiController::class, 'subCategories']);
Route::get('categories/find', [CategoryApiController::class, 'find']);
Route::get('categories/{slug}', [CategoryApiController::class, 'show']);

// get brands
Route::get('brands', [BrandApiController::class, 'index']);

// products
Route::prefix('products')->name('products.')->middleware('throttle:60,1')->group(function () {
    Route::get('/', [ProductApiController::class, 'getAllProduct']);
    Route::get('/featured', [ProductApiController::class, 'getFeaturedProduct']);
    Route::get('/new-arrivals', [ProductApiController::class, 'getNewArrivals']);
    Route::get('/by-ids', [ProductApiController::class, 'getByIds']);
    Route::get('/search-by-keywords', [ProductApiController::class, 'searchByKeywords']);
    Route::get('/store-wise', [ProductApiController::class, 'storeWise']);
    Route::get('/{slug}', [ProductApiController::class, 'show']);
    Route::get('/{slug}/faqs', [ProductFaqApiController::class, 'getByProduct']);
    Route::get('/{slug}/reviews', [ProductReviewApiController::class, 'getProductReviews']);
    Route::get('/{slug}/available-order-items', [ProductReviewApiController::class, 'getAvailableOrderItemsForProduct'])->middleware('auth:sanctum');
});

// stores
Route::prefix('stores')->name('stores.')->group(function () {
    Route::get('/', [StoreApiController::class, 'index']);
    Route::get('/{slug}', [StoreApiController::class, 'show']);
//    Route::get('/zone', [StoreApiController::class, 'getStoresByZone']);
});

// delivery zone check
Route::prefix('delivery-zone')->name('delivery_zone.')->group(function () {
    Route::get('/', [DeliveryZoneApiController::class, 'index']);
    Route::get('/check', [DeliveryZoneApiController::class, 'checkDelivery']);
    Route::get('/stores', [StoreApiController::class, 'getStoresByLocation']);
    Route::get('/products', [ProductApiController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/{id}', [DeliveryZoneApiController::class, 'show']);
});

// get faqs
Route::prefix('faqs')->name('faqs.')->group(function () {
    Route::get('/grouped', [FaqApiController::class, 'grouped'])->name('grouped'); // ← specific before wildcard
    Route::get('/', [FaqApiController::class, 'index'])->name('index');
    Route::get('/{id}', [FaqApiController::class, 'show'])->name('show');
});


// get product faqs
Route::prefix('product-faqs')->name('product_faqs.')->group(function () {
    Route::get('/', [ProductFaqApiController::class, 'index']);
    Route::get('/{id}', [ProductFaqApiController::class, 'show']);
});

// featured sections
Route::prefix('featured-sections')->name('featured-sections.')->group(function () {
    Route::get('/', [FeaturedSectionApiController::class, 'index'])->name('index');
    Route::get('/all', [FeaturedSectionApiController::class, 'all'])->name('all');
    Route::get('/types', [FeaturedSectionApiController::class, 'types'])->name('types');
    Route::get('/{slug}', [FeaturedSectionApiController::class, 'show'])->name('show');
    Route::get('/{slug}/products', [FeaturedSectionApiController::class, 'products'])->name('products');
});

Route::get('payment/variables', [PaymentController::class, 'paymentVariables']);

// razorpay routes
Route::prefix('razorpay')->group(function () {
    Route::post('create-order',    [RazorpayController::class, 'createOrder'])->middleware('auth:sanctum');
    Route::post('verify-payment',  [RazorpayController::class, 'verifyPaymentHttp'])->middleware('auth:sanctum');
//    Route::get('payment/{paymentId}', [RazorpayController::class, 'getPaymentDetails']);
});
Route::post('/webhook/razorpay', [RazorpayController::class, 'handleWebhook']);

// stripe routes
Route::post('stripe/create-order', [StripeController::class, 'createOrderPaymentIntent'])->middleware('auth:sanctum');
Route::post('stripe/webhook', [StripeController::class, 'handleWebhook']);
Route::post('stripe/refund-payment', [StripeController::class, 'refundPayment']);

// paystack routes
Route::post('paystack/create-order', [PaystackController::class, 'createOrderPaymentIntent'])->middleware('auth:sanctum');
Route::post('paystack/webhook', [PaystackController::class, 'handleWebhook']);
Route::get('paystack/callback', [PaystackController::class, 'handleCallback'])->name('paystack.callback');
Route::post('paystack/refund', [PaystackController::class, 'refundPayment']);


Route::post('flutterwave/webhook', [FlutterwaveController::class, 'handleWebhook']);

// easepay routes
Route::post('easepay/create-order', [EasepayController::class, 'createOrder'])->middleware('auth:sanctum');
Route::post('easepay/verify-payment', [EasepayController::class, 'verifyPayment'])->middleware('auth:sanctum');
Route::post('easepay/refund', [EasepayController::class, 'refundPayment']);
Route::post('easepay/webhook', [EasepayController::class, 'handleWebhook']);

Route::post('/test-fcm', [\App\Http\Controllers\NotificationController::class, 'test']);
Route::post('/test-fcms', [\App\Http\Controllers\NotificationController::class, 'sendBulk']);

// Gift Cards (public: validate barcode at checkout)
Route::post('gift-cards/validate', [GiftCardController::class, 'validate'])->name('gift-cards.validate');

// Support Tickets (public: types list; auth: customer CRUD)
Route::get('support-ticket-types', [SupportTicketApiController::class, 'types']);
Route::middleware('auth:sanctum')->prefix('user/support-tickets')->group(function () {
    Route::get('/',          [SupportTicketApiController::class, 'index']);
    Route::post('/',         [SupportTicketApiController::class, 'store']);
    Route::get('/{id}',      [SupportTicketApiController::class, 'show']);
    Route::post('/{id}/reply', [SupportTicketApiController::class, 'reply']);
});

// Navigation Menus (public, read-only — consumed by Next.js frontend)
Route::get('menus',          [MenuApiController::class, 'index'])->name('menus.index');
Route::get('footer-menus',   [MenuApiController::class, 'footer'])->name('menus.footer');
Route::get('menus/{slug}',   [MenuApiController::class, 'show'])->name('menus.show');
