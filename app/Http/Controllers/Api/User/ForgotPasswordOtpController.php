<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordOtpMail;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\EmailService;
use App\Services\SettingService;
use App\Services\SmsService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ForgotPasswordOtpController extends Controller
{
    protected array $smsConfig;
    protected array $emailConfig;
    protected bool  $smsEnabled;
    protected bool  $smsDemoMode;

    public function __construct(
        protected SettingService $settingService,
        protected SmsService $smsService,
    ) {
        $smsSetting        = $this->settingService->getSettingByVariable(SettingTypeEnum::SMS());
        $emailSetting      = $this->settingService->getSettingByVariable(SettingTypeEnum::EMAIL());
        $this->smsConfig   = $smsSetting?->value ?? [];
        $this->emailConfig = $emailSetting?->value ?? [];
        $this->smsEnabled  = (bool)($this->smsConfig['enabled'] ?? false);
        $this->smsDemoMode = $this->smsEnabled && (bool)($this->smsConfig['otp_demo_mode'] ?? false);
    }

    // -------------------------------------------------------------------------
    // Send OTP for password reset (only to registered users)
    // -------------------------------------------------------------------------

    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'  => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
        ]);

        $email       = $validated['email'] ?? null;
        $mobile      = isset($validated['mobile']) ? preg_replace('/\D/', '', $validated['mobile']) : null;
        $countryCode = '+91';

        if (empty($email) && empty($mobile)) {
            return ApiResponseType::sendJsonResponse(false, 'labels.email_or_mobile_required', []);
        }

        $expiryMins = (int)($this->smsConfig['otp_expiry_minutes'] ?? 5);
        $otp        = $this->smsDemoMode ? '123456' : $this->generateOtp();

        // Find user — silently ignore if not found (prevent enumeration)
        $user = null;
        if (!empty($mobile)) {
            $user = User::where('mobile', $mobile)->first();
        }
        if (!$user && !empty($email)) {
            $user = User::where('email', $email)->first();
        }

        $demoOtp = null;

        if ($user) {
            // Invalidate previous OTPs
            if (!empty($mobile)) {
                OtpVerification::invalidatePrevious($mobile, $countryCode);
            }
            if (!empty($email)) {
                OtpVerification::invalidatePreviousByEmail($email);
            }

            OtpVerification::create([
                'mobile'       => $mobile ?: '',
                'email'        => $email,
                'country_code' => $countryCode,
                'otp'          => Hash::make($otp),
                'expires_at'   => now()->addMinutes($expiryMins),
                'attempts'     => 0,
            ]);

            // Send via SMS
            if (!empty($mobile) && ($this->smsEnabled || $this->smsDemoMode)) {
                if ($this->smsDemoMode) {
                    $demoOtp = $otp;
                } else {
                    try {
                        $this->smsService->sendOtp($mobile, $countryCode, $otp);
                    } catch (\Throwable $th) {
                        Log::error('[ForgotPassword] SMS send failed: ' . $th->getMessage());
                    }
                }
            }

            // Send via email (always attempt for password reset, regardless of emailOtpEnabled)
            if (!empty($email)) {
                if ($this->smsDemoMode) {
                    $demoOtp = $otp;
                } else {
                    try {
                        app(EmailService::class)->send(
                            new ForgotPasswordOtpMail($user->name, $otp, $expiryMins),
                            $email,
                            $user->name
                        );
                    } catch (\Throwable $th) {
                        Log::error('[ForgotPassword] Email send failed: ' . $th->getMessage());
                    }
                }
            }
        }

        $responseData = ['expires_in_minutes' => $expiryMins];
        if ($demoOtp) {
            $responseData['demo_otp'] = $demoOtp;
        }

        // Always return success to prevent account enumeration
        return ApiResponseType::sendJsonResponse(true, 'labels.otp_sent_if_registered', $responseData);
    }

    // -------------------------------------------------------------------------
    // Verify OTP and reset password
    // -------------------------------------------------------------------------

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'                 => 'nullable|email|max:255',
            'mobile'                => 'nullable|string|max:20',
            'otp'                   => 'required|string|size:6',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $email       = $validated['email'] ?? null;
        $mobile      = isset($validated['mobile']) ? preg_replace('/\D/', '', $validated['mobile']) : null;
        $countryCode = '+91';

        if (empty($email) && empty($mobile)) {
            return ApiResponseType::sendJsonResponse(false, 'labels.email_or_mobile_required', []);
        }

        // Find OTP record
        $record = null;
        if (!empty($mobile)) {
            $record = OtpVerification::findLatest($mobile, $countryCode);
        }
        if (!$record && !empty($email)) {
            $record = OtpVerification::findLatestByEmail($email);
        }

        if (!$record || $record->isExpired()) {
            return ApiResponseType::sendJsonResponse(false, 'labels.otp_expired_or_not_found', []);
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

        // Find user
        $user = null;
        if (!empty($mobile)) {
            $user = User::where('mobile', $mobile)->first();
        }
        if (!$user && !empty($email)) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            return ApiResponseType::sendJsonResponse(false, 'labels.user_not_found', []);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return ApiResponseType::sendJsonResponse(true, 'labels.password_reset_successfully', []);
    }

    // -------------------------------------------------------------------------
    // Resend OTP
    // -------------------------------------------------------------------------

    public function resendOtp(Request $request): JsonResponse
    {
        return $this->sendOtp($request);
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
