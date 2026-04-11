<?php

namespace App\Services;

use App\Enums\SettingTypeEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected array $config;

    public function __construct(SettingService $settingService)
    {
        $setting = $settingService->getSettingByVariable(SettingTypeEnum::SMS());
        $this->config = $setting?->value ?? [];
    }

    /**
     * Send a plain transactional SMS message (non-OTP).
     *
     * @param  string  $mobile       Digits only, without country code prefix
     * @param  string  $countryCode  E.164 prefix, e.g. "+91"
     * @param  string  $message      SMS body text
     * @return bool
     */
    public function sendMessage(string $mobile, string $countryCode, string $message): bool
    {
        if (empty($this->config['enabled'])) {
            Log::info('[SmsService] SMS disabled — skipping transactional message.');
            return false;
        }

        $gateway = $this->config['gateway'] ?? 'msg91';

        return match ($gateway) {
            'msg91'  => $this->sendTransactionalViaMSG91($mobile, $countryCode, $message),
            'twilio' => $this->sendViaTwilio($mobile, $countryCode, $message),
            default  => $this->logFallback($mobile, $countryCode, $message),
        };
    }

    /**
     * Send an OTP SMS to the given mobile number.
     *
     * @param  string  $mobile       Digits only, without country code prefix
     * @param  string  $countryCode  E.164 prefix, e.g. "+91"
     * @param  string  $otp          Plain-text OTP to send
     * @return bool
     */
    public function sendOtp(string $mobile, string $countryCode, string $otp): bool
    {
        $gateway = $this->config['gateway'] ?? 'msg91';

        return match ($gateway) {
            'msg91'   => $this->sendViaMSG91($mobile, $countryCode, $otp),
            'twilio'  => $this->sendViaTwilio($mobile, $countryCode, $otp),
            default   => $this->logFallback($mobile, $countryCode, $otp),
        };
    }

    // -------------------------------------------------------------------------
    // MSG91 — Transactional (non-OTP)
    // -------------------------------------------------------------------------

    protected function sendTransactionalViaMSG91(string $mobile, string $countryCode, string $message): bool
    {
        $authKey  = $this->config['msg91_auth_key']  ?? '';
        $senderId = $this->config['msg91_sender_id'] ?? 'LCOMRC';

        if (empty($authKey)) {
            Log::warning('[SmsService] MSG91 auth key not configured for transactional SMS.');
            return false;
        }

        $dialCode = ltrim($countryCode, '+');
        $to       = $dialCode . $mobile;

        try {
            $response = Http::withHeaders([
                'authkey'      => $authKey,
                'content-type' => 'application/json',
            ])->post('https://api.msg91.com/api/v2/sendsms', [
                'sender'  => $senderId,
                'route'   => '4', // transactional route
                'country' => '91',
                'sms'     => [['message' => $message, 'to' => [$to]]],
            ]);

            if (!$response->successful()) {
                Log::error('[SmsService] MSG91 transactional error', ['status' => $response->status(), 'body' => $response->body()]);
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('[SmsService] MSG91 transactional exception: ' . $e->getMessage());
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // MSG91 — OTP
    // -------------------------------------------------------------------------

    protected function sendViaMSG91(string $mobile, string $countryCode, string $otp): bool
    {
        $authKey    = $this->config['msg91_auth_key']    ?? '';
        $templateId = $this->config['msg91_template_id'] ?? '';
        $senderId   = $this->config['msg91_sender_id']   ?? 'LCOMRC';

        if (empty($authKey) || empty($templateId)) {
            Log::warning('[SmsService] MSG91 auth key or template ID not configured.');
            return false;
        }

        // Strip leading '+' for MSG91
        $dialCode = ltrim($countryCode, '+');
        $to       = $dialCode . $mobile;

        try {
            $response = Http::withHeaders([
                'authkey'      => $authKey,
                'content-type' => 'application/json',
            ])->post('https://api.msg91.com/api/v5/otp', [
                'template_id' => $templateId,
                'mobile'      => $to,
                'authkey'     => $authKey,
                'otp'         => $otp,
                'sender'      => $senderId,
            ]);

            if (!$response->successful()) {
                Log::error('[SmsService] MSG91 error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            $json = $response->json();
            return ($json['type'] ?? '') === 'success';

        } catch (\Throwable $e) {
            Log::error('[SmsService] MSG91 exception: ' . $e->getMessage());
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // Twilio
    // -------------------------------------------------------------------------

    protected function sendViaTwilio(string $mobile, string $countryCode, string $otp): bool
    {
        $accountSid = $this->config['twilio_account_sid'] ?? '';
        $authToken  = $this->config['twilio_auth_token']  ?? '';
        $from       = $this->config['twilio_from_number'] ?? '';

        if (empty($accountSid) || empty($authToken) || empty($from)) {
            Log::warning('[SmsService] Twilio credentials not configured.');
            return false;
        }

        $to   = $countryCode . $mobile;
        $body = "Your OTP is {$otp}. Valid for 10 minutes. Do not share with anyone.";

        try {
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                    'From' => $from,
                    'To'   => $to,
                    'Body' => $body,
                ]);

            if (!$response->successful()) {
                Log::error('[SmsService] Twilio error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return in_array($response->json('status'), ['queued', 'sent', 'delivered']);

        } catch (\Throwable $e) {
            Log::error('[SmsService] Twilio exception: ' . $e->getMessage());
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // Fallback — log only (useful in local/staging)
    // -------------------------------------------------------------------------

    protected function logFallback(string $mobile, string $countryCode, string $otp): bool
    {
        Log::info("[SmsService] [FALLBACK] OTP for {$countryCode}{$mobile}: {$otp}");
        return true;
    }
}
