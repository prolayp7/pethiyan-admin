<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\SettingTypeEnum;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Mail\LoginOtpMail;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\EmailService;
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
    protected array $emailConfig;
    protected bool  $smsEnabled;
    protected bool  $emailOtpEnabled;
    protected bool  $smsDemoMode;
    protected bool  $emailDemoMode;

    protected function respondWithFrontendAuthCookie(array $payload, string $token, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status)->cookie(FrontendAuthCookie::make($token));
    }

    public function __construct(
        protected SettingService $settingService,
        protected SmsService $smsService,
    ) {
        $smsSetting            = $this->settingService->getSettingByVariable(SettingTypeEnum::SMS());
        $emailSetting          = $this->settingService->getSettingByVariable(SettingTypeEnum::EMAIL());
        $this->smsConfig       = $smsSetting?->value ?? [];
        $this->emailConfig     = $emailSetting?->value ?? [];
        $this->smsEnabled      = (bool)($this->smsConfig['enabled'] ?? false);
        $this->emailOtpEnabled = (bool)($this->emailConfig['email_otp_enabled'] ?? ($this->smsConfig['email_enabled'] ?? false));
        $this->smsDemoMode     = $this->smsEnabled && (bool)($this->smsConfig['otp_demo_mode'] ?? false);
        $this->emailDemoMode   = $this->emailOtpEnabled && (bool)($this->emailConfig['email_demo_mode'] ?? false);
    }

    // -------------------------------------------------------------------------
    // Send OTP
    // -------------------------------------------------------------------------

    /**
     * Send OTP to mobile and/or email.
     *
     * At least one of mobile or email is required. OTP is sent via SMS if
     * mobile is provided and SMS OTP is enabled. OTP is sent via email if
     * email is provided and Email OTP is enabled.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mobile'       => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:6',
            'email'        => 'nullable|email|max:255',
        ]);

        $mobile      = isset($validated['mobile']) ? preg_replace('/\D/', '', $validated['mobile']) : null;
        $email       = $validated['email'] ?? null;
        $countryCode = $validated['country_code'] ?? '+91';

        if (empty($mobile) && empty($email)) {
            return ApiResponseType::sendJsonResponse(false, 'labels.mobile_or_email_required', []);
        }

        // Reject if no account matches the provided identifier
        if (!empty($mobile) && !User::where('mobile', $mobile)->exists()) {
            return ApiResponseType::sendJsonResponse(false, 'Mobile number not found. Please check and try again.', []);
        }
        if (!empty($email) && !User::where('email', $email)->exists()) {
            return ApiResponseType::sendJsonResponse(false, 'Email address not found. Please check and try again.', []);
        }

        // OTP always goes to email — resolve email from mobile when needed
        if (!empty($mobile) && empty($email)) {
            $user  = User::where('mobile', $mobile)->first();
            $email = $user?->email;
            if (empty($email)) {
                return ApiResponseType::sendJsonResponse(false, 'No email address is linked to this mobile number. Please contact support.', []);
            }
        }

        if (!$this->emailOtpEnabled && !$this->emailDemoMode) {
            return ApiResponseType::sendJsonResponse(false, 'labels.otp_channel_not_enabled', []);
        }

        $expiryMins = (int)($this->smsConfig['otp_expiry_minutes'] ?? 5);
        $otp        = $this->emailDemoMode ? '123456' : $this->generateOtp();

        if (!empty($mobile)) {
            OtpVerification::invalidatePrevious($mobile, $countryCode);
        }
        OtpVerification::invalidatePreviousByEmail($email);

        OtpVerification::create([
            'mobile'       => $mobile ?: '',
            'email'        => $email,
            'country_code' => $countryCode,
            'otp'          => Hash::make($otp),
            'expires_at'   => now()->addMinutes($expiryMins),
            'attempts'     => 0,
        ]);

        $emailOtpSent = false;

        if ($this->emailDemoMode) {
            Log::info('[OtpAuth] Email demo OTP generated.', ['email' => $email, 'otp' => $otp]);
            $emailOtpSent = true;
        } else {
            try {
                $user         = User::where('email', $email)->first();
                $name         = $user?->name ?? 'User';
                $emailOtpSent = app(EmailService::class)->send(
                    new LoginOtpMail($name, $otp, $expiryMins),
                    $email,
                    $name
                );
            } catch (\Throwable $th) {
                Log::error('[OtpAuth] Email OTP send failed: ' . $th->getMessage());
            }
        }

        if (!$emailOtpSent && !$this->emailDemoMode) {
            return ApiResponseType::sendJsonResponse(false, 'labels.otp_send_failed', []);
        }

        $responseData = [
            'expires_in_minutes' => $expiryMins,
            'sms_otp_sent'       => false,
            'email_otp_sent'     => true,
            'email_sent_to'      => $email,
        ];

        if ($this->emailDemoMode) {
            $responseData['demo_mode'] = true;
            $responseData['demo_otp']  = $otp;
        }

        return ApiResponseType::sendJsonResponse(true, 'labels.otp_sent_successfully', $responseData);
    }

    // -------------------------------------------------------------------------
    // Verify OTP
    // -------------------------------------------------------------------------

    /**
     * Verify OTP and authenticate.
     *
     * Looks up the OTP record by mobile (if provided) or email. Verifies the
     * OTP, then finds or creates the user and returns an auth token.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mobile'       => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:6',
            'email'        => 'nullable|email|max:255',
            'otp'          => 'required|string|size:6',
            'name'         => 'sometimes|string|max:255',
            'company_name' => 'sometimes|nullable|string|max:255',
        ]);

        $mobile      = isset($validated['mobile']) ? preg_replace('/\D/', '', $validated['mobile']) : null;
        $email       = $validated['email'] ?? null;
        $countryCode = $validated['country_code'] ?? '+91';

        if (empty($mobile) && empty($email)) {
            return ApiResponseType::sendJsonResponse(false, 'labels.mobile_or_email_required', []);
        }

        // Prefer mobile lookup; fall back to email
        $record = null;
        if (!empty($mobile)) {
            $record = OtpVerification::findLatest($mobile, $countryCode);
        }
        if (!$record && !empty($email)) {
            $record = OtpVerification::findLatestByEmail($email);
        }

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

        $record->increment('attempts');

        if (!$record->checkOtp($validated['otp'])) {
            $remaining = $maxAttempts - $record->fresh()->attempts;
            return ApiResponseType::sendJsonResponse(false, 'labels.otp_invalid', [
                'attempts_remaining' => max(0, $remaining),
            ]);
        }

        $record->update(['verified_at' => now()]);

        // Find user by mobile first, then email
        $user  = null;
        $isNew = false;

        if (!empty($mobile)) {
            $user = User::where('mobile', $mobile)->first();
        }
        if (!$user && !empty($email)) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            $isNew = true;
            $user  = User::create([
                'name'         => $validated['name'] ?? ('User ' . Str::upper(Str::random(5))),
                'email'        => $email ?? null,
                'mobile'       => $mobile ?: null,
                'company_name' => $validated['company_name'] ?? null,
                'password'     => Hash::make(Str::random(24)),
            ]);

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

        $verificationUpdates = [];
        if (!empty($mobile) && $user->mobile === $mobile) {
            $verificationUpdates['mobile_verified_at'] = now();
        }
        if (!empty($email) && $user->email === $email) {
            $verificationUpdates['email_verified_at'] = now();
        }
        if (!empty($verificationUpdates)) {
            $user->update($verificationUpdates);
        }

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
     * Resend OTP to mobile and/or email.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mobile'       => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:6',
            'email'        => 'nullable|email|max:255',
        ]);

        $mobile      = isset($validated['mobile']) ? preg_replace('/\D/', '', $validated['mobile']) : null;
        $email       = $validated['email'] ?? null;
        $countryCode = $validated['country_code'] ?? '+91';

        if (empty($mobile) && empty($email)) {
            return ApiResponseType::sendJsonResponse(false, 'labels.mobile_or_email_required', []);
        }

        // OTP always goes to email — resolve email from mobile when needed
        if (!empty($mobile) && empty($email)) {
            $user  = User::where('mobile', $mobile)->first();
            $email = $user?->email;
            if (empty($email)) {
                return ApiResponseType::sendJsonResponse(false, 'No email address is linked to this mobile number. Please contact support.', []);
            }
        }

        if (!$this->emailOtpEnabled && !$this->emailDemoMode) {
            return ApiResponseType::sendJsonResponse(false, 'labels.otp_channel_not_enabled', []);
        }

        $expiryMins = (int)($this->smsConfig['otp_expiry_minutes'] ?? 5);
        $otp        = $this->emailDemoMode ? '123456' : $this->generateOtp();

        if (!empty($mobile)) {
            OtpVerification::invalidatePrevious($mobile, $countryCode);
        }
        OtpVerification::invalidatePreviousByEmail($email);

        OtpVerification::create([
            'mobile'       => $mobile ?: '',
            'email'        => $email,
            'country_code' => $countryCode,
            'otp'          => Hash::make($otp),
            'expires_at'   => now()->addMinutes($expiryMins),
            'attempts'     => 0,
        ]);

        $emailOtpSent = false;

        if ($this->emailDemoMode) {
            Log::info('[OtpAuth] Email demo OTP resent.', ['email' => $email, 'otp' => $otp]);
            $emailOtpSent = true;
        } else {
            try {
                $user         = User::where('email', $email)->first();
                $name         = $user?->name ?? 'User';
                $emailOtpSent = app(EmailService::class)->send(
                    new LoginOtpMail($name, $otp, $expiryMins),
                    $email,
                    $name
                );
            } catch (\Throwable $th) {
                Log::error('[OtpAuth] Email OTP resend failed: ' . $th->getMessage());
            }
        }

        $responseData = [
            'expires_in_minutes' => $expiryMins,
            'sms_otp_sent'       => false,
            'email_otp_sent'     => true,
            'email_sent_to'      => $email,
        ];

        if ($this->emailDemoMode) {
            $responseData['demo_mode'] = true;
            $responseData['demo_otp']  = $otp;
        }

        return ApiResponseType::sendJsonResponse(true, 'labels.otp_resent_successfully', $responseData);
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
