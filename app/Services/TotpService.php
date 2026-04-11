<?php

namespace App\Services;

class TotpService
{
    public function generateSecret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $max = strlen($alphabet) - 1;
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, $max)];
        }

        return $secret;
    }

    public function getProvisioningUri(string $issuer, string $accountName, string $secret): string
    {
        $issuerEncoded = rawurlencode($issuer);
        $accountEncoded = rawurlencode($accountName);

        return "otpauth://totp/{$issuerEncoded}:{$accountEncoded}?secret={$secret}&issuer={$issuerEncoded}&algorithm=SHA1&digits=6&period=30";
    }

    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timestamp = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            $otp = $this->generateTotp($secret, $timestamp + $i);
            if (hash_equals($otp, $code)) {
                return true;
            }
        }

        return false;
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }

    private function generateTotp(string $secret, int $timeSlice): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncatedHash = substr($hash, $offset, 4);
        $value = unpack('N', $truncatedHash)[1] & 0x7FFFFFFF;
        $otp = $value % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(str_replace('=', '', preg_replace('/\s+/', '', $secret)));

        $bits = '';
        $decoded = '';

        $length = strlen($secret);
        for ($i = 0; $i < $length; $i++) {
            $char = $secret[$i];
            $position = strpos($alphabet, $char);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $chunks = str_split($bits, 8);
        foreach ($chunks as $chunk) {
            if (strlen($chunk) === 8) {
                $decoded .= chr(bindec($chunk));
            }
        }

        return $decoded;
    }
}
