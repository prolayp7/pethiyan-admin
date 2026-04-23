<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\User\UserResource;
use App\Services\ProfileService;
use App\Services\SettingService;
use App\Enums\SettingTypeEnum;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

#[Group('Users')]
class UserApiController extends Controller
{
    protected ProfileService $profileService;
    protected SettingService $settingService;

    public function __construct(ProfileService $profileService, SettingService $settingService)
    {
        $this->profileService = $profileService;
        $this->settingService = $settingService;
    }

    private function isDemoModeEnabled(): bool
    {
        $resource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
        $settings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
        return (bool)($settings['demoMode'] ?? false);
    }
    /**
     * Update user profile
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'labels.user_not_authenticated',
                    []
                );
            }

            $validated = $request->validated();
            $updatedUser = $this->profileService->updateProfile($user, $validated, $request);

            return ApiResponseType::sendJsonResponse(
                true,
                'labels.profile_updated_successfully',
                new UserResource($updatedUser)
            );

        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.something_went_wrong',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get user profile
     *
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'labels.user_not_authenticated',
                    []
                );
            }

            return ApiResponseType::sendJsonResponse(
                true,
                'labels.profile_retrieved_successfully',
                new UserResource($user)
            );

        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.something_went_wrong',
                ['error' => $e->getMessage()]
            );
        }
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            if ($this->isDemoModeEnabled()) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.demo_mode_message_placeholder'),
                    [],
                    403
                );
            }

            $user = Auth::user();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'labels.user_not_authenticated',
                    []
                );
            }

            $user->password = Hash::make($request->input('password'));
            $user->save();

            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.password_updated_successfully'),
                []
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.password_update_failed', ['error' => $e->getMessage()]),
                []
            );
        }
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->delete();
            return response()->json([
                'success' => true,
                'message' => __('labels.account_deleted_successfully'),
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.account_deletion_failed', ['error' => $e->getMessage()]),
                'data' => []
            ], 500);
        }
    }
}
