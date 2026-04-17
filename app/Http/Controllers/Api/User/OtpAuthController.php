<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\SettingTypeEnum;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\SettingService;
use App\Services\SmsService;
use App\Services\WalletService;
use App\Support\FrontendAuthCookie;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

#[Group('Auth')]
class OtpAuthController extends Controller
{
    protected array $smsConfig;
    protected bool  $demoMode;

    protected function respondWithFrontendAuthCookie(array $payload, string $token, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status)->cookie(FrontendAuthCookie::make($token));
    }

    public function __construct(
        protected SettingService $settingService,
        protected SmsService $smsService,
    ) {
        $smsSetting      = $this->settingService->getSettingByVariable(SettingTypeEnum::SMS());
        $this->smsConfig = $smsSetting?->value ?? [];
        $this->demoMode  = (bool)($this->smsConfig['otp_demo_mode'] ?? false);
    }

    // -------------------------------------------------------------------------
    // Send OTP
    // -------------------------------------------------------------------------

    /**
     * Send OTP to mobile number
     *
     * Generates and sends a 6-digit OTP to the provided mobile number via SMS.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mobile'       => 'required|string|max:20',
            'country_code' => 'required|string|max:6',
        ]);

        $mobile      = preg_replace('/\D/', '', $validated['mobile']);
        $countryCode = $validated['country_code'];
        $expiryMins  = (int)($this->smsConfig['otp_expiry_minutes'] ?? 10);

        // Demo mode: use fixed OTP 123456, skip SMS gateway entirely
        if ($this->demoMode) {
            OtpVerification::invalidatePrevious($mobile, $countryCode);
            OtpVerification::create([
                'mobile'       => $mobile,
                'country_code' => $countryCode,
                'otp'          => Hash::make('123456'),
                'expires_at'   => now()->addMinutes($expiryMins),
                'attempts'     => 0,
            ]);
            return ApiResponseType::sendJsonResponse(true, 'labels.otp_sent_successfully', [
                'expires_in_minutes' => $expiryMins,
                'demo_mode'          => true,
                'demo_otp'           => '123456',
            ]);
        }

        if (empty($this->smsConfig['enabled'])) {
            return ApiResponseType::sendJsonResponse(false, 'labels.sms_otp_not_enabled', []);
        }

        // Invalidate previous OTPs
        OtpVerification::invalidatePrevious($mobile, $countryCode);

        $otp    = $this->generateOtp();
        $record = OtpVerification::create([
            'mobile'       => $mobile,
            'country_code' => $countryCode,
            'otp'          => Hash::make($otp),
            'expires_at'   => now()->addMinutes($expiryMins),
            'attempts'     => 0,
        ]);

        $sent = $this->smsService->sendOtp($mobile, $countryCode, $otp);

        if (!$sent) {
            $record->delete();
            return ApiResponseType::sendJsonResponse(false, 'labels.sms_send_failed', []);
        }

        return ApiResponseType::sendJsonResponse(true, 'labels.otp_sent_successfully', [
            'expires_in_minutes' => $expiryMins,
        ]);
    }

    // -------------------------------------------------------------------------
    // Verify OTP
    // -------------------------------------------------------------------------

    /**
     * Verify OTP and authenticate
     *
     * Verifies the OTP and returns a Sanctum token. Creates a new user account
     * if the mobile number is not registered.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mobile'       => 'required|string|max:20',
            'country_code' => 'required|string|max:6',
            'otp'          => 'required|string|size:' . ($this->demoMode ? 6 : ($this->smsConfig['otp_length'] ?? 6)),
            // Optional fields for new-user registration
            'name'         => 'sometimes|string|max:255',
            'company_name' => 'sometimes|nullable|string|max:255',
            'email'        => 'sometimes|email|max:255',
        ]);

        $mobile      = preg_replace('/\D/', '', $validated['mobile']);
        $countryCode = $validated['country_code'];

        $record = OtpVerification::findLatest($mobile, $countryCode);

        if (!$record) {
            return ApiResponseType::sendJsonResponse(false, 'labels.otp_expired_or_not_found', []);
        }

        if ($record->isExpired()) {
            return ApiResponseType::sendJsonResponse(false, 'labels.otp_expired', []);
        }

        $maxAttempts = 5;
        if ($record->hasExceededAttempts($maxAttempts)) {
            return ApiResponseType::sendJsonResponse(false, 'labels.otp_max_attempts_exceeded', []);
        }

        // Increment attempt counter before checking
        $record->increment('attempts');

        if (!$record->checkOtp($validated['otp'])) {
            $remaining = $maxAttempts - $record->fresh()->attempts;
            return ApiResponseType::sendJsonResponse(false, 'labels.otp_invalid', [
                'attempts_remaining' => max(0, $remaining),
            ]);
        }

        // Mark as verified
        $record->update(['verified_at' => now()]);

        // Find or create user
        $user    = User::where('mobile', $mobile)->first();
        $isNew   = false;

        if (!$user) {
            $isNew = true;
            $user  = User::create([
                'name' => $validated['name'] ?? ('User ' . Str::upper(Str::random(5))),
                'email' => $validated['email'] ?? null,
                'mobile' => $mobile,
                'company_name' => $validated['company_name'] ?? null,
                'password' => Hash::make(Str::random(24)), // random unusable password
            ]);

            // Welcome wallet credit
            try {
                $systemSettings = $this->settingService
                    ->getSettingByVariable(SettingTypeEnum::SYSTEM())
                    ?->value ?? [];
                $welcomeAmount = (float)($systemSettings['welcomeWalletBalanceAmount'] ?? 0);
                if ($welcomeAmount > 0) {
                    app(WalletService::class)->addBalance($user->id, [
                        'amount'         => $welcomeAmount,
                        'payment_method' => 'system',
                        'description'    => __('labels.welcome_wallet_bonus') ?? 'Welcome bonus',
                    ]);
                }
            } catch (\Throwable $th) {
                Log::error('[OtpAuth] Welcome wallet credit failed for user ' . $user->id . ': ' . $th->getMessage());
            }

            event(new UserRegistered($user));
        } else {
            event(new UserLoggedIn($user));
        }

        $verificationUpdates = [
            'mobile_verified_at' => now(),
        ];

        if (!empty($user->email) && is_null($user->email_verified_at)) {
            $verificationUpdates['email_verified_at'] = now();
        }

        $user->update($verificationUpdates);

        $token = $user->createToken('otp-auth')->plainTextToken;

        return $this->respondWithFrontendAuthCookie(ApiResponseType::toArray(
            true,
            __($isNew ? 'labels.registration_successful' : 'labels.login_successful'),
            [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'is_new_user'  => $isNew,
            'user'         => new UserResource($user->fresh()),
            ]
        ), $token);
    }

    // -------------------------------------------------------------------------
    // Resend OTP
    // -------------------------------------------------------------------------

    /**
     * Resend OTP
     *
     * Invalidates the previous OTP and sends a fresh one. Rate-limited to
     * prevent abuse (3 resends per 10 minutes via route-level throttle).
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mobile'       => 'required|string|max:20',
            'country_code' => 'required|string|max:6',
        ]);

        $mobile      = preg_replace('/\D/', '', $validated['mobile']);
        $countryCode = $validated['country_code'];
        $expiryMins  = (int)($this->smsConfig['otp_expiry_minutes'] ?? 10);

        // Demo mode: use fixed OTP 123456, skip SMS gateway entirely
        if ($this->demoMode) {
            OtpVerification::invalidatePrevious($mobile, $countryCode);
            OtpVerification::create([
                'mobile'       => $mobile,
                'country_code' => $countryCode,
                'otp'          => Hash::make('123456'),
                'expires_at'   => now()->addMinutes($expiryMins),
                'attempts'     => 0,
            ]);
            return ApiResponseType::sendJsonResponse(true, 'labels.otp_resent_successfully', [
                'expires_in_minutes' => $expiryMins,
                'demo_mode'          => true,
                'demo_otp'           => '123456',
            ]);
        }

        if (empty($this->smsConfig['enabled'])) {
            return ApiResponseType::sendJsonResponse(false, 'labels.sms_otp_not_enabled', []);
        }

        OtpVerification::invalidatePrevious($mobile, $countryCode);

        $otp    = $this->generateOtp();
        $record = OtpVerification::create([
            'mobile'       => $mobile,
            'country_code' => $countryCode,
            'otp'          => Hash::make($otp),
            'expires_at'   => now()->addMinutes($expiryMins),
            'attempts'     => 0,
        ]);

        $sent = $this->smsService->sendOtp($mobile, $countryCode, $otp);

        if (!$sent) {
            $record->delete();
            return ApiResponseType::sendJsonResponse(false, 'labels.sms_send_failed', []);
        }

        return ApiResponseType::sendJsonResponse(true, 'labels.otp_resent_successfully', [
            'expires_in_minutes' => $expiryMins,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function generateOtp(): string
    {
        $length = (int)($this->smsConfig['otp_length'] ?? 6);
        $max    = (int)str_pad('9', $length, '9');
        $min    = (int)str_pad('1', $length, '0');
        return str_pad((string)random_int($min, $max), $length, '0', STR_PAD_LEFT);
    }
}
