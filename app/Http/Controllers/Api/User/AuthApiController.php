<?php

namespace App\Http\Controllers\Api\User;

use App\Mail\RegistrationOtpMail;
use App\Enums\SettingTypeEnum;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\VerifyUserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\EmailService;
use App\Services\SettingService;
use App\Services\SmsService;
use App\Traits\AuthTrait;
use App\Types\Api\ApiResponseType;
use App\Support\FrontendAuthCookie;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Factory;
use App\Models\OtpVerification;
use App\Services\WalletService;

#[Group('Auth')]
class AuthApiController extends Controller
{
    use AuthTrait;

    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    protected function respondWithFrontendAuthCookie(array $payload, string $token, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status)->cookie(FrontendAuthCookie::make($token));
    }

    /**
     * Verify mobile OTP after registration and mark mobile as verified.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyMobile(Request $request): JsonResponse
    {
        try {
            $smsSettings = $this->settingService->getSettingByVariable(SettingTypeEnum::SMS())?->value ?? [];
            $otpLength = (int)($smsSettings['otp_length'] ?? 6);
            $validated = $request->validate([
                'mobile'       => 'required|string|max:20',
                'otp'          => 'required|string|size:' . $otpLength,
                'country_code' => 'nullable|string|max:6',
            ]);

            $countryCode = $validated['country_code'] ?? '+91';
            $mobile      = $validated['mobile'];

            $user = User::where('mobile', $mobile)->first();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(false, 'labels.user_not_found', []);
            }

            if (!is_null($user->mobile_verified_at)) {
                return ApiResponseType::sendJsonResponse(true, 'labels.mobile_already_verified', []);
            }

            $otpRecord = OtpVerification::findLatest($mobile, $countryCode);
            if (!$otpRecord) {
                return ApiResponseType::sendJsonResponse(false, 'labels.otp_expired_or_not_found', []);
            }

            if ($otpRecord->isExpired()) {
                return ApiResponseType::sendJsonResponse(false, 'labels.otp_expired', []);
            }

            if ($otpRecord->hasExceededAttempts()) {
                return ApiResponseType::sendJsonResponse(false, 'labels.otp_max_attempts_exceeded', []);
            }

            // Increment attempts before checking so a wrong guess is counted
            $otpRecord->increment('attempts');

            if (!$otpRecord->checkOtp($validated['otp'])) {
                return ApiResponseType::sendJsonResponse(false, 'labels.otp_invalid', []);
            }

            $otpRecord->update(['verified_at' => now()]);

            $user->mobile_verified_at = now();
            if (!empty($user->email) && is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
            }
            $user->save();

            return ApiResponseType::sendJsonResponse(true, 'labels.mobile_verified_successfully', [
                'user' => new UserResource($user->fresh()),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.validation_error') . ":- " . $e->getMessage(),
                'data'    => [],
            ], 422);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.something_went_wrong', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Verify if user exists by email or mobile
     *
     * @param VerifyUserRequest $request
     * @return JsonResponse
     */
    public function verifyUser(VerifyUserRequest $request): JsonResponse
    {
        try {
            $type = $request->input('type');
            $value = $request->input('value');

            $user = null;

            if ($type === 'email') {
                $user = User::where('email', $value)->first();
            } elseif ($type === 'mobile') {
                $user = User::where('mobile', $value)->first();
            }

            $exists = !is_null($user);

            $responseData = [
                'exists' => $exists,
                'type' => $type,
                'value' => $value
            ];

            if ($exists) {
                return ApiResponseType::sendJsonResponse(
                    true,
                    'labels.user_found',
                    $responseData
                );
            } else {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'labels.user_not_found',
                    $responseData
                );
            }

        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.something_went_wrong',
                ['error' => $e->getMessage()]
            );
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'         => 'required|string|max:255',
                'email'        => 'required|string|email|max:255',
                'mobile'       => 'required|numeric',
                'company_name' => 'nullable|string|max:255',
                'password'     => 'required|string|min:6|confirmed',
                'country'      => 'nullable|string|max:255',
                'iso_2'        => 'nullable|string|max:2',
                'country_code' => 'nullable|string|max:6',
            ]);

            $isNewUser = false;
            $existingUser = User::where('email', $validated['email'])->first();

            if ($existingUser) {
                // Already fully verified — tell them to sign in instead
                if (!is_null($existingUser->email_verified_at)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('labels.email_already_registered'),
                        'data'    => [],
                    ], 422);
                }
                // Unverified account exists — update credentials and resend OTP
                // If mobile changed, ensure it isn't taken by a different account
                if ($validated['mobile'] != $existingUser->mobile) {
                    if (User::where('mobile', $validated['mobile'])->where('id', '!=', $existingUser->id)->exists()) {
                        return response()->json([
                            'success' => false,
                            'message' => __('labels.mobile_already_registered'),
                            'data'    => [],
                        ], 422);
                    }
                }
                $existingUser->name     = $validated['name'];
                $existingUser->mobile   = $validated['mobile']; // keep in sync so verifyMobile lookup works
                $existingUser->password = Hash::make($validated['password']);
                $existingUser->save();
                $user = $existingUser;
                $successMessage = __('labels.otp_resent_to_unverified');
            } else {
                // Brand-new registration — check mobile uniqueness here
                if (User::where('mobile', $validated['mobile'])->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('labels.mobile_already_registered'),
                        'data'    => [],
                    ], 422);
                }

                $user = User::create([
                    'name'         => $validated['name'],
                    'email'        => $validated['email'],
                    'mobile'       => $validated['mobile'],
                    'company_name' => $validated['company_name'] ?? null,
                    'country'      => $validated['country'] ?? null,
                    'iso_2'        => $validated['iso_2'] ?? null,
                    'password'     => Hash::make($validated['password']),
                    // email_verified_at intentionally null — set after OTP verification
                ]);

                $isNewUser = true;
                $successMessage = __('labels.registration_successful');

                try {
                    $systemSettingsResource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
                    $systemSettings = $systemSettingsResource?->toArray($request)['value'] ?? [];
                    $welcomeAmount = (float)($systemSettings['welcomeWalletBalanceAmount'] ?? 0);

                    if ($welcomeAmount > 0) {
                        $walletService = app(WalletService::class);
                        $walletService->addBalance($user->id, [
                            'amount'         => $welcomeAmount,
                            'payment_method' => 'system',
                            'description'    => __('labels.welcome_wallet_bonus') ?? 'Welcome bonus added to wallet',
                        ]);
                    }
                } catch (\Throwable $th) {
                    Log::error('Welcome wallet credit failed for user ' . $user->id . ': ' . $th->getMessage());
                }
            }

            $smsSettings      = $this->settingService->getSettingByVariable(SettingTypeEnum::SMS())?->value ?? [];
            $emailSettings    = $this->settingService->getSettingByVariable(SettingTypeEnum::EMAIL())?->value ?? [];
            $smsEnabled       = (bool)($smsSettings['enabled'] ?? false);
            $emailOtpEnabled  = (bool)($emailSettings['email_otp_enabled'] ?? ($smsSettings['email_enabled'] ?? false));
            $smsDemoMode      = $smsEnabled && (bool)($smsSettings['otp_demo_mode'] ?? false);
            $emailDemoMode    = $emailOtpEnabled && (bool)($emailSettings['email_demo_mode'] ?? false);
            $countryCode      = $validated['country_code'] ?? '+91';
            $expiryMinutes    = (int)($smsSettings['otp_expiry_minutes'] ?? 10);
            $otpLength        = (int)($smsSettings['otp_length'] ?? 6);
            $smsOtpSent       = false;
            $emailOtpSent     = false;

            if ($smsEnabled || $emailOtpEnabled) {
                $otp = $smsDemoMode ? '123456' : $this->generateRegistrationOtp($otpLength);

                OtpVerification::invalidatePrevious($user->mobile, $countryCode);
                OtpVerification::create([
                    'mobile'      => $user->mobile,
                    'country_code'=> $countryCode,
                    'otp'         => Hash::make($otp),
                    'expires_at'  => now()->addMinutes($expiryMinutes),
                    'attempts'    => 0,
                ]);

                if ($smsEnabled) {
                    try {
                        if ($smsDemoMode) {
                            Log::info('[RegisterOtp] SMS demo OTP generated.', [
                                'user_id'      => $user->id,
                                'mobile'       => $user->mobile,
                                'country_code' => $countryCode,
                                'otp'          => $otp,
                            ]);
                            $smsOtpSent = true;
                        } else {
                            $smsOtpSent = app(SmsService::class)->sendOtp($user->mobile, $countryCode, $otp);
                        }
                    } catch (\Throwable $th) {
                        Log::error('Mobile OTP send failed for user ' . $user->id . ': ' . $th->getMessage());
                    }
                }

                if ($emailOtpEnabled) {
                    try {
                        if ($emailDemoMode) {
                            Log::info('[RegisterOtp] Email demo OTP generated.', [
                                'user_id' => $user->id,
                                'email'   => $user->email,
                                'otp'     => $otp,
                            ]);
                            $emailOtpSent = true;
                        } else {
                            $emailOtpSent = app(EmailService::class)->send(
                                new RegistrationOtpMail($user->name, $otp, $expiryMinutes),
                                $user->email,
                                $user->name
                            );
                        }
                    } catch (\Throwable $th) {
                        Log::error('Email OTP send failed for user ' . $user->id . ': ' . $th->getMessage());
                    }
                }
            } else {
                // No OTP configured — auto-verify both channels immediately
                $user->update([
                    'mobile_verified_at' => now(),
                    'email_verified_at'  => now(),
                ]);
            }

            if ($isNewUser) {
                event(new UserRegistered($user));
            }

            $token = $user->createToken($user->email)->plainTextToken;

            return $this->respondWithFrontendAuthCookie([
                'success'      => true,
                'message'      => $successMessage,
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'data'         => [
                    'user'           => new UserResource($user->fresh()),
                    'mobile_verified'=> !is_null($user->fresh()->mobile_verified_at),
                    'otp_sent'       => $smsOtpSent || $emailOtpSent,
                    'sms_otp_sent'   => $smsOtpSent,
                    'email_otp_sent' => $emailOtpSent,
                ],
            ], $token);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.validation_error') . ':- ' . $e->getMessage(),
                'data'    => [],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.registration_failed', ['error' => $e->getMessage()]),
                'data'    => [],
            ], 500);
        }
    }

    private function getFirebaseAuth(): array
    {
        try {
            $authSetting = $this->settingService->getSettingByVariable(SettingTypeEnum::AUTHENTICATION());
            if (empty($authSetting)) {
                return [
                    'success' => false,
                    'message' => 'labels.setting_not_found',
                    'data' => []
                ];
            }
            $serviceAccount = storage_path('app/private/settings/service-account-file.json');

            $factory = (new Factory)->withServiceAccount($serviceAccount);
            return [
                'success' => true,
                'message' => 'labels.token_generated',
                'data' => $factory->createAuth()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'labels.something_went_wrong',
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Handle Firebase authentication callback
     */
    public function googleCallback(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'idToken' => 'required|string',
            ]);
            // Check if Google login is enabled in settings
            $authSetting = $this->settingService->getSettingByVariable(SettingTypeEnum::AUTHENTICATION());
            $authConfig = $authSetting?->value ?? [];
            if (empty($authConfig['googleLogin'])) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.google_login_not_enabled',
                    data: []
                );
            }
            $auth = $this->getFirebaseAuth();
            if ($auth['success'] === false) {
                return ApiResponseType::sendJsonResponse(
                    $auth['success'],
                    $auth['message'],
                    $auth['data']
                );
            }
            $auth = $auth['data'];
            // Verify the Firebase ID token
            $verifiedIdToken = $auth->verifyIdToken($request->idToken);
            $uid = $verifiedIdToken->claims()->get('sub');

            // Get user info from Firebase
            $firebaseUser = $auth->getUser($uid);
            $user = User::where('email', $firebaseUser->email)->first();
            if ($user) {
                $user->update([
                    'email_verified_at' => $firebaseUser->emailVerified ? now() : null,
                ]);
                $token = $user->createToken($firebaseUser->email)->plainTextToken;
                event(new UserLoggedIn($user));
                return $this->respondWithFrontendAuthCookie([
                    'success' => true,
                    'message' => __('labels.login_successful'),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'data' => new UserResource($user)
                ], $token);
            }
            if (!$request->has('mobile')) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.new_user',
                    data: [
                        'new_user' => true,
                        'name' => $firebaseUser->displayName,
                        'email' => $firebaseUser->email,
                    ]
                );
            }

            $validated = $request->validate([
                'mobile' => 'required|unique:users|numeric',
                'company_name' => 'nullable|string|max:255',
                'password' => 'required|string|min:6|confirmed',
                'country' => 'nullable|string|max:255',
                'iso_2' => 'nullable|string|max:2'
            ]);
            $user = User::create([
                'name' => $firebaseUser->displayName ?? $firebaseUser->email,
                'email' => $firebaseUser->email,
                'mobile' => $validated['mobile'],
                'company_name' => $validated['company_name'] ?? null,
                'country' => $validated['country'] ?? null,
                'iso_2' => $validated['iso_2'] ?? null,
                'password' => Hash::make($validated['password'])
            ]);
            // Grant welcome wallet balance if configured
            try {
                $systemSettingsResource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
                $systemSettings = $systemSettingsResource?->toArray($request)['value'] ?? [];
                $welcomeAmount = (float)($systemSettings['welcomeWalletBalanceAmount'] ?? 0);

                if ($welcomeAmount > 0) {
                    $walletService = app(WalletService::class);
                    $walletService->addBalance($user->id, [
                        'amount' => $welcomeAmount,
                        'payment_method' => 'system',
                        'description' => __('labels.welcome_wallet_bonus') ?? 'Welcome bonus added to wallet',
                    ]);
                }
            } catch (\Throwable $th) {
                Log::error('Welcome wallet credit failed for user ' . $user->id . ': ' . $th->getMessage());
            }
            event(new UserRegistered($user));
            $token = $user->createToken($firebaseUser->email)->plainTextToken;

            return $this->respondWithFrontendAuthCookie([
                'success' => true,
                'message' => __('labels.registration_successful'),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'data' => new UserResource($user)
            ], $token);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first() ?? $e->getMessage();

            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $firstError,
                data: ['errors' => $errors],
                status: 422
            );
        } catch (AuthenticationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.authentication_error',
                data: ['error' => $e->getMessage()],
            );
        } catch (FailedToVerifyToken $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.invalid_firebase_token',
                data: ['error' => $e->getMessage()],
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.something_went_wrong',
                data: ['error' => $e->getMessage()],
            );
        }
    }

    /**
     * Handle Firebase Apple authentication callback
     */
    public function appleCallback(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'idToken' => 'required|string',
            ]);

            // Check if Apple login is enabled in settings
            $authSetting = $this->settingService->getSettingByVariable(SettingTypeEnum::AUTHENTICATION());
            $authConfig = $authSetting?->value ?? [];
            if (empty($authConfig['appleLogin'])) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.apple_login_not_enabled',
                    data: []
                );
            }

            $auth = $this->getFirebaseAuth();
            if ($auth['success'] === false) {
                return ApiResponseType::sendJsonResponse(
                    $auth['success'],
                    $auth['message'],
                    $auth['data']
                );
            }
            $auth = $auth['data'];

            // Verify the Firebase ID token
            $verifiedIdToken = $auth->verifyIdToken($request->idToken);
            $uid = $verifiedIdToken->claims()->get('sub');

            // Get user info from Firebase
            $firebaseUser = $auth->getUser($uid);

            // Apple may not always return email; try to get it from claims
            $claims = $verifiedIdToken->claims()->all();
            $email = $firebaseUser->email ?? ($claims['email'] ?? null);
            $displayName = $firebaseUser->displayName ?? ($claims['name'] ?? null);

            if ($email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->update([
                        'email_verified_at' => ($firebaseUser->emailVerified ?? ($claims['email_verified'] ?? false)) ? now() : null,
                    ]);
                    $token = $user->createToken($email)->plainTextToken;
                    event(new UserLoggedIn($user));
                    return $this->respondWithFrontendAuthCookie([
                        'success' => true,
                        'message' => __('labels.login_successful'),
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'data' => new UserResource($user)
                    ], $token);
                }
            }

            // New user flow
            if (!$request->has('mobile')) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.new_user',
                    data: [
                        'new_user' => true,
                        'name' => $displayName,
                        'email' => $email,
                    ]
                );
            }

            // If email was not provided by Apple/Firebase, require it from client during registration
            $rules = [
                'mobile' => 'required|unique:users|numeric',
                'company_name' => 'nullable|string|max:255',
                'password' => 'required|string|min:6|confirmed',
                'country' => 'nullable|string|max:255',
                'iso_2' => 'nullable|string|max:2',
            ];
            if (!$email) {
                $rules['email'] = 'required|email|unique:users';
            }

            $validated = $request->validate($rules);
            $finalEmail = $email ?? $validated['email'];

            $user = User::create([
                'name' => $displayName ?? $finalEmail,
                'email' => $finalEmail,
                'mobile' => $validated['mobile'],
                'company_name' => $validated['company_name'] ?? null,
                'country' => $validated['country'] ?? null,
                'iso_2' => $validated['iso_2'] ?? null,
                'password' => Hash::make($validated['password'])
            ]);
            // Grant welcome wallet balance if configured
            try {
                $systemSettingsResource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
                $systemSettings = $systemSettingsResource?->toArray($request)['value'] ?? [];
                $welcomeAmount = (float)($systemSettings['welcomeWalletBalanceAmount'] ?? 0);

                if ($welcomeAmount > 0) {
                    $walletService = app(WalletService::class);
                    $walletService->addBalance($user->id, [
                        'amount' => $welcomeAmount,
                        'payment_method' => 'system',
                        'description' => __('labels.welcome_wallet_bonus') ?? 'Welcome bonus added to wallet',
                    ]);
                }
            } catch (\Throwable $th) {
                Log::error('Welcome wallet credit failed for user ' . $user->id . ': ' . $th->getMessage());
            }
            event(new UserRegistered($user));
            $token = $user->createToken($finalEmail)->plainTextToken;

            return $this->respondWithFrontendAuthCookie([
                'success' => true,
                'message' => __('labels.registration_successful'),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'data' => new UserResource($user)
            ], $token);
        } catch (AuthenticationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.authentication_error',
                data: ['error' => $e->getMessage()],
            );
        } catch (FailedToVerifyToken $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.invalid_firebase_token',
                data: ['error' => $e->getMessage()],
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.something_went_wrong',
                data: ['error' => $e->getMessage()],
            );
        }
    }

    private function generateRegistrationOtp(int $length): string
    {
        $normalizedLength = max(4, min(8, $length));
        $max = (int) str_repeat('9', $normalizedLength);
        $min = (int) ('1' . str_repeat('0', max(0, $normalizedLength - 1)));

        return str_pad((string) random_int($min, $max), $normalizedLength, '0', STR_PAD_LEFT);
    }
}
