<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\Seller\SellerVerificationStatusEnum;
use App\Enums\Seller\SellerVisibilityStatusEnum;
use App\Http\Requests\Seller\StoreSellerRequest;
use App\Services\SellerService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SellerApiController
{
    protected $sellerService;

    public function __construct(SellerService $sellerService)
    {
        $this->sellerService = $sellerService;
    }

    /**
     * creating sellers API
     */
    public function createSeller(StoreSellerRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['verification_status'] = SellerVisibilityStatusEnum::Draft();
            $validated['visibility_status'] = SellerVerificationStatusEnum::NotApproved();
            $seller = $this->sellerService->createSeller(
                $validated,
                $request->allFiles()
            );

            return ApiResponseType::sendJsonResponse(
                true,
                'labels.seller_created_successfully',
                $seller,
                201
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed' . $e->getMessage(),
                data: $e->errors(),
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.seller_created_successfully',
                status: 500
            );
        }
    }
}
