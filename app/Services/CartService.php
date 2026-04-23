<?php

namespace App\Services;

use App\Enums\PromoDiscountTypeEnum;
use App\Enums\PromoModeEnum;
use App\Enums\SettingTypeEnum;
use App\Services\SettingService;
use App\Events\Cart\ItemAddedToCart;
use App\Events\Cart\ItemRemovedFromCart;
use App\Events\Cart\CartUpdatedByLocation;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartSaveForLaterItem;
use App\Models\OrderPromoLine;
use App\Models\Promo;
use App\Models\StoreProductVariant;
use App\Models\User;
use App\Http\Resources\Product\ProductListResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartService
{
    public function __construct(
        protected GstService $gstService
    ) {
    }

    /**
     * Add item to cart
     */
    public function addToCart(User $user, array $data): array
    {
        try {
            // Verify product variant exists in selected store
            $storeProductVariant = StoreProductVariant::where('store_id', $data['store_id'])
                ->where('product_variant_id', $data['product_variant_id'])
                ->with(['productVariant', 'store'])
                ->first();

            if (!$storeProductVariant || empty($storeProductVariant['productVariant'])) {
                return [
                    'success' => false,
                    'message' => __('messages.product_variant_not_available_in_store'),
                    'data' => []
                ];
            }

            // Check if the store is online
            if ($storeProductVariant->store && method_exists($storeProductVariant->store, 'isOffline') && $storeProductVariant->store->isOffline()) {
                return [
                    'success' => false,
                    'message' => __('messages.store_offline_cannot_add_to_cart'),
                    'data' => ['store_id' => $storeProductVariant->store->id]
                ];
            }

            // Get or create cart for user
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['uuid' => Str::uuid()->toString()]
            );

            // Check if item already exists in cart
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $storeProductVariant['productVariant']['product_id'])
                ->where('product_variant_id', $data['product_variant_id'])
                ->where('store_id', $data['store_id'])
                ->first();
            $saveForLaterItem = CartSaveForLaterItem::where('cart_id', $cart->id)
                ->where('product_id', $storeProductVariant['productVariant']['product_id'])
                ->where('product_variant_id', $data['product_variant_id'])
                ->where('store_id', $data['store_id'])
                ->first();
            $userCart = $this->getUserCart($user);

            // Validate checkout type (single or multi store) similar to OrderService::validateCartAndSettings
            try {
                $settingService = app(SettingService::class);
                $settings = $settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
                $checkoutType = $settings->value['checkoutType'] ?? null;

                if ($checkoutType === 'single_store') {
                    $existingStoreIds = collect($userCart?->items ?? [])->pluck('store_id')->filter()->unique();
                    // If cart already has items and the incoming item is from a different store, block it
                    if ($existingStoreIds->count() > 0 && !$existingStoreIds->contains($data['store_id'])) {
                        return [
                            'success' => false,
                            'message' => __('labels.checkout_type_single_store_error'),
                            'data' => []
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Fail safe: do not block add-to-cart if settings fetching fails, but log it
                Log::warning('Checkout type validation failed while adding to cart', [
                    'error' => $e->getMessage(),
                ]);
            }

            $requestedQuantity = $data['quantity'] ?? 1;
            // Validate product-level quantity rules (min, step, total allowed)
            $product = $storeProductVariant->productVariant->product ?? null;
            $minQty = $product->minimum_order_quantity ?? 1;
            $stepQty = $product->quantity_step_size ?? 1;
            $totalAllowed = $product->total_allowed_quantity ?? null;

            // When adding, the requested quantity is either new or will be added to existing
            $effectiveQuantity = $requestedQuantity;
            if ($cartItem) {
                $effectiveQuantity = $cartItem->quantity + $requestedQuantity;
            }

            if ($effectiveQuantity < $minQty) {
                return [
                    'success' => false,
                    'message' => __('messages.quantity_below_minimum_order_quantity'),
                    'data' => ['minimum_order_quantity' => $minQty]
                ];
            }

            // Enforce step size relative to minimum order quantity
            $stepQty = max(1, (int) $stepQty);
            if ($stepQty > 1) {
                if ((($effectiveQuantity - $minQty) % $stepQty) !== 0) {
                    return [
                        'success' => false,
                        'message' => __('messages.quantity_must_be_multiple_of_step'),
                        'data' => ['quantity_step_size' => $stepQty, 'minimum_order_quantity' => $minQty]
                    ];
                }
            }

            if (!is_null($totalAllowed) && $totalAllowed > 0 && $effectiveQuantity > $totalAllowed) {
                return [
                    'success' => false,
                    'message' => __('messages.quantity_exceeds_total_allowed_quantity'),
                    'data' => ['total_allowed_quantity' => $totalAllowed]
                ];
            }
            DB::beginTransaction();
            if ($cartItem) {
                if ($cartItem->save_for_later === true) {
                    $cartItem->update(['save_for_later' => '0']);
                }
                // Update quantity
                $newQuantity = $cartItem->quantity + $requestedQuantity;

                $cartItem->update(['quantity' => $newQuantity]);
            } elseif ($saveForLaterItem) {
                $res = $this->validateCartMaxItems($userCart);
                if (!$res) {
                    return [
                        'success' => false,
                        'message' => __('messages.maximum_items_allowed_in_cart_reached'),
                        'data' => []
                    ];
                }

                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $saveForLaterItem->product_id,
                    'product_variant_id' => $saveForLaterItem->product_variant_id,
                    'store_id' => $saveForLaterItem->store_id,
                    'quantity' => $saveForLaterItem->quantity,
                    'save_for_later' => '0',
                ]);
                $saveForLaterItem->delete();
            } else {
                // Check if requested quantity is available
                $res = $this->validateCartMaxItems($userCart);
                if (!$res) {
                    return [
                        'success' => false,
                        'message' => __('messages.maximum_items_allowed_in_cart_reached'),
                        'data' => []
                    ];
                }
                // Create new cart item
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $storeProductVariant['productVariant']['product_id'],
                    'product_variant_id' => $data['product_variant_id'],
                    'store_id' => $data['store_id'],
                    'quantity' => $requestedQuantity,
                    'save_for_later' => '0' // Changed from false to '0'
                ]);
            }

            // Load cart with items
            $cart->load(['items.product', 'items.variant', 'items.store']);

            // Fire event
            event(new ItemAddedToCart($cart, $cartItem, $user));

            DB::commit();

            return [
                'success' => true,
                'message' => __('messages.item_added_to_cart_successfully'),
                'data' => $cart
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    public static function validateCartMaxItems($cart): bool
    {
        $settingService = new SettingService();
        $setting = $settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
        $maxCartItems = (float) ($setting->value['maximumItemsAllowedInCart'] ?? 0);

        // 0 means unlimited — admin has not configured a cap
        if ($maxCartItems <= 0) {
            return true;
        }

        $itemCount = ($cart && $cart->relationLoaded('items'))
            ? $cart->items->count()
            : (($cart && $cart->items) ? $cart->items->count() : 0);
        if ($itemCount >= $maxCartItems) {
            return false;
        }
        return true;
    }

    public function syncMultiStoreCart(User $user, array $data): array
    {
        $synced = [];
        $failed = [];

        DB::beginTransaction();

        try {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['uuid' => Str::uuid()->toString()]
            );

            $desiredKeys = collect($data['items'])
                ->map(fn ($item) => $item['store_id'] . ':' . $item['product_variant_id'])
                ->all();

            CartItem::where('cart_id', $cart->id)
                ->where('save_for_later', '0')
                ->get()
                ->each(function (CartItem $cartItem) use ($desiredKeys) {
                    $itemKey = $cartItem->store_id . ':' . $cartItem->product_variant_id;
                    if (!in_array($itemKey, $desiredKeys, true)) {
                        $cartItem->delete();
                    }
                });

            foreach ($data['items'] as $item) {
                $matchingItems = CartItem::where('cart_id', $cart->id)
                    ->where('store_id', $item['store_id'])
                    ->where('product_variant_id', $item['product_variant_id'])
                    ->where('save_for_later', '0')
                    ->orderBy('id')
                    ->get();

                $primaryItem = $matchingItems->first();

                if ($matchingItems->count() > 1 && $primaryItem) {
                    CartItem::whereIn('id', $matchingItems->slice(1)->pluck('id'))->delete();
                }

                if ($primaryItem) {
                    $result = $this->updateCartItemQuantity($user, $primaryItem->id, $item['quantity']);
                } else {
                    $result = $this->addToCart($user, [
                        'store_id' => $item['store_id'],
                        'product_variant_id' => $item['product_variant_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }

                // Load product resource for the provided store and variant
                $storeProductVariant = StoreProductVariant::with([
                    'productVariant.product.variants.storeProductVariants.store',
                    'productVariant.product.category',
                    'productVariant.product.brand',
                    'productVariant.product.seller.user',
                    'store',
                    'productVariant',
                ])
                    ->where('store_id', $item['store_id'])
                    ->where('product_variant_id', $item['product_variant_id'])
                    ->first();

                // Build ProductListResource from the linked product if available
                $productResource = null;
                if ($storeProductVariant && $storeProductVariant->productVariant && $storeProductVariant->productVariant->product) {
                    $product = $storeProductVariant->productVariant->product;
                    $productResource = new ProductListResource($product->loadMissing([
                        'variants.storeProductVariants.store',
                        'category',
                        'brand',
                        'seller.user',
                    ]));
                }

                if ($result['success']) {
                    $synced[] = [
                        'store_id' => $item['store_id'],
                        'product_variant_id' => $item['product_variant_id'],
                        'quantity' => $item['quantity'],
                        'product' => $productResource,
                    ];
                } else {
                    $failed[] = [
                        'store_id' => $item['store_id'],
                        'product_variant_id' => $item['product_variant_id'],
                        'product' => $productResource,
                        'reason' => $result['message'],
                    ];
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => __('labels.cart_synced_successfully'),
                'data' => [
                    'synced_items' => $synced,
                    'failed_items' => $failed,
                    'cart' => $this->getUserCart($user),
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Multi-store cart sync failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => [],
            ];
        }
    }


    /**
     * Calculate cart item prices and totals
     *
     * @param Cart $cart The cart to calculate totals for
     * @return array Array containing items_total and store_ids
     */
    private function calculateCartTotals(Cart $cart): array
    {
        $itemsTotal = 0;
        $totalGst = 0;
        $storeIds = [];

        foreach ($cart->items as $key => $item) {
            if (empty($item->variant)) {
                $item->delete();
                continue;
            }

            if (!in_array($item->store_id, $storeIds)) {
                $storeIds[] = $item->store_id;
            }

            $storeVariant = $item->variant->storeProductVariants->where('store_id', $item->store->id)->first();
            if (!$storeVariant) {
                continue;
            }

            $product = $item->product;
            $specialPrice = (float) ($storeVariant->special_price_exclude_tax ?? $storeVariant->getRawOriginal('special_price') ?? 0);
            $basePrice = (float) ($storeVariant->price_exclude_tax ?? $storeVariant->getRawOriginal('price') ?? 0);
            $unitPrice = $specialPrice > 0 ? $specialPrice : $basePrice;
            $gstRatePct = $product ? $this->gstService->resolveProductGstRate($product) : 0;
            $gstData = $this->gstService->calculateLineItem(
                unitPrice: $unitPrice,
                quantity: (int) $item->quantity,
                gstRatePct: $gstRatePct,
                supplyType: 'intra',
                priceInclusive: (bool) ($product->is_inclusive_tax ?? false)
            );

            $cart->items[$key]->price = $gstData['taxable_amount'];
            $cart->items[$key]->special_price = $gstData['total_amount'];
            $cart->items[$key]->total_tax_amount = $gstData['total_tax_amount'];

            $itemsTotal += $gstData['taxable_amount'];
            $totalGst += $gstData['total_tax_amount'];
        }

        $cart->items_total = $itemsTotal;
        $cart->total_gst = $totalGst;

        return [
            'items_total' => $itemsTotal,
            'total_gst' => $totalGst,
            'store_ids' => $storeIds
        ];
    }

    /**
     * Get user's cart with updated location information
     *
     * @param User $user The user whose cart to retrieve
     * @param float|null $latitude User's latitude coordinate
     * @param float|null $longitude User's longitude coordinate
     * @param bool $isRushDelivery Whether to use rush delivery
     * @param bool $useWallet Whether to use wallet balance for payment
     * @param string|null $promoCode Promo code to apply discount
     * @return array Cart data with success status and message
     */
    public function getCart(User $user, ?float $latitude = null, ?float $longitude = null, bool $isRushDelivery = false, bool $useWallet = false, ?string $promoCode = null, $addressId = null): array
    {
        try {
            DB::beginTransaction();

            // Get and validate user's cart
            $cart = $this->getUserCart($user);
            if (!$cart || $cart->items->isEmpty()) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => __('messages.cart_is_empty'),
                    'data' => []
                ];
            }

            // Validate delivery zone and rush delivery if coordinates are provided
            if ($latitude !== null && $longitude !== null) {
                $zoneResult = $this->validateDeliveryZone($latitude, $longitude, $isRushDelivery);
                if (!$zoneResult['success']) {
                    return $zoneResult;
                }
                $zone = $zoneResult['zone'];
                $zone['rush_delivery_available'] = $zoneResult['rush_delivery_available'];
                if ($isRushDelivery && !$zoneResult['rush_delivery_available']) {
                    $zone['rush_delivery_error_message'] = $zoneResult['message'];
                    // Force regular delivery when rush delivery is not available
                    $isRushDelivery = false;
                }

                // Check delivery availability and process cart items
                $processResult = $this->processCartItems($cart, $latitude, $longitude, $user);
                $removedItems = $processResult['removed_items'];

            } else {
                $zone = null;
                $removedItems = [];
            }
            if (empty($addressId)) {
                $latitude = null;
                $longitude = null;
//                $isRushDelivery = false;
//                $useWallet = false;
//                $promoCode = null;
            }
            // Calculate payment summary
            $cart = $this->calculateCartPaymentSummary(cart: $cart, latitude: $latitude, longitude: $longitude, isRushDelivery: $isRushDelivery, useWallet: $useWallet, removedItems: $removedItems, user: $user, promoCode: $promoCode);
            DB::commit();

            // Prepare and return the response
            return $this->prepareCartResponse($cart, $removedItems, $zone);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CartService::getCart failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Get and validate user's cart
     *
     * @param User $user The user whose cart to retrieve
     * @return Cart Result containing success status and cart if found
     */
    public static function getUserCart(User $user): ?Cart
    {
        return Cart::with(['items' => function ($query) {
            $query->where('save_for_later', "0");
        }, 'items.product', 'items.variant', 'items.store'])
            ->where('user_id', $user->id)->first();
    }

    public function getSaveForLaterCart(User $user): ?Cart
    {
        $cart = Cart::with([
            'saveForLaterItems.product',
            'saveForLaterItems.variant',
            'saveForLaterItems.store',
        ])->where('user_id', $user->id)->first();

        if (!$cart) {
            return null;
        }

        // Keep CartResource response shape unchanged by mapping saveForLaterItems as items.
        $cart->setRelation('items', $cart->saveForLaterItems);
        return $cart;
    }

    public static function cartStoreCount(User $user): int
    {
        $cart = Cart::with(['items' => function ($query) {
            $query->where('save_for_later', "0");
            $query->groupBy('store_id');
        }])
            ->where('user_id', $user->id)->first();
        return $cart?->items?->count() ?? 0;
    }

    /**
     * Validate delivery zone and rush delivery availability
     *
     * @param float $latitude User's latitude coordinate
     * @param float $longitude User's longitude coordinate
     * @param bool $isRushDelivery Whether to use rush delivery
     * @return array Result containing success status and zone if valid
     */
    private function validateDeliveryZone(float $latitude, float $longitude, bool $isRushDelivery): array
    {
        $zone = DeliveryZoneService::getZonesAtPoint($latitude, $longitude);
        if (empty($zone) || !$zone['exists']) {
            return [
                'success' => false,
                'message' => __('messages.invalid_coordinates'),
                'parameters' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'is_rush_delivery' => $isRushDelivery,
                ],
                'rush_delivery_available' => false,
                'zone' => null,
            ];
        }

        $rushDeliveryAvailable = $this->isRushDeliveryAvailable($zone);

        if ($isRushDelivery && !$rushDeliveryAvailable) {
            return [
                'success' => true,
                'message' => __('labels.rush_delivery_not_available_for_this_zone'),
                'parameters' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'is_rush_delivery' => $isRushDelivery,
                    'zone_id' => $zone['id'] ?? null,
                    'zone_name' => $zone['name'] ?? null,
                ],
                'rush_delivery_available' => false,
                'zone' => $zone,
            ];
        }

        return [
            'success' => true,
            'message' => __('labels.valid_delivery_zone'),
            'parameters' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_rush_delivery' => $isRushDelivery,
                'zone_id' => $zone['id'] ?? null,
                'zone_name' => $zone['name'] ?? null,
            ],
            'rush_delivery_available' => $rushDeliveryAvailable,
            'zone' => $zone,
        ];
    }

    /**
     * Process cart items based on delivery availability
     *
     * @param Cart $cart The cart to process
     * @param float $latitude User's latitude coordinate
     * @param float $longitude User's longitude coordinate
     * @return array Result containing processed cart and removed items
     */
    private function processCartItems(Cart $cart, float $latitude, float $longitude, $user): array
    {
        // Check delivery availability and remove unavailable items
        $availabilityResult = DeliveryZoneService::checkDeliveryAvailability($cart, $latitude, $longitude);
        $removedItems = $availabilityResult['removed_items'];
        $reassignedItems = $availabilityResult['reassigned_items'];

        // Reload the cart with remaining items
        $cart = $this->getUserCart($user);
        return [
            'cart' => $cart,
            'removed_items' => $removedItems,
            'reassigned_items' => $reassignedItems
        ];
    }

    /**
     * Calculate payment summary for the cart
     *
     * @param Cart $cart The cart to calculate payment for
     * @param bool $isRushDelivery Whether to use rush delivery
     * @param bool $useWallet Whether to use wallet balance for payment
     * @param array $removedItems Items removed from the cart due to availability
     * @param User $user The user who owns the cart
     * @param float|null $latitude User's latitude coordinate
     * @param float|null $longitude User's longitude coordinate
     * @param string|null $promoCode Promo code to apply discount
     * @return Cart Cart with payment summary attached
     */
    private function calculateCartPaymentSummary(Cart $cart, bool $isRushDelivery, bool $useWallet, array $removedItems, User $user, ?float $latitude = null, ?float $longitude = null, ?string $promoCode = null): Cart
    {
        try {
            if ($latitude !== null && $longitude !== null) {

                // Get all the payment-related summary
                $paymentSummary = $this->getPaymentSummary(
                    cart: $cart,
                    latitude: $latitude,
                    longitude: $longitude,
                    isRushDelivery: $isRushDelivery,
                    useWallet: $useWallet,
                    promoCode: $promoCode
                );
                $cart->payment_summary = $paymentSummary;
                // Fire event
                event(new CartUpdatedByLocation($cart, $removedItems, $user, $latitude, $longitude));
            } else {
                $cart->payment_summary = $this->createDefaultPaymentSummary($cart, $isRushDelivery, $useWallet);
            }

        } catch (\Throwable $e) {
            Log::error('Error getting payment summary: ' . $e->getMessage());
            // Set empty payment summary to avoid null reference
            $cart->payment_summary = $this->createDefaultPaymentSummary($cart, $isRushDelivery, $useWallet);
        }
        return $cart;
    }

    /**
     * Prepare the cart response
     *
     * @param Cart $cart The cart to include in the response
     * @param array $removedItems Items removed from cart due to availability
     * @param array|null $zone Delivery zone information
     * @return array Response with cart data
     */
    private function prepareCartResponse(Cart $cart, array $removedItems, ?array $zone): array
    {
        return [
            'success' => true,
            'message' => count($removedItems) > 0 ? __('messages.cart_updated_based_on_location') : __('messages.cart_location_verified'),
            'data' => [
                'cart' => $cart,
                'removed_items' => $removedItems,
                'removed_count' => count($removedItems),
                'delivery_zone' => $zone
            ]
        ];
    }

    /**
     * Calculate payment summary for a cart
     *
     * @param Cart $cart The cart to calculate payment summary for
     * @param float $latitude User's latitude coordinate
     * @param float $longitude User's longitude coordinate
     * @param bool $isRushDelivery Whether to use rush delivery
     * @param bool $useWallet Whether to use wallet balance for payment
     * @param string|null $promoCode Promo code to apply discount
     * @return array Payment summary details
     */
    public function getPaymentSummary(Cart $cart, float $deliveryCharge = 0, bool $isRushDelivery = false, bool $useWallet = false, string $promoCode = null): array
    {
        try {
            $totalsResult = $this->calculateCartTotals($cart);
            $itemsTotal   = $totalsResult['items_total'];
            $totalGst     = $totalsResult['total_gst'] ?? 0;

            $totalDeliveryCharges = $deliveryCharge;

            // Calculate payable amount
            $payableAmount = $itemsTotal + $totalGst + $totalDeliveryCharges;

            // Apply promo code discount if provided
            $promoDiscount      = 0;
            $promoCodeApplied   = null;
            $promoValidationError = null;

            if (!empty($promoCode)) {
                $promoResult = $this->validateAndApplyPromoCode(promoCode: $promoCode, user: $cart->user, cartTotal: $itemsTotal, deliveryCharge: $totalDeliveryCharges);
                if ($promoResult['success']) {
                    $promoDiscount    = $promoResult['discount'];
                    $promoCodeApplied = $promoResult['promo'];
                    if ($promoCodeApplied['promo_mode'] === PromoModeEnum::INSTANT()) {
                        $payableAmount = max(0, $payableAmount - $promoDiscount);
                    }
                } else {
                    $promoValidationError = $promoResult['message'];
                }
            }

            // Get wallet balance if using wallet
            $walletAmountUsed = 0;
            $orderTotal       = $payableAmount;
            $wallet           = $cart->user->wallet()->first();
            $walletBalance    = !empty($wallet) ? $wallet->balance : 0;
            if ($useWallet && $wallet) {
                $walletAmountUsed = min($walletBalance, $payableAmount);
                $payableAmount    = $payableAmount - $walletAmountUsed;
            }

            return [
                'items_total'              => (float) $itemsTotal,
                'total_gst'                => (float) $totalGst,
                'per_store_drop_off_fee'   => 0.0,
                'is_rush_delivery'         => $isRushDelivery,
                'is_rush_delivery_available' => false,
                'delivery_charges'         => (float) $deliveryCharge,
                'handling_charges'         => 0.0,
                'delivery_distance_charges'=> 0.0,
                'delivery_distance_km'     => 0.0,
                'total_stores'             => 0.0,
                'total_delivery_charges'   => (float) $totalDeliveryCharges,
                'estimated_delivery_time'  => 0.0,
                'promo_code'               => $promoCode,
                'promo_discount'           => (float) $promoDiscount,
                'promo_applied'            => $promoCodeApplied,
                'promo_error'              => $promoValidationError,
                'use_wallet'               => $useWallet,
                'wallet_balance'           => (float) $walletBalance,
                'wallet_amount_used'       => (float) $walletAmountUsed,
                'payable_amount'           => (float) $payableAmount,
                'order_total'              => (float) $orderTotal,
            ];
        } catch (\Throwable $e) {
            Log::error('Error calculating payment summary: ' . $e->getMessage());
            return $this->createDefaultPaymentSummary($cart, $isRushDelivery, $useWallet);
        }
    }

    /**
     * Validate required zone data
     *
     * @param array $zone Delivery zone information
     * @param bool $isRushDelivery Whether to validate rush delivery data
     * @throws \Exception If required zone data is missing
     */
    private function validateZoneData(array $zone, bool $isRushDelivery = false): void
    {
        // Validate basic required fields
        if (!isset($zone['per_store_drop_off_fee']) ||
            !isset($zone['regular_delivery_charges']) ||
            !isset($zone['handling_charges']) ||
            !isset($zone['free_delivery_amount'])) {
            throw new \Exception('Missing required delivery zone data');
        }

        // If rush delivery is requested, validate rush delivery fields
        // Note: We no longer throw exceptions for rush delivery unavailability
        // as we handle this gracefully by falling back to regular delivery
        if ($isRushDelivery) {
            if (!isset($zone['rush_delivery_charges']) || !isset($zone['rush_delivery_time_per_km'])) {
                throw new \Exception('Missing required rush delivery data');
            }
        }
    }

    /**
     * Calculate delivery distance information
     *
     * @param array $storeIds Array of store IDs
     * @param array $zone Delivery zone information
     * @param float $latitude User's latitude coordinate
     * @param float $longitude User's longitude coordinate
     * @return array Distance information including distance_km and distance_charges
     */
    private function calculateDeliveryDistanceInfo(array $storeIds, array $zone, float $latitude, float $longitude): array
    {
        $deliveryDistanceKm = 0;
        $deliveryDistanceCharges = 0;

        if (!empty($storeIds)) {
            try {
                $routeInfo = DeliveryZoneService::calculateDeliveryRoute($latitude, $longitude, $storeIds);

                // Add delivery distance-based charges if applicable
                if (isset($routeInfo['total_distance']) &&
                    $routeInfo['total_distance'] > 0 &&
                    isset($zone['distance_based_delivery_charges'])) {
                    $deliveryDistanceKm = $routeInfo['total_distance'];
                    $deliveryDistanceCharges = $zone['distance_based_delivery_charges'] * $routeInfo['total_distance'];
                }
            } catch (\Exception $e) {
                // Log the error but continue with calculation
                Log::error('Error calculating delivery route: ' . $e->getMessage());
            }
        }

        return [
            'distance_km' => $deliveryDistanceKm,
            'distance_charges' => $deliveryDistanceCharges
        ];
    }

    /**
     * Calculate estimated delivery time
     *
     * @param Cart $cart The cart to calculate delivery time for
     * @param array $zone Delivery zone information
     * @param float $deliveryDistanceKm Delivery distance in kilometers
     * @param bool $isRushDelivery Whether to use rush delivery time
     * @return int Estimated delivery time in minutes
     */
    private function calculateEstimatedDeliveryTime(Cart $cart, array $zone, float $deliveryDistanceKm, bool $isRushDelivery = false): int
    {
        if ($deliveryDistanceKm <= 0) {
            return 0;
        }

        // Find maximum base preparation time from all products
        $maxBasePrepTime = 0;
        foreach ($cart->items as $item) {
            $product = $item->product;
            if ($product && $product->base_prep_time > $maxBasePrepTime) {
                $maxBasePrepTime = $product->base_prep_time;
            }
        }

        // Determine which delivery time per km to use based on rush delivery flag
        $deliveryTimePerKm = $isRushDelivery && isset($zone['rush_delivery_time_per_km'])
            ? $zone['rush_delivery_time_per_km']
            : ($zone['delivery_time_per_km'] ?? 0);

        $bufferTime = $zone['buffer_time'] ?? 0;

        // Calculate estimated time using the formula
        $estimatedTime = $maxBasePrepTime + ($deliveryDistanceKm * $deliveryTimePerKm) + $bufferTime;

        // Round to the nearest minute
        return ceil($estimatedTime);
    }

    /**
     * Create a default payment summary with zeros
     *
     * @param Cart $cart The cart to create default summary for
     * @param bool $isRushDelivery Whether this is a rush delivery
     * @param bool $useWallet Whether to use wallet balance for payment
     * @return array Default payment summary
     */
    private function createDefaultPaymentSummary(Cart $cart, bool $isRushDelivery = false, bool $useWallet = false): array
    {
        $totalsResult = $this->calculateCartTotals($cart);
        $itemsTotal = $totalsResult['items_total'] ?? 0;
        $totalGst = $totalsResult['total_gst'] ?? 0;
        $walletBalance = 0;
        $walletAmountUsed = 0;
        $remainingPayable = $itemsTotal + $totalGst;
        $orderTotal = $remainingPayable;
        $wallet = $cart->user->wallet()->first();
        $walletBalance = !empty($wallet) ? $wallet->balance : 0;
        if ($useWallet && $cart->user) {
            if ($wallet) {
                $walletAmountUsed = min($walletBalance, $remainingPayable);
                $remainingPayable = $remainingPayable - $walletAmountUsed;
            }
        }

        return [
            'items_total' => $itemsTotal,
            'total_gst' => $totalGst,
            'per_store_drop_off_fee' => 0,
            'is_rush_delivery' => $isRushDelivery,
            'is_rush_delivery_available' => false,
            'delivery_charges' => 0,
            'handling_charges' => 0,
            'delivery_distance_charges' => 0,
            'delivery_distance_km' => 0,
            'total_stores' => 0,
            'total_delivery_charges' => 0,
            'estimated_delivery_time' => 0,
            'use_wallet' => $useWallet,
            'wallet_balance' => $walletBalance,
            'wallet_amount_used' => $walletAmountUsed,
            'payable_amount' => $remainingPayable,
            'order_total' => (float)$orderTotal
        ];
    }

    /**
     * Remove item from the cart
     */
    public function removeFromCart(User $user, int $cartItemId): array
    {
        try {
            DB::beginTransaction();

            $cartItem = CartItem::whereHas('cart', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->find($cartItemId);

            if (!$cartItem) {
                return [
                    'success' => false,
                    'message' => __('messages.cart_item_not_found'),
                    'data' => []
                ];
            }

            // Load relationships before deletion
            $cartItem->load(['product', 'variant', 'store']);
            $cart = $cartItem->cart;

            // Delete the item
            $cartItem->delete();

            // Fire event
            event(new ItemRemovedFromCart($cart, $cartItem, $user));

            // Get updated cart
            $cart = $this->getUserCart($user);

            DB::commit();

            return [
                'success' => true,
                'message' => __('messages.item_removed_from_cart_successfully'),
                'data' => $cart
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }


    /**
     * Save cart item for later
     */
    public function addToSaveForLater(User $user, int $cartItemId): array
    {
        try {
            DB::beginTransaction();

            $cartItem = CartItem::whereHas('cart', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->find($cartItemId);

            if (!$cartItem) {
                return [
                    'success' => false,
                    'message' => __('messages.cart_item_not_found'),
                    'data' => []
                ];
            }

            // Load relationships before move
            $cartItem->load(['product', 'variant', 'store']);
            $cart = $cartItem->cart;

            $existingSaveItem = CartSaveForLaterItem::where('cart_id', $cart->id)
                ->where('product_id', $cartItem->product_id)
                ->where('product_variant_id', $cartItem->product_variant_id)
                ->where('store_id', $cartItem->store_id)
                ->first();

            if ($existingSaveItem) {
                $existingSaveItem->update([
                    'quantity' => $existingSaveItem->quantity + $cartItem->quantity,
                    'save_for_later' => '1',
                ]);
            } else {
                CartSaveForLaterItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $cartItem->product_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'store_id' => $cartItem->store_id,
                    'quantity' => $cartItem->quantity,
                    'save_for_later' => '1',
                ]);
            }

            $cartItem->delete();

            // Fire event
            event(new ItemRemovedFromCart($cart, $cartItem, $user));

            // Get updated cart
            $cart = $this->getSaveForLaterCart($user);

            DB::commit();

            return [
                'success' => true,
                'message' => __('labels.item_saved_for_later_successfully'),
                'data' => $cart
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItemQuantity(User $user, int $cartItemId, int $quantity): array
    {
        try {
            DB::beginTransaction();

            $cartItem = CartItem::whereHas('cart', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->find($cartItemId);

            if (!$cartItem) {
                return [
                    'success' => false,
                    'message' => __('messages.cart_item_not_found'),
                    'data' => []
                ];
            }

            // Check stock availability
            $storeProductVariant = StoreProductVariant::where('store_id', $cartItem->store_id)
                ->where('product_variant_id', $cartItem->product_variant_id)
                ->first();

            if (!$storeProductVariant) {
                return [
                    'success' => false,
                    'message' => __('messages.product_variant_not_available_in_store'),
                    'data' => []
                ];
            }

            // Validate product-level quantity rules (min, step, total allowed)
            $product = $cartItem->product ?? ($cartItem->variant?->product ?? null);
            $minQty = $product->minimum_order_quantity ?? 1;
            $stepQty = $product->quantity_step_size ?? 1;
            $totalAllowed = $product->total_allowed_quantity ?? null;

            if ($quantity < $minQty) {
                return [
                    'success' => false,
                    'message' => __('messages.quantity_below_minimum_order_quantity'),
                    'data' => ['minimum_order_quantity' => $minQty]
                ];
            }

            $stepQty = max(1, (int) $stepQty);
            if ($stepQty > 1) {
                if ((($quantity - $minQty) % $stepQty) !== 0) {
                    return [
                        'success' => false,
                        'message' => __('messages.quantity_must_be_multiple_of_step'),
                        'data' => ['quantity_step_size' => $stepQty, 'minimum_order_quantity' => $minQty]
                    ];
                }
            }

            if (!is_null($totalAllowed) && $totalAllowed > 0 && $quantity > $totalAllowed) {
                return [
                    'success' => false,
                    'message' => __('messages.quantity_exceeds_total_allowed_quantity'),
                    'data' => ['total_allowed_quantity' => $totalAllowed]
                ];
            }

            $cartItem->update(['quantity' => $quantity]);

            // Get updated cart
            $cart = $this->getUserCart($user);

            DB::commit();

            return [
                'success' => true,
                'message' => __('messages.cart_item_quantity_updated_successfully'),
                'data' => $cart
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart(User $user): array
    {
        try {
            DB::beginTransaction();

            $cart = $this->getUserCart($user);

            if (!$cart) {
                return [
                    'success' => true,
                    'message' => __('messages.cart_is_empty'),
                    'data' => []
                ];
            }

            // Delete all cart items
            $cart->items()->where('save_for_later', '0')->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => __('messages.cart_cleared_successfully'),
                'data' => []
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }


    /**
     * Update cart based on user location
     *
     * @param User $user The user whose cart to update
     * @param float $latitude User's latitude coordinate
     * @param float $longitude User's longitude coordinate
     * @param bool $isRushDelivery Whether to use rush delivery
     * @param bool $useWallet Whether to use wallet balance for payment
     * @return array Cart data with success status and message
     */
    public function updateCartByLocation(User $user, float $latitude, float $longitude, bool $isRushDelivery = false, bool $useWallet = false): array
    {
        // This method is essentially the same as getCart but specifically for location updates
        // We can reuse all the helper methods we created for getCart
        return $this->getCart($user, $latitude, $longitude, $isRushDelivery, $useWallet);
    }

    /**
     * Check if rush delivery is available in the given zone
     *
     * @param array $zone Delivery zone information
     * @return bool True if rush delivery is available, false otherwise
     */
    private function isRushDeliveryAvailable(array $zone): bool
    {
        return isset($zone['rush_delivery_enabled']) &&
            $zone['rush_delivery_enabled'] &&
            isset($zone['rush_delivery_charges']) &&
            isset($zone['rush_delivery_time_per_km']);
    }

    /**
     * Public method to validate promo code
     *
     * @param string $promoCode The promo code to validate
     * @param User $user The user applying the promo code
     * @param float $cartTotal The order amount before discount
     * @return array Result with success status, discount amount, and promo details
     */
    public function validatePromoCode(string $promoCode, User $user, float $cartTotal, float $deliveryCharge): array
    {
        return $this->validateAndApplyPromoCode($promoCode, $user, $cartTotal, $deliveryCharge);
    }

    /**
     * Validate and apply promo code discount
     *
     * @param string $promoCode The promo code to validate
     * @param User $user The user applying the promo code
     * @param float $cartTotal The order amount before discount
     * @return array Result with success status, discount amount, and promo details
     */
    private function validateAndApplyPromoCode(string $promoCode, User $user, float $cartTotal, float $deliveryCharge): array
    {
        try {
            // Find the promo code
            $promo = Promo::where('code', $promoCode)->first();

            if (!$promo) {
                return [
                    'success' => false,
                    'message' => __('messages.invalid_promo_code'),
                    'discount' => 0,
                    'promo' => null
                ];
            }

            // Check if promo code is active (not soft deleted)
            if ($promo->deleted_at) {
                return [
                    'success' => false,
                    'message' => __('messages.promo_code_expired'),
                    'discount' => 0,
                    'promo' => null
                ];
            }

            // Check date validity
            $now = now();
            if ($promo->start_date && $now->lt($promo->start_date)) {
                return [
                    'success' => false,
                    'message' => __('messages.promo_code_not_yet_active'),
                    'discount' => 0,
                    'promo' => null
                ];
            }

            if ($promo->end_date && $now->gt($promo->end_date)) {
                return [
                    'success' => false,
                    'message' => __('messages.promo_code_expired'),
                    'discount' => 0,
                    'promo' => null
                ];
            }

            // Check minimum order total
            if ($promo->min_order_total && $cartTotal < $promo->min_order_total) {
                return [
                    'success' => false,
                    'message' => __('messages.minimum_order_amount_not_met', ['amount' => $promo->min_order_total]),
                    'discount' => 0,
                    'promo' => null
                ];
            }

            // Check maximum total usage
            if ($promo->max_total_usage && $promo->usage_count >= $promo->max_total_usage) {
                return [
                    'success' => false,
                    'message' => __('messages.promo_code_usage_limit_exceeded'),
                    'discount' => 0,
                    'promo' => null
                ];
            }

            // Check maximum usage per user
            if ($promo->max_usage_per_user) {
                $userUsageCount = OrderPromoLine::where('promo_id', $promo->id)
                    ->whereHas('order', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->count();

                if ($userUsageCount >= $promo->max_usage_per_user) {
                    return [
                        'success' => false,
                        'message' => __('messages.promo_code_user_limit_exceeded'),
                        'discount' => 0,
                        'promo' => null
                    ];
                }
            }

            // Calculate discount
            $discount = 0;
            if ($promo->discount_type === PromoDiscountTypeEnum::PERCENTAGE()) {
                $discount = ($cartTotal * $promo->discount_amount) / 100;
                if ($promo->max_discount_value && $discount > $promo->max_discount_value) {
                    $discount = $promo->max_discount_value;
                }
            } elseif ($promo->discount_type === PromoDiscountTypeEnum::FIXED()) {
                $discount = $promo->discount_amount;
            } elseif ($promo->discount_type === PromoDiscountTypeEnum::FREE_SHIPPING()) {
                $discount = $deliveryCharge;
            }

            // Ensure discount doesn't exceed order amount
            $discount = min($discount, $cartTotal);

            return [
                'success' => true,
                'message' => __('messages.promo_code_applied_successfully'),
                'discount' => $discount,
                'promo' => $promo
            ];

        } catch (\Exception $e) {
            Log::error('Error validating promo code: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => __('messages.promo_code_validation_error'),
                'discount' => 0,
                'promo' => null
            ];
        }
    }
}
