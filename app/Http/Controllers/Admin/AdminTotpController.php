<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Services\SettingService;
use App\Services\TotpService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminTotpController extends Controller
{
    public function __construct(
        private readonly TotpService $totpService,
        private readonly SettingService $settingService
    )
    {
    }

    public function status(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        $enabled = method_exists($admin, 'isTotpEnabled') ? $admin->isTotpEnabled() : (!empty($admin?->totp_secret) && !empty($admin?->totp_enabled_at));

        return ApiResponseType::sendJsonResponse(true, 'TOTP status fetched successfully.', [
            'enabled' => $enabled,
            'enabled_at' => $admin?->totp_enabled_at,
        ]);
    }

    public function setup(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        $secret = $this->totpService->generateSecret();

        $request->session()->put('admin_totp_setup_secret', $secret);

        $issuer = $this->resolveIssuerName($request);
        $account = $admin->email ?: ('admin_' . $admin->id);
        $otpauth = $this->totpService->getProvisioningUri($issuer, $account, $secret);

        return ApiResponseType::sendJsonResponse(true, 'Scan the QR code in Google Authenticator and verify.', [
            'secret' => $secret,
            'otpauth_uri' => $otpauth,
            'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($otpauth),
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string',
            'totp_code' => 'required|string',
        ]);

        $admin = Auth::guard('admin')->user();

        if (!Hash::check($validated['password'], $admin->password)) {
            return ApiResponseType::sendJsonResponse(false, 'Invalid password.', [], 422);
        }

        $secret = $request->session()->get('admin_totp_setup_secret');
        if (empty($secret)) {
            return ApiResponseType::sendJsonResponse(false, 'TOTP setup session expired. Please restart setup.', [], 422);
        }

        if (!$this->totpService->verifyCode($secret, trim($validated['totp_code']))) {
            return ApiResponseType::sendJsonResponse(false, 'Invalid TOTP code.', [], 422);
        }

        $recoveryCodes = $this->totpService->generateRecoveryCodes();

        $admin->totp_secret = $secret;
        $admin->totp_enabled_at = now();
        $admin->totp_recovery_codes = $recoveryCodes;
        $admin->save();

        $request->session()->forget('admin_totp_setup_secret');

        return ApiResponseType::sendJsonResponse(true, 'TOTP enabled successfully.', [
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string',
            'totp_code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        $admin = Auth::guard('admin')->user();

        if (!Hash::check($validated['password'], $admin->password)) {
            return ApiResponseType::sendJsonResponse(false, 'Invalid password.', [], 422);
        }

        $verified = false;
        if (!empty($validated['totp_code']) && !empty($admin->totp_secret)) {
            $verified = $this->totpService->verifyCode($admin->totp_secret, trim($validated['totp_code']));
        }

        if (!$verified && !empty($validated['recovery_code'])) {
            $recoveryCodes = is_array($admin->totp_recovery_codes) ? $admin->totp_recovery_codes : [];
            $normalized = strtoupper(trim($validated['recovery_code']));
            $index = array_search($normalized, $recoveryCodes, true);
            if ($index !== false) {
                unset($recoveryCodes[$index]);
                $admin->totp_recovery_codes = array_values($recoveryCodes);
                $verified = true;
            }
        }

        if (!$verified) {
            return ApiResponseType::sendJsonResponse(false, 'Invalid TOTP or recovery code.', [], 422);
        }

        $admin->totp_secret = null;
        $admin->totp_enabled_at = null;
        $admin->totp_recovery_codes = null;
        $admin->save();

        return ApiResponseType::sendJsonResponse(true, 'TOTP disabled successfully.', []);
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string',
            'totp_code' => 'required|string',
        ]);

        $admin = Auth::guard('admin')->user();

        if (!Hash::check($validated['password'], $admin->password)) {
            return ApiResponseType::sendJsonResponse(false, 'Invalid password.', [], 422);
        }

        if (empty($admin->totp_secret) || !$this->totpService->verifyCode($admin->totp_secret, trim($validated['totp_code']))) {
            return ApiResponseType::sendJsonResponse(false, 'Invalid TOTP code.', [], 422);
        }

        $recoveryCodes = $this->totpService->generateRecoveryCodes();
        $admin->totp_recovery_codes = $recoveryCodes;
        $admin->save();

        return ApiResponseType::sendJsonResponse(true, 'Recovery codes regenerated successfully.', [
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    private function resolveIssuerName(Request $request): string
    {
        $systemSettings = $this->settingService
            ->getSettingByVariable(SettingTypeEnum::SYSTEM())
            ?->toArray($request)['value'] ?? [];

        return (string) ($systemSettings['appName'] ?? config('app.name', 'Lcommerce'));
    }
}
