<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * @method static create(array $data)
 * @method static where(string $column, mixed $value)
 */
class OtpVerification extends Model
{
    protected $fillable = [
        'mobile',
        'email',
        'country_code',
        'otp',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
        'attempts'    => 'integer',
    ];

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function hasExceededAttempts(int $max = 5): bool
    {
        return $this->attempts >= $max;
    }

    public function checkOtp(string $plainOtp): bool
    {
        return Hash::check($plainOtp, $this->otp);
    }

    /**
     * Find the latest unused, unexpired record for a mobile number.
     */
    public static function findLatest(string $mobile, string $countryCode): ?static
    {
        return static::where('mobile', $mobile)
            ->where('country_code', $countryCode)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Find the latest unused, unexpired record for an email address.
     */
    public static function findLatestByEmail(string $email): ?static
    {
        return static::where('email', $email)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Invalidate all previous OTPs for a mobile (mark as expired).
     */
    public static function invalidatePrevious(string $mobile, string $countryCode): void
    {
        static::where('mobile', $mobile)
            ->where('country_code', $countryCode)
            ->whereNull('verified_at')
            ->update(['expires_at' => now()]);
    }

    /**
     * Invalidate all previous OTPs for an email (mark as expired).
     */
    public static function invalidatePreviousByEmail(string $email): void
    {
        static::where('email', $email)
            ->whereNull('verified_at')
            ->update(['expires_at' => now()]);
    }
}
