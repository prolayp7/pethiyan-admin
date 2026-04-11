<?php

namespace App\Traits;

use App\Enums\Seller\SellerVerificationStatusEnum;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Http\Resources\User\UserResource;
use App\Models\AdminUser;
use App\Models\OtpVerification;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\SmsService;
use App\Services\TotpService;
use App\Types\Api\ApiResponseType;
use App\Services\SettingService;
use App\Enums\SettingTypeEnum;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait AuthTrait
{
    private function resolveSessionGuard(): string
    {
        if (property_exists($this, 'role')) {
            if ($this->role === 'admin') {
                return 'admin';
            }
            if ($this->role === 'seller') {
                return 'seller';
            }
        }

        return config('auth.defaults.guard', 'web');
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $guard = $this->resolveSessionGuard();
            $isAdminLogin = property_exists($this, 'role') && $this->role === 'admin' && $guard === 'admin';

            if ($isAdminLogin && $request->filled('challenge_token')) {
                return $this->verifyAdminTotpChallenge($request);
            }

            // Validate either email or mobile with password
            $validated = $request->validate([
                'email' => 'required_without:mobile|email',
                'mobile' => 'required_without:email|numeric',
                'password' => 'required',
            ]);

            // Build credentials based on provided identifier
            $identifierField = $request->filled('email') ? 'email' : 'mobile';
            $identifierValue = $request->input($identifierField);
            $credentials = [
                $identifierField => $identifierValue,
                'password' => $validated['password'],
            ];

            // Optional role-based access check (admin/seller), if the controller sets $role
            $userModelClass = User::class;
            if (property_exists($this, 'role')) {
                $role = $this->role;
                if ($role === 'admin') {
                    $userModelClass = AdminUser::class;
                } elseif ($role === 'seller') {
                    $userModelClass = User::class;
                }
            }

            $userForRoleCheck = $userModelClass::where($identifierField, $identifierValue)->first();

            if (!$userForRoleCheck) {
                return response()->json([
                    'success' => false,
                    'message' => __('labels.invalid_credentials'),
                    'data' => []
                ]);
            }

            if (property_exists($this, 'role')) {
                $role = $this->role;
                if ($role === 'seller') {
                    if (!empty($userForRoleCheck->access_panel?->value) && $userForRoleCheck->access_panel->value !== 'seller') {
                        return response()->json([
                            'success' => false,
                            'message' => __('labels.invalid_credentials'),
                            'data' => []
                        ]);
                    }

                    // Also validate seller linkage and verification status during login
                    $seller = $userForRoleCheck->seller();

                    if (!$seller) {
                        return response()->json([
                            'success' => false,
                            'message' => __('labels.not_a_seller') ?? 'Not a seller account.',
                            'data' => []
                        ], 403);
                    }

                    if ($seller->verification_status !== SellerVerificationStatusEnum::Approved()) {
                        return response()->json([
                            'success' => false,
                            'message' => __('labels.account_not_verified') ?? 'Your seller account is not approved yet.',
                            'data' => [
                                'verification_status' => $seller->verification_status,
                            ]
                        ], 403);
                    }
                }
            }

            if ($isAdminLogin) {
                if (!Hash::check($validated['password'], $userForRoleCheck->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('labels.invalid_credentials'),
                        'data' => []
                    ]);
                }

                if ($userForRoleCheck instanceof AdminUser && $userForRoleCheck->isTotpEnabled()) {
                    $token = Str::random(64);
                    $ttlSeconds = 300; // 5 minutes
                    Cache::put(
                        "admin_totp_login_challenge:{$token}",
                        [
                            'admin_user_id' => $userForRoleCheck->id,
                            'attempts' => 0,
                            'expires_at' => now()->addSeconds($ttlSeconds)->timestamp,
                        ],
                        now()->addSeconds($ttlSeconds)
                    );

                    return response()->json([
                        'success' => true,
                        'requires_totp' => true,
                        'challenge_token' => $token,
                        'message' => 'Enter the 6-digit code from Google Authenticator.',
                        'data' => []
                    ]);
                }

                FacadesAuth::guard($guard)->login($userForRoleCheck);
            } elseif (!FacadesAuth::guard($guard)->attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => __('labels.invalid_credentials'),
                    'data' => []
                ]);
            }

            // Prevent session fixation after successful authentication when a session exists
            // (API routes typically don't have session middleware enabled).
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
            $user = FacadesAuth::guard($guard)->user();

            // Verification gate: the identifier used to log in must be verified
            if ($identifierField === 'email' && is_null($user->email_verified_at)) {
                FacadesAuth::guard($guard)->logout();
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
                return response()->json([
                    'success' => false,
                    'message' => __('labels.email_not_verified'),
                    'data' => ['verified' => false, 'type' => 'email'],
                ], 403);
            }

            if ($identifierField === 'mobile' && is_null($user->mobile_verified_at)) {
                FacadesAuth::guard($guard)->logout();
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
                return response()->json([
                    'success' => false,
                    'message' => __('labels.mobile_not_verified'),
                    'data' => ['verified' => false, 'type' => 'mobile'],
                ], 403);
            }
            try {
                if ($user instanceof User && !empty($request['fcm_token']) && !empty($request['device_type'])) {
                    UserFcmToken::updateOrCreate(
                        [
                            'fcm_token' => $request['fcm_token'],
                        ],
                        [
                            'user_id' => $user->id,
                            'device_type' => $request['device_type'],
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error('Error updating or creating FCM token: ' . $e->getMessage());
            }
            $token = $user->createToken($user->email ?? ($user->mobile ?? 'api-token'))->plainTextToken;
            event(new UserLoggedIn($user));
            return response()->json([
                'success' => true,
                'message' => __('labels.login_successful'),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'data' => new UserResource($user)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.validation_error') . ":- " . $e->getMessage(),
                'data' => []
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.login_failed', ['error' => $e->getMessage()]),
                'data' => []
            ], 500);
        }
    }

    private function verifyAdminTotpChallenge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_token' => 'required|string',
            'totp_code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        $challengeKey = 'admin_totp_login_challenge:' . $validated['challenge_token'];
        $challenge = Cache::get($challengeKey);

        if (!is_array($challenge) || empty($challenge['admin_user_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'TOTP challenge expired. Please login again.',
                'data' => []
            ], 422);
        }

        $admin = AdminUser::query()->find($challenge['admin_user_id']);
        if (!$admin || !$admin->isTotpEnabled()) {
            Cache::forget($challengeKey);
            return response()->json([
                'success' => false,
                'message' => 'TOTP challenge is no longer valid. Please login again.',
                'data' => []
            ], 422);
        }

        $providedTotp = trim((string) ($validated['totp_code'] ?? ''));
        $providedRecovery = strtoupper(trim((string) ($validated['recovery_code'] ?? '')));
        if ($providedTotp === '' && $providedRecovery === '') {
            return response()->json([
                'success' => false,
                'message' => 'Provide either an authenticator code or a recovery code.',
                'data' => []
            ], 422);
        }

        $totpService = app(TotpService::class);
        $isValid = false;
        $usedRecovery = false;

        if ($providedTotp !== '' && $totpService->verifyCode($admin->totp_secret, $providedTotp)) {
            $isValid = true;
        }

        if (!$isValid && $providedRecovery !== '') {
            $recoveryCodes = is_array($admin->totp_recovery_codes) ? $admin->totp_recovery_codes : [];
            $index = array_search($providedRecovery, $recoveryCodes, true);
            if ($index !== false) {
                unset($recoveryCodes[$index]);
                $admin->totp_recovery_codes = array_values($recoveryCodes);
                $usedRecovery = true;
                $isValid = true;
            }
        }

        if (!$isValid) {
            $attempts = (int) ($challenge['attempts'] ?? 0) + 1;
            if ($attempts >= 5) {
                Cache::forget($challengeKey);
                return response()->json([
                    'success' => false,
                    'message' => 'Too many invalid OTP attempts. Please login again.',
                    'data' => []
                ], 429);
            }

            $expiresAt = (int) ($challenge['expires_at'] ?? now()->addMinutes(5)->timestamp);
            $secondsLeft = max(1, $expiresAt - now()->timestamp);
            $challenge['attempts'] = $attempts;
            Cache::put($challengeKey, $challenge, now()->addSeconds($secondsLeft));

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP/recovery code.',
                'data' => []
            ], 422);
        }

        if ($usedRecovery) {
            $admin->save();
        }

        Cache::forget($challengeKey);

        FacadesAuth::guard('admin')->login($admin);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $token = $admin->createToken($admin->email ?? ('admin-token-' . $admin->id))->plainTextToken;
        event(new UserLoggedIn($admin));

        return response()->json([
            'success' => true,
            'message' => __('labels.login_successful'),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => new UserResource($admin)
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users',
                'mobile' => 'required|unique:users|numeric',
                'password' => 'required|string|min:6|confirmed',
                'country' => 'nullable|string|max:255',
                'iso_2' => 'nullable|string|max:2',
                'country_code' => 'nullable|string|max:6',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
                'country' => $validated['country'] ?? null,
                'iso_2' => $validated['iso_2'] ?? null,
                'password' => Hash::make($validated['password']),
                // TODO: Remove auto-verification once email integration is added.
                // Replace with: send a verification link to the email address.
                'email_verified_at' => now(),
            ]);

            // Grant welcome wallet balance if enabled in system settings
            try {
                $settingService = app(SettingService::class);
                $systemSettingsResource = $settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
                $systemSettings = $systemSettingsResource?->toArray(request())['value'] ?? [];
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

            // Generate and send mobile OTP
            $otpSent = false;
            try {
                $countryCode = $validated['country_code'] ?? '+91';
                // $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp = '123456';

                OtpVerification::invalidatePrevious($validated['mobile'], $countryCode);
                OtpVerification::create([
                    'mobile'       => $validated['mobile'],
                    'country_code' => $countryCode,
                    'otp'          => Hash::make($otp),
                    'expires_at'   => now()->addMinutes(10),
                ]);

                // TODO: Replace with real SMS gateway once available.
                // SmsService will log the OTP when no gateway is configured (dummy mode).
                $smsService = app(SmsService::class);
                $smsService->sendOtp($validated['mobile'], $countryCode, $otp);
                $otpSent = true;
            } catch (\Throwable $th) {
                Log::error('Mobile OTP generation failed for user ' . $user->id . ': ' . $th->getMessage());
            }

            event(new UserRegistered($user));

            return response()->json([
                'success' => true,
                'message' => __('labels.registration_successful'),
                'access_token' => $user->createToken($validated['email'])->plainTextToken,
                'token_type' => 'Bearer',
                'data' => [
                    'user' => new UserResource($user),
                    'mobile_verified' => false,
                    'otp_sent' => $otpSent,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.validation_error') . ":- " . $e->getMessage(),
                'data' => []
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.registration_failed', ['error' => $e->getMessage()]),
                'data' => []
            ], 500);
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate(['email' => 'required|email']);

            $status = Password::sendResetLink($request->only('email'));
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => __($status),
                    'data' => []
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __($status),
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.password_reset_failed', ['error' => $e->getMessage()]),
                'data' => []
            ], 500);
        }
    }

    /**
     * Logout user
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.logout_successful',
                data: []
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.logout_failed',
            );
        }
    }
}
