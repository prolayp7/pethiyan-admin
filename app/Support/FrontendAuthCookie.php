<?php

namespace App\Support;

use Illuminate\Contracts\Cookie\QueueingFactory as CookieFactory;
use Symfony\Component\HttpFoundation\Cookie;

class FrontendAuthCookie
{
    public const NAME = 'auth_token';
    public const LIFETIME_MINUTES = 43200; // 30 days

    public static function make(string $token): Cookie
    {
        return app(CookieFactory::class)->make(
            name: self::NAME,
            value: $token,
            minutes: self::LIFETIME_MINUTES,
            path: '/',
            domain: env('FRONTEND_AUTH_COOKIE_DOMAIN'),
            secure: app()->environment('production'),
            httpOnly: true,
            raw: false,
            sameSite: env('FRONTEND_AUTH_COOKIE_SAMESITE', 'lax')
        );
    }

    public static function forget(): Cookie
    {
        return app(CookieFactory::class)->forget(
            name: self::NAME,
            path: '/',
            domain: env('FRONTEND_AUTH_COOKIE_DOMAIN')
        );
    }
}
