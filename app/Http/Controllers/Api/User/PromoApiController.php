<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\OrderPromoLine;
use App\Models\Promo;
use App\Models\Product;
use App\Services\CartService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

#[Group('Promo')]
class PromoApiController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get active promo popup data for storefront visitors.
     */
    public function getPopupPromo(): JsonResponse
    {
        try {
            $now = now();

            $promo = Promo::query()
                ->where(function ($query) use ($now) {
                    $query->where('start_date', '<=', $now)
                        ->orWhereNull('start_date');
                })
                ->where(function ($query) use ($now) {
                    $query->where('end_date', '>=', $now)
                        ->orWhereNull('end_date');
                })
                ->where(function ($query) {
                    $query->whereNull('max_total_usage')
                        ->orWhereRaw('usage_count < max_total_usage');
                })
                ->orderByDesc('start_date')
                ->orderByDesc('created_at')
                ->first();

            if (!$promo) {
                return ApiResponseType::sendJsonResponse(
                    success: true,
                    message: 'No active popup promo available.',
                    data: null
                );
            }

            $products = Product::query()
                ->where('verification_status', ProductVarificationStatusEnum::APPROVED->value)
                ->where('status', ProductStatusEnum::ACTIVE->value)
                ->where('featured', '1')
                ->with([
                    'variants.storeProductVariants',
                ])
                ->orderByDesc('id')
                ->limit(3)
                ->get()
                ->map(function (Product $product) {
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'image_url' => $product->main_image ?: $product->variants->pluck('image')->filter()->first(),
                        'price' => $this->resolveProductPrice($product),
                    ];
                })
                ->values();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('messages.promos_retrieved_successfully'),
                data: [
                    'promo' => [
                        'id' => $promo->id,
                        'code' => $promo->code,
                        'description' => $promo->description,
                        'discount_type' => $promo->discount_type,
                        'discount_amount' => (float) ($promo->discount_amount ?? 0),
                        'discount_label' => $this->formatDiscountLabel($promo),
                        'start_date' => optional($promo->start_date)?->toIso8601String(),
                        'end_date' => optional($promo->end_date)?->toIso8601String(),
                    ],
                    'products' => $products,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: []
            );
        }
    }

    /**
     * Get user available promos
     */
    public function getUserAvailablePromos(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }

        try {
            // Get all active promos that are currently valid
            $now = now();
            $promos = Promo::where(function ($query) use ($now) {
                $query->where('start_date', '<=', $now)
                    ->orWhereNull('start_date');
            })
                ->where(function ($query) use ($now) {
                    $query->where('end_date', '>=', $now)
                        ->orWhereNull('end_date');
                })
                ->where(function ($query) {
                    // Check if promo hasn't reached max total usage
                    $query->whereNull('max_total_usage')
                        ->orWhereRaw('usage_count < max_total_usage');
                })
                ->orderBy('created_at', 'desc')
                ->get();

            // Filter promos based on user-specific usage limits
            $availablePromos = $promos->filter(function ($promo) use ($user) {
                if ($promo->max_usage_per_user) {
                    $userUsageCount = OrderPromoLine::where('promo_id', $promo->id)
                        ->whereHas('order', function ($query) use ($user) {
                            $query->where('user_id', $user->id);
                        })
                        ->count();

                    return $userUsageCount < $promo->max_usage_per_user;
                }
                return true;
            });

            return ApiResponseType::sendJsonResponse(
                true,
                __('messages.promos_retrieved_successfully'),
                $availablePromos->values()
            );

        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.something_went_wrong'),
                []
            );
        }
    }

    /**
     * Validate promo code
     */
    #[QueryParameter('promo_code', description: 'Promo code to validate.', type: 'string', example: 'SAVE20')]
    #[QueryParameter('cart_amount', description: 'Order amount to validate against.', type: 'float', example: 1000.00)]
    #[QueryParameter('delivery_charge', description: 'Delivery Charge amount.', type: 'float', example: 100.00)]
    public function validatePromoCode(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        }

        $promoCode = $request->input('promo_code');
        $cartAmount = (float)$request->input('cart_amount', 0);
        $deliveryCharge = (float)$request->input('delivery_charge');

        if (empty($promoCode)) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('messages.promo_code_required'),
                []
            );
        }

        if ($cartAmount <= 0) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('messages.cart_amount_required'),
                []
            );
        }

        $result = $this->cartService->validatePromoCode(promoCode: $promoCode, user: $user, cartTotal: $cartAmount, deliveryCharge: $deliveryCharge);

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            [
                'promo_code' => $promoCode,
                'discount' => $result['discount'],
                'promo_details' => $result['promo']
            ]
        );
    }

    private function formatDiscountLabel(Promo $promo): string
    {
        return match ($promo->discount_type) {
            'percent' => rtrim(rtrim(number_format((float) $promo->discount_amount, 2, '.', ''), '0'), '.') . '% OFF',
            'flat' => '₹' . rtrim(rtrim(number_format((float) $promo->discount_amount, 2, '.', ''), '0'), '.') . ' OFF',
            'free_shipping' => 'FREE SHIPPING',
            default => 'SPECIAL OFFER',
        };
    }

    private function resolveProductPrice(Product $product): float
    {
        $prices = $product->variants
            ->flatMap(function ($variant) {
                return $variant->storeProductVariants->map(function ($storeVariant) {
                    return $storeVariant->special_price && $storeVariant->special_price > 0
                        ? (float) $storeVariant->special_price
                        : (float) ($storeVariant->price ?? 0);
                });
            })
            ->filter(fn($price) => $price > 0)
            ->values();

        if ($prices->isEmpty()) {
            return 0;
        }

        return (float) $prices->min();
    }
}
